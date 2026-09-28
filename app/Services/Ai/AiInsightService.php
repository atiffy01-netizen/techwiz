<?php

namespace App\Services\Ai;

use App\Models\AiMonthlyInsight;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Ai\Providers\FallbackAiProvider;
use App\Services\ReportAnalyticsService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AiInsightService
{
    /**
     * Retrieve the stored monthly insight or generate one if none exists.
     *
     * @param User $user
     * @param string|null $month Format 'YYYY-MM'
     * @return AiMonthlyInsight
     */
    public static function getOrCreateInsight(User $user, ?string $month = null): AiMonthlyInsight
    {
        $month = $month ?: Carbon::now()->format('Y-m');

        $existing = AiMonthlyInsight::where('user_id', $user->id)
            ->where('month', $month)
            ->first();

        if ($existing) {
            return $existing;
        }

        return static::generateInsight($user, $month, false);
    }

    /**
     * Generate or regenerate monthly insight for a user.
     *
     * @param User $user
     * @param string $month
     * @param bool $force
     * @return AiMonthlyInsight
     */
    public static function generateInsight(User $user, string $month, bool $force = false): AiMonthlyInsight
    {
        if (!$force) {
            $existing = AiMonthlyInsight::where('user_id', $user->id)
                ->where('month', $month)
                ->first();
            if ($existing) {
                return $existing;
            }
        }

        // 1. Gather Aggregated Financial Analytics (Strictly user-isolated)
        $aggregatedData = static::aggregateFinancialData($user, $month);

        // 2. Request from AI Provider
        $provider = AiManager::provider();
        $aiResult = $provider->generateMonthlyInsight($aggregatedData);
        $status = AiMonthlyInsight::STATUS_AI_GENERATED;
        $providerName = $provider->getName();
        $modelName = config("ai.{$providerName}.model", 'ai-model');

        // 3. Fallback if provider returns null or fails
        if (!$aiResult) {
            $fallbackProvider = new FallbackAiProvider();
            $aiResult = $fallbackProvider->generateMonthlyInsight($aggregatedData);
            $status = AiMonthlyInsight::STATUS_FALLBACK;
            $providerName = 'fallback';
            $modelName = 'deterministic-analytics';
        }

        // 4. Clean and Validate Response Structure
        $summary = $aiResult['summary'] ?? "Automated monthly financial summary for {$aggregatedData['month_label']}.";
        $highlights = is_array($aiResult['highlights'] ?? null) ? $aiResult['highlights'] : [];
        $actions = is_array($aiResult['actions'] ?? null) ? $aiResult['actions'] : [];

        // 5. Store / Update in Database (Controlled single record per user/month)
        return AiMonthlyInsight::updateOrCreate(
            [
                'user_id' => $user->id,
                'month' => $month,
            ],
            [
                'summary' => $summary,
                'highlights' => $highlights,
                'actions' => $actions,
                'status' => $status,
                'provider' => $providerName,
                'model' => $modelName,
                'metadata' => [
                    'total_income' => $aggregatedData['total_income'],
                    'total_expense' => $aggregatedData['total_expense'],
                    'net_balance' => $aggregatedData['net_balance'],
                    'top_category' => $aggregatedData['top_category_name'],
                ],
                'generated_at' => now(),
            ]
        );
    }

    /**
     * Gather aggregated metrics for AI analysis without exposing sensitive information.
     */
    public static function aggregateFinancialData(User $user, string $month): array
    {
        $parsedDate = Carbon::createFromFormat('Y-m', $month);
        $startOfMonth = $parsedDate->copy()->startOfMonth()->toDateString();
        $endOfMonth = $parsedDate->copy()->endOfMonth()->toDateString();
        $monthLabel = $parsedDate->format('F Y');

        // Current month totals
        $totalIncome = (float) Transaction::where('user_id', $user->id)
            ->where('type', 'income')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $totalExpense = (float) Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $netBalance = $totalIncome - $totalExpense;
        $totalTransactionsCount = Transaction::where('user_id', $user->id)
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->count();

        // Category Breakdown
        $categoryBreakdown = Transaction::where('transactions.user_id', $user->id)
            ->where('transactions.type', 'expense')
            ->whereBetween('transactions.transaction_date', [$startOfMonth, $endOfMonth])
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->select('categories.name', DB::raw('SUM(transactions.amount) as cat_total'))
            ->groupBy('categories.name')
            ->orderByDesc('cat_total')
            ->get()
            ->map(function ($row) use ($totalExpense) {
                $amount = (float) $row->cat_total;
                $pct = $totalExpense > 0 ? round(($amount / $totalExpense) * 100, 1) : 0.0;
                return [
                    'name' => $row->name,
                    'amount' => $amount,
                    'percentage' => $pct,
                ];
            })
            ->toArray();

        $topCategory = $categoryBreakdown[0] ?? null;

        // Month-over-Month comparison
        $prevMonthDate = $parsedDate->copy()->subMonth();
        $prevStart = $prevMonthDate->startOfMonth()->toDateString();
        $prevEnd = $prevMonthDate->endOfMonth()->toDateString();

        $prevIncome = (float) Transaction::where('user_id', $user->id)
            ->where('type', 'income')
            ->whereBetween('transaction_date', [$prevStart, $prevEnd])
            ->sum('amount');

        $prevExpense = (float) Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$prevStart, $prevEnd])
            ->sum('amount');

        $hasHistory = ($prevExpense > 0 || $prevIncome > 0);
        $expenseChangePercent = $hasHistory && $prevExpense > 0
            ? round((($totalExpense - $prevExpense) / $prevExpense) * 100, 1)
            : null;

        // Budget context
        $budgets = Budget::with('category')
            ->where('user_id', $user->id)
            ->where('month', $month)
            ->get();

        $nearLimitBudgets = [];
        $overBudgetBudgets = [];
        foreach ($budgets as $b) {
            $catName = $b->category ? $b->category->name : 'Category';
            $actual = $b->getActualSpending();
            $limit = (float) $b->limit_amount;
            if ($limit > 0) {
                $usage = ($actual / $limit) * 100;
                if ($usage >= 100) {
                    $overBudgetBudgets[] = $catName;
                } elseif ($usage >= 75) {
                    $nearLimitBudgets[] = $catName;
                }
            }
        }

        return [
            'month' => $month,
            'month_label' => $monthLabel,
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'net_balance' => $netBalance,
            'total_transactions_count' => $totalTransactionsCount,
            'category_breakdown' => $categoryBreakdown,
            'top_category_name' => $topCategory['name'] ?? null,
            'top_category_amount' => $topCategory['amount'] ?? 0.0,
            'top_category_percent' => $topCategory['percentage'] ?? 0.0,
            'has_history' => $hasHistory,
            'expense_change_percent' => $expenseChangePercent,
            'near_limit_budgets' => $nearLimitBudgets,
            'over_budget_categories' => $overBudgetBudgets,
            'academic_program' => $user->program ?? 'Student',
        ];
    }

    /**
     * Retrieve past stored monthly insights for the user.
     *
     * @param User $user
     * @param string|null $excludeMonth
     * @param int $limit
     * @return Collection<AiMonthlyInsight>
     */
    public static function getPastInsights(User $user, ?string $excludeMonth = null, int $limit = 6): Collection
    {
        $query = AiMonthlyInsight::where('user_id', $user->id)
            ->orderBy('month', 'desc');

        if ($excludeMonth) {
            $query->where('month', '!=', $excludeMonth);
        }

        return $query->take($limit)->get();
    }
}
