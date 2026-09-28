<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\Category;
use App\Models\SavingTip;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SavingTipService
{
    /**
     * Generate or refresh deterministic saving tips for a user in a given month.
     *
     * @param User $user
     * @param string|null $month Format 'YYYY-MM', defaults to current month
     * @return Collection<SavingTip> Active tips (ordered by rank)
     */
    public static function generateTipsForUser(User $user, ?string $month = null): Collection
    {
        $month = $month ?: Carbon::now()->format('Y-m');
        $parsedMonth = Carbon::createFromFormat('Y-m', $month);
        $startOfMonth = $parsedMonth->copy()->startOfMonth()->toDateString();
        $endOfMonth = $parsedMonth->copy()->endOfMonth()->toDateString();
        $monthLabel = $parsedMonth->format('F Y');

        // Check if user has any transactions
        $totalUserTxnCount = Transaction::where('user_id', $user->id)->count();
        if ($totalUserTxnCount === 0) {
            return collect();
        }

        $candidates = [];

        // -------------------------------------------------------------
        // 1. Current Month Expenses Data
        // -------------------------------------------------------------
        $currentMonthExpenses = Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->get();

        $totalMonthlyExpense = (float) $currentMonthExpenses->sum('amount');
        $currentMonthIncome = (float) Transaction::where('user_id', $user->id)
            ->where('type', 'income')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $categorySpending = $currentMonthExpenses->groupBy('category_id')->map(function ($txns) {
            return (float) $txns->sum('amount');
        });

        // -------------------------------------------------------------
        // 2. Historical Averages by Category (Prior 1-6 months)
        // -------------------------------------------------------------
        $priorMonthsData = Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->where('transaction_date', '<', $startOfMonth)
            ->where('transaction_date', '>=', $parsedMonth->copy()->subMonths(6)->startOfMonth()->toDateString())
            ->select(
                'category_id',
                DB::raw("DATE_FORMAT(transaction_date, '%Y-%m') as ym"),
                DB::raw('SUM(amount) as monthly_cat_sum')
            )
            ->groupBy('category_id', 'ym')
            ->get();

        $historicalCategoryAverages = [];
        $historicalMonthsCount = [];

        foreach ($priorMonthsData as $row) {
            $catId = $row->category_id;
            if (!isset($historicalCategoryAverages[$catId])) {
                $historicalCategoryAverages[$catId] = 0.0;
                $historicalMonthsCount[$catId] = 0;
            }
            $historicalCategoryAverages[$catId] += (float) $row->monthly_cat_sum;
            $historicalMonthsCount[$catId]++;
        }

        foreach ($historicalCategoryAverages as $catId => $sum) {
            $count = $historicalMonthsCount[$catId] ?: 1;
            $historicalCategoryAverages[$catId] = round($sum / $count, 2);
        }

        // Cache category models
        $allCategories = Category::forUser($user->id)->get()->keyBy('id');

        // Evaluate Rule: ABOVE_HISTORICAL_AVERAGE & IMPROVEMENT
        foreach ($categorySpending as $catId => $spent) {
            if (isset($historicalCategoryAverages[$catId]) && $historicalMonthsCount[$catId] >= 1) {
                $avg = $historicalCategoryAverages[$catId];
                $catName = isset($allCategories[$catId]) ? $allCategories[$catId]->name : 'Category';

                // If spending is significantly above average (>15% higher and >= Rs. 500 above avg)
                if ($avg > 0 && $spent >= ($avg * 1.15) && ($spent - $avg) >= 500) {
                    $diff = round($spent - $avg, 2);
                    $monthsActive = $historicalMonthsCount[$catId];
                    $candidates[] = [
                        'signature' => SavingTip::TYPE_ABOVE_HISTORICAL_AVERAGE . "_{$catId}",
                        'tip_type' => SavingTip::TYPE_ABOVE_HISTORICAL_AVERAGE,
                        'category_id' => $catId,
                        'title' => "Above Average: {$catName}",
                        'message' => "Your {$catName} spending is Rs. " . number_format($spent, 2) . " this month, which is Rs. " . number_format($diff, 2) . " above your {$monthsActive}-month average of Rs. " . number_format($avg, 2) . ". Consider setting a weekly limit.",
                        'potential_savings' => $diff,
                        'priority' => 80 + min(15, (int) round($diff / 500)),
                        'metadata' => [
                            'current_spent' => $spent,
                            'historical_average' => $avg,
                            'difference' => $diff,
                            'months_count' => $monthsActive,
                        ],
                    ];
                } elseif ($avg >= 1500 && $spent <= ($avg * 0.80) && ($avg - $spent) >= 500) {
                    // Rule: IMPROVEMENT (Spent >= 20% less than historical average)
                    $saved = round($avg - $spent, 2);
                    $candidates[] = [
                        'signature' => SavingTip::TYPE_IMPROVEMENT . "_{$catId}",
                        'tip_type' => SavingTip::TYPE_IMPROVEMENT,
                        'category_id' => $catId,
                        'title' => "Great Control: {$catName}",
                        'message' => "You've reduced {$catName} expenses by Rs. " . number_format($saved, 2) . " compared to your past average (Rs. " . number_format($avg, 2) . "). Keep up the disciplined spending!",
                        'potential_savings' => null,
                        'priority' => 45,
                        'metadata' => [
                            'current_spent' => $spent,
                            'historical_average' => $avg,
                            'saved_amount' => $saved,
                        ],
                    ];
                }
            }
        }

        // -------------------------------------------------------------
        // 3. Budget-Based Tips (Phase 4 Budget System)
        // -------------------------------------------------------------
        $budgets = Budget::with('category')
            ->where('user_id', $user->id)
            ->where('month', $month)
            ->get();

        foreach ($budgets as $budget) {
            $catId = $budget->category_id;
            $catName = $budget->category ? $budget->category->name : 'Category';
            $limit = (float) $budget->limit_amount;
            $spent = (float) ($categorySpending[$catId] ?? 0.0);

            if ($limit <= 0) {
                continue;
            }

            $usagePercent = round(($spent / $limit) * 100, 1);

            if ($usagePercent >= Budget::THRESHOLD_OVER_BUDGET) {
                // Rule: OVER_BUDGET
                $overage = round($spent - $limit, 2);
                $candidates[] = [
                    'signature' => SavingTip::TYPE_OVER_BUDGET . "_{$catId}",
                    'tip_type' => SavingTip::TYPE_OVER_BUDGET,
                    'category_id' => $catId,
                    'title' => "Over Budget: {$catName}",
                    'message' => "Your {$catName} spending of Rs. " . number_format($spent, 2) . " has exceeded your Rs. " . number_format($limit, 2) . " limit by Rs. " . number_format($overage, 2) . ". Postpone non-essential expenses for the rest of {$monthLabel}.",
                    'potential_savings' => $overage,
                    'priority' => 95,
                    'metadata' => [
                        'limit_amount' => $limit,
                        'spent_amount' => $spent,
                        'overage' => $overage,
                        'usage_percentage' => $usagePercent,
                    ],
                ];
            } elseif ($usagePercent >= Budget::THRESHOLD_NEAR_LIMIT) {
                // Rule: NEAR_BUDGET_LIMIT
                $remaining = round(max(0, $limit - $spent), 2);
                $candidates[] = [
                    'signature' => SavingTip::TYPE_NEAR_BUDGET_LIMIT . "_{$catId}",
                    'tip_type' => SavingTip::TYPE_NEAR_BUDGET_LIMIT,
                    'category_id' => $catId,
                    'title' => "Approaching Limit: {$catName}",
                    'message' => "You have used {$usagePercent}% of your {$catName} budget for {$monthLabel}. Only Rs. " . number_format($remaining, 2) . " remains of your Rs. " . number_format($limit, 2) . " limit.",
                    'potential_savings' => $remaining > 0 ? $remaining : null,
                    'priority' => 85,
                    'metadata' => [
                        'limit_amount' => $limit,
                        'spent_amount' => $spent,
                        'remaining' => $remaining,
                        'usage_percentage' => $usagePercent,
                    ],
                ];
            }
        }

        // -------------------------------------------------------------
        // 4. High-Spending Category Detection
        // -------------------------------------------------------------
        if ($totalMonthlyExpense >= 1000) {
            $sortedCats = $categorySpending->sortDesc();
            $topCatId = $sortedCats->keys()->first();
            $topCatAmount = $sortedCats->first();

            if ($topCatId && $topCatAmount > 0) {
                $topCatPct = round(($topCatAmount / $totalMonthlyExpense) * 100, 1);
                $topCatName = isset($allCategories[$topCatId]) ? $allCategories[$topCatId]->name : 'Top Category';

                // If single category consumes >= 35% of total expenses
                if ($topCatPct >= 35.0 && $topCatAmount >= 1500) {
                    $excessOver25Pct = round(max(0, $topCatAmount - ($totalMonthlyExpense * 0.25)), 2);
                    $candidates[] = [
                        'signature' => SavingTip::TYPE_HIGH_SPENDING_CATEGORY . "_{$topCatId}",
                        'tip_type' => SavingTip::TYPE_HIGH_SPENDING_CATEGORY,
                        'category_id' => $topCatId,
                        'title' => "Dominant Expense: {$topCatName}",
                        'message' => "{$topCatName} accounts for {$topCatPct}% (Rs. " . number_format($topCatAmount, 2) . ") of your total spending this month. Look for opportunities to moderate large transactions in this area.",
                        'potential_savings' => $excessOver25Pct > 0 ? $excessOver25Pct : null,
                        'priority' => 70,
                        'metadata' => [
                            'category_amount' => $topCatAmount,
                            'total_monthly_expense' => $totalMonthlyExpense,
                            'percentage' => $topCatPct,
                        ],
                    ];
                }
            }
        }

        // -------------------------------------------------------------
        // 5. Repeated Subscriptions / Recurring Services
        // -------------------------------------------------------------
        $subscriptionCategories = Category::forUser($user->id)
            ->where(function ($q) {
                $q->where('name', 'LIKE', '%subscription%')
                  ->orWhere('name', 'LIKE', '%entertainment%')
                  ->orWhere('name', 'LIKE', '%streaming%');
            })
            ->pluck('id')
            ->toArray();

        $subExpenses = $currentMonthExpenses->filter(function ($t) use ($subscriptionCategories) {
            $desc = strtolower($t->description ?? '');
            return in_array($t->category_id, $subscriptionCategories) ||
                str_contains($desc, 'netflix') ||
                str_contains($desc, 'spotify') ||
                str_contains($desc, 'youtube') ||
                str_contains($desc, 'prime') ||
                str_contains($desc, 'gym') ||
                str_contains($desc, 'icloud') ||
                str_contains($desc, 'subscription');
        });

        $subTotal = (float) $subExpenses->sum('amount');
        if ($subTotal >= 500) {
            $subPotential = round($subTotal * 0.50, 2); // E.g., sharing plans saves 50%
            $firstSubCatId = $subExpenses->first()?->category_id;
            $candidates[] = [
                'signature' => SavingTip::TYPE_REPEATED_SUBSCRIPTION . "_general",
                'tip_type' => SavingTip::TYPE_REPEATED_SUBSCRIPTION,
                'category_id' => $firstSubCatId,
                'title' => "Review Recurring Subscriptions",
                'message' => "You spent Rs. " . number_format($subTotal, 2) . " across " . $subExpenses->count() . " recurring subscription/service transactions this month. Consider family plans or cancelling unused memberships.",
                'potential_savings' => $subPotential,
                'priority' => 65,
                'metadata' => [
                    'total_subscription_amount' => $subTotal,
                    'transaction_count' => $subExpenses->count(),
                ],
            ];
        }

        // -------------------------------------------------------------
        // 6. Daily / Weekly Spending Pattern Analysis
        // -------------------------------------------------------------
        if ($totalMonthlyExpense >= 2000) {
            // Check past 7 days spending
            $sevenDaysAgo = $parsedMonth->copy()->endOfMonth()->min(Carbon::now())->subDays(7)->toDateString();
            $recentWeekSpending = (float) $currentMonthExpenses
                ->where('transaction_date', '>=', $sevenDaysAgo)
                ->sum('amount');

            $weekPct = round(($recentWeekSpending / $totalMonthlyExpense) * 100, 1);

            if ($weekPct >= 45.0 && $recentWeekSpending >= 1500) {
                $weeklyAvg = round($totalMonthlyExpense / 4, 2);
                $weekPotential = round(max(0, $recentWeekSpending - $weeklyAvg), 2);
                $candidates[] = [
                    'signature' => SavingTip::TYPE_HIGH_WEEKLY_SPENDING . "_general",
                    'tip_type' => SavingTip::TYPE_HIGH_WEEKLY_SPENDING,
                    'category_id' => null,
                    'title' => "High Recent Weekly Spending",
                    'message' => "You spent Rs. " . number_format($recentWeekSpending, 2) . " ({$weekPct}% of the monthly total) over the last 7 days. Pacing your weekly allowance helps avoid cash shortfalls.",
                    'potential_savings' => $weekPotential > 0 ? $weekPotential : null,
                    'priority' => 60,
                    'metadata' => [
                        'recent_week_spent' => $recentWeekSpending,
                        'weekly_percentage' => $weekPct,
                    ],
                ];
            }

            // Check single-day spending spikes
            $dailyGroups = $currentMonthExpenses->groupBy(function ($t) {
                return $t->transaction_date ? $t->transaction_date->format('Y-m-d') : '';
            });

            foreach ($dailyGroups as $date => $dayTxns) {
                $dayTotal = (float) $dayTxns->sum('amount');
                if ($dayTotal >= 3000 && ($dayTotal / $totalMonthlyExpense) >= 0.35) {
                    $formattedDate = Carbon::parse($date)->format('M d');
                    $candidates[] = [
                        'signature' => SavingTip::TYPE_HIGH_DAILY_SPENDING . "_{$date}",
                        'tip_type' => SavingTip::TYPE_HIGH_DAILY_SPENDING,
                        'category_id' => null,
                        'title' => "Spending Spike on {$formattedDate}",
                        'message' => "You had a significant single-day outflow of Rs. " . number_format($dayTotal, 2) . " on {$formattedDate}. Planning large irregular purchases in advance protects your monthly budget.",
                        'potential_savings' => null,
                        'priority' => 55,
                        'metadata' => [
                            'spike_date' => $date,
                            'spike_amount' => $dayTotal,
                            'txns_count' => $dayTxns->count(),
                        ],
                    ];
                    break; // Keep only the largest single day spike
                }
            }
        }

        // -------------------------------------------------------------
        // 7. Saving Opportunity / Surplus Goal Allocation
        // -------------------------------------------------------------
        $netMonthlySavings = $currentMonthIncome - $totalMonthlyExpense;
        $savingsGoal = (float) ($user->savings_goal > 0 ? $user->savings_goal : 5000.0);

        if ($netMonthlySavings >= 1000 && $currentMonthIncome > 0) {
            $allocatable = round(min($netMonthlySavings, $savingsGoal), 2);
            $candidates[] = [
                'signature' => SavingTip::TYPE_SAVING_OPPORTUNITY . "_surplus",
                'tip_type' => SavingTip::TYPE_SAVING_OPPORTUNITY,
                'category_id' => null,
                'title' => "Monthly Surplus Opportunity",
                'message' => "You have a positive cash flow of Rs. " . number_format($netMonthlySavings, 2) . " this month. You can direct Rs. " . number_format($allocatable, 2) . " toward your target savings goal.",
                'potential_savings' => $allocatable,
                'priority' => 50,
                'metadata' => [
                    'net_savings' => $netMonthlySavings,
                    'savings_goal' => $savingsGoal,
                ],
            ];
        }

        // -------------------------------------------------------------
        // 8. Database Synchronization & Duplication Control
        // -------------------------------------------------------------
        $existingTips = SavingTip::where('user_id', $user->id)
            ->where('reference_month', $month)
            ->get();

        $existingBySig = [];
        foreach ($existingTips as $tip) {
            $sig = $tip->tip_type . '_' . ($tip->category_id ?: ($tip->metadata['spike_date'] ?? 'general'));
            $existingBySig[$sig] = $tip;
        }

        $activeSignatures = [];

        foreach ($candidates as $cand) {
            $sig = $cand['signature'];
            $activeSignatures[] = $sig;

            if (isset($existingBySig[$sig])) {
                // Update existing record while preserving is_pinned & dismissed_at
                $existing = $existingBySig[$sig];
                $existing->update([
                    'title' => $cand['title'],
                    'message' => $cand['message'],
                    'potential_savings' => $cand['potential_savings'],
                    'priority' => $cand['priority'],
                    'category_id' => $cand['category_id'],
                    'metadata' => $cand['metadata'],
                ]);
            } else {
                // Create new record
                SavingTip::create([
                    'user_id' => $user->id,
                    'category_id' => $cand['category_id'],
                    'tip_type' => $cand['tip_type'],
                    'title' => $cand['title'],
                    'message' => $cand['message'],
                    'potential_savings' => $cand['potential_savings'],
                    'priority' => $cand['priority'],
                    'reference_month' => $month,
                    'is_pinned' => false,
                    'dismissed_at' => null,
                    'metadata' => $cand['metadata'],
                ]);
            }
        }

        // Clean up unpinned, obsolete tips that no longer match spending conditions
        foreach ($existingBySig as $sig => $tip) {
            if (!in_array($sig, $activeSignatures) && !$tip->is_pinned) {
                // If dismissed or regular unpinned, delete stale calculation
                $tip->delete();
            }
        }

        // Fetch fresh ranked active tips
        return SavingTip::with('category')
            ->where('user_id', $user->id)
            ->where('reference_month', $month)
            ->ranked()
            ->get();
    }

    /**
     * Check if user has sufficient historical transaction data.
     *
     * @param User $user
     * @param string|null $month
     * @return bool
     */
    public static function hasSufficientHistory(User $user, ?string $month = null): bool
    {
        $month = $month ?: Carbon::now()->format('Y-m');
        $startOfMonth = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString();

        $priorMonthsCount = Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->where('transaction_date', '<', $startOfMonth)
            ->select(DB::raw("DISTINCT DATE_FORMAT(transaction_date, '%Y-%m') as ym"))
            ->count();

        return $priorMonthsCount >= 1;
    }

    /**
     * Calculate total active potential savings for a user in a given month.
     *
     * @param User $user
     * @param string|null $month
     * @return float
     */
    public static function getTotalPotentialSavings(User $user, ?string $month = null): float
    {
        $month = $month ?: Carbon::now()->format('Y-m');

        return (float) SavingTip::where('user_id', $user->id)
            ->where('reference_month', $month)
            ->whereNull('dismissed_at')
            ->whereNotNull('potential_savings')
            ->sum('potential_savings');
    }
}
