<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SpendingForecastService
{
    /**
     * Generate deterministic upcoming-month spending forecast for a user.
     *
     * @param User $user
     * @param string|null $referenceMonth 'YYYY-MM'
     * @return array
     */
    public static function generateForecast(User $user, ?string $referenceMonth = null): array
    {
        $currentMonth = $referenceMonth ? Carbon::createFromFormat('Y-m', $referenceMonth) : Carbon::now();
        $upcomingMonth = $currentMonth->copy()->addMonth();
        $upcomingMonthKey = $upcomingMonth->format('Y-m');
        $upcomingMonthLabel = $upcomingMonth->format('F Y');

        // Look at past 3 to 6 completed months (excluding current active month)
        $completedMonthsData = [];
        $totalHistoricalExpense = 0.0;
        $categoryTotals = []; // [category_id => ['amount' => float, 'name' => string, 'icon' => string]]

        for ($i = 1; $i <= 6; $i++) {
            $mDate = $currentMonth->copy()->subMonths($i);
            $mStart = $mDate->copy()->startOfMonth()->toDateString();
            $mEnd = $mDate->copy()->endOfMonth()->toDateString();
            $mKey = $mDate->format('Y-m');
            $mLabel = $mDate->format('F Y');

            $mExpense = (float) Transaction::where('user_id', $user->id)
                ->where('type', 'expense')
                ->whereBetween('transaction_date', [$mStart, $mEnd])
                ->sum('amount');

            $mCount = Transaction::where('user_id', $user->id)
                ->where('type', 'expense')
                ->whereBetween('transaction_date', [$mStart, $mEnd])
                ->count();

            if ($mCount > 0 || $mExpense > 0) {
                $completedMonthsData[] = [
                    'month_key' => $mKey,
                    'month_label' => $mLabel,
                    'total_expense' => $mExpense,
                    'transaction_count' => $mCount,
                ];
                $totalHistoricalExpense += $mExpense;

                // Group by category for this month
                $catExpenses = Transaction::where('transactions.user_id', $user->id)
                    ->where('transactions.type', 'expense')
                    ->whereBetween('transactions.transaction_date', [$mStart, $mEnd])
                    ->join('categories', 'transactions.category_id', '=', 'categories.id')
                    ->select('categories.id', 'categories.name', 'categories.icon', DB::raw('SUM(transactions.amount) as cat_total'))
                    ->groupBy('categories.id', 'categories.name', 'categories.icon')
                    ->get();

                foreach ($catExpenses as $c) {
                    $cid = $c->id;
                    if (!isset($categoryTotals[$cid])) {
                        $categoryTotals[$cid] = [
                            'name' => $c->name,
                            'icon' => $c->icon ?: 'bi-tag',
                            'total_amount' => 0.0,
                        ];
                    }
                    $categoryTotals[$cid]['total_amount'] += (float) $c->cat_total;
                }
            }
        }

        $completedMonthsCount = count($completedMonthsData);

        // Insufficient historical data threshold (< 1 historical month with activity)
        if ($completedMonthsCount < 1) {
            return [
                'has_sufficient_data' => false,
                'upcoming_month' => $upcomingMonthKey,
                'upcoming_month_label' => $upcomingMonthLabel,
                'message' => 'Not enough historical data to generate a reliable forecast. Continue recording your monthly expenses to unlock forecasts!',
                'estimated_total_spending' => 0.0,
                'category_forecasts' => [],
                'completed_months_count' => 0,
                'historical_months' => [],
                'previous_month_expense' => 0.0,
                'difference_from_prev' => 0.0,
                'percent_change' => 0.0,
            ];
        }

        // 1. Total Estimated Upcoming Spending (Average of active completed historical months)
        $estimatedTotal = round($totalHistoricalExpense / $completedMonthsCount, 2);

        // 2. Category Forecasts
        $categoryForecasts = [];
        foreach ($categoryTotals as $cid => $data) {
            $catAverage = round($data['total_amount'] / $completedMonthsCount, 2);
            $catPercent = $estimatedTotal > 0 ? round(($catAverage / $estimatedTotal) * 100, 1) : 0.0;

            $categoryForecasts[] = [
                'name' => $data['name'],
                'icon' => $data['icon'],
                'estimated_amount' => $catAverage,
                'percentage' => $catPercent,
            ];
        }

        // Sort categories by highest estimated spending
        usort($categoryForecasts, fn($a, $b) => $b['estimated_amount'] <=> $a['estimated_amount']);

        // 3. Comparison with previous completed month
        $prevCompletedMonth = $completedMonthsData[0] ?? null;
        $prevMonthExpense = $prevCompletedMonth ? (float) $prevCompletedMonth['total_expense'] : 0.0;
        $differenceFromPrev = round($estimatedTotal - $prevMonthExpense, 2);
        $percentChange = $prevMonthExpense > 0 ? round(($differenceFromPrev / $prevMonthExpense) * 100, 1) : 0.0;

        return [
            'has_sufficient_data' => true,
            'upcoming_month' => $upcomingMonthKey,
            'upcoming_month_label' => $upcomingMonthLabel,
            'estimated_total_spending' => $estimatedTotal,
            'completed_months_count' => $completedMonthsCount,
            'historical_months' => $completedMonthsData,
            'category_forecasts' => $categoryForecasts,
            'previous_month_expense' => $prevMonthExpense,
            'difference_from_prev' => $differenceFromPrev,
            'percent_change' => $percentChange,
            'basis_explanation' => "Estimated based on average spending across your {$completedMonthsCount} recent completed month(s).",
        ];
    }
}
