<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportAnalyticsService
{
    /**
     * Build comprehensive report dataset for a given user and filter set.
     */
    public function getReportData(User $user, array $filters = []): array
    {
        // 1. Resolve Period & Dates
        $resolvedDates = $this->resolveDateRange($filters);
        $startDate = $resolvedDates['start_date'];
        $endDate = $resolvedDates['end_date'];
        $periodType = $resolvedDates['period_type']; // 'month' or 'custom'
        $selectedMonth = $resolvedDates['selected_month']; // 'YYYY-MM'
        $periodLabel = $resolvedDates['period_label'];
        $totalDays = $resolvedDates['total_days'];

        // 2. Base Query Filters
        $typeFilter = $filters['type'] ?? 'all'; // 'all', 'income', 'expense'
        $categoryId = !empty($filters['category_id']) && $filters['category_id'] !== 'all' ? (int) $filters['category_id'] : null;
        $incomeSourceId = !empty($filters['income_source_id']) && $filters['income_source_id'] !== 'all' ? (int) $filters['income_source_id'] : null;

        // 3. User Categories for Filter Dropdowns
        $expenseCategories = Category::where(function ($q) use ($user) {
            $q->whereNull('user_id')->orWhere('user_id', $user->id);
        })->where('type', 'expense')->orderBy('name')->get();

        $incomeCategories = Category::where(function ($q) use ($user) {
            $q->whereNull('user_id')->orWhere('user_id', $user->id);
        })->where('type', 'income')->orderBy('name')->get();

        $selectedCategory = $categoryId ? Category::find($categoryId) : null;
        $selectedIncomeSource = $incomeSourceId ? Category::find($incomeSourceId) : null;

        // 4. Filtered Transactions Query (User Isolated)
        $txQuery = Transaction::with('category')
            ->where('user_id', $user->id)
            ->whereBetween('transaction_date', [$startDate, $endDate]);

        if ($typeFilter !== 'all' && in_array($typeFilter, ['income', 'expense'])) {
            $txQuery->where('type', $typeFilter);
        }

        if ($categoryId) {
            $txQuery->where('category_id', $categoryId);
        } elseif ($incomeSourceId) {
            $txQuery->where('category_id', $incomeSourceId)->where('type', 'income');
        }

        // 5. Summary KPI Calculations
        // Note: For overview totals, we also calculate the unconstrained (all types) income & expense for the period
        $unconstrainedIncome = (float) Transaction::where('user_id', $user->id)
            ->where('type', 'income')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->when($incomeSourceId, fn($q) => $q->where('category_id', $incomeSourceId))
            ->when($categoryId && $selectedCategory && $selectedCategory->type === 'income', fn($q) => $q->where('category_id', $categoryId))
            ->when($categoryId && $selectedCategory && $selectedCategory->type === 'expense', fn($q) => $q->whereRaw('1=0'))
            ->sum('amount');

        $unconstrainedExpense = (float) Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->when($categoryId && $selectedCategory && $selectedCategory->type === 'expense', fn($q) => $q->where('category_id', $categoryId))
            ->when($categoryId && $selectedCategory && $selectedCategory->type === 'income', fn($q) => $q->whereRaw('1=0'))
            ->when($incomeSourceId, fn($q) => $q->whereRaw('1=0'))
            ->sum('amount');

        $totalIncome = ($typeFilter === 'expense') ? 0.0 : $unconstrainedIncome;
        $totalExpense = ($typeFilter === 'income') ? 0.0 : $unconstrainedExpense;
        $netBalance = $totalIncome - $totalExpense;

        $incomeCount = Transaction::where('user_id', $user->id)
            ->where('type', 'income')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->when($categoryId, fn($q) => $q->where('category_id', $categoryId))
            ->when($incomeSourceId, fn($q) => $q->where('category_id', $incomeSourceId))
            ->count();

        $expenseCount = Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->when($categoryId, fn($q) => $q->where('category_id', $categoryId))
            ->count();

        $totalTransactionsCount = ($typeFilter === 'income') ? $incomeCount : (($typeFilter === 'expense') ? $expenseCount : ($incomeCount + $expenseCount));

        $savingsRate = ($totalIncome > 0) ? round((($totalIncome - $totalExpense) / $totalIncome) * 100, 1) : 0.0;

        // 6. Category-wise Spending Breakdown
        $categoryBreakdown = $this->calculateCategoryBreakdown($user, $startDate, $endDate, $categoryId);
        $topCategory = $categoryBreakdown->first() ?? null;

        // 7. Income vs Expense Comparison Details
        $incomeVsExpense = [
            'income' => $totalIncome,
            'expense' => $totalExpense,
            'balance' => $netBalance,
            'savings_rate' => $savingsRate,
            'income_count' => $incomeCount,
            'expense_count' => $expenseCount,
        ];

        // 8. Consecutive 6-Month Trend (Current Month + Previous 5 Months)
        $sixMonthTrend = $this->calculateSixMonthTrend($user, $selectedMonth);

        // 9. Daily Spending Summary (For the active period)
        $dailySpending = $this->calculateDailySpending($user, $startDate, $endDate, $categoryId);

        // Find highest spending day
        $highestSpendingDay = null;
        if (!empty($dailySpending['days'])) {
            $highestItem = collect($dailySpending['days'])->where('expense', '>', 0)->sortByDesc('expense')->first();
            if ($highestItem && $highestItem['expense'] > 0) {
                $highestSpendingDay = [
                    'date' => Carbon::parse($highestItem['raw_date'])->format('F d, Y'),
                    'short_date' => Carbon::parse($highestItem['raw_date'])->format('M d'),
                    'amount' => $highestItem['expense'],
                ];
            }
        }

        // Average Daily Spending (Total expenses divided by calendar days in selected period)
        $averageDailySpending = $totalDays > 0 ? round($totalExpense / $totalDays, 2) : 0.0;

        // 10. Weekly Spending Summary (Grouped for selected month or multi-week range)
        $weeklySpending = $this->calculateWeeklySpending($user, $startDate, $endDate, $categoryId);

        // 11. Month-over-Month Comparison (if month period)
        $monthOverMonth = $this->calculateMonthOverMonth($user, $selectedMonth, $totalExpense, $totalIncome);

        // 12. Budget Comparison (Using Phase 4 real budgets for selected month)
        $budgetComparison = $this->calculateBudgetComparison($user, $selectedMonth);

        // 13. Deterministic Highlights
        $highlights = $this->generateDeterministicHighlights(
            $topCategory,
            $monthOverMonth,
            $averageDailySpending,
            $totalDays,
            $savingsRate,
            $netBalance,
            $budgetComparison
        );

        // 14. Active Filter Tags
        $activeFilters = [];
        $activeFilters[] = ['key' => 'period', 'label' => $periodLabel];
        if ($typeFilter !== 'all') {
            $activeFilters[] = ['key' => 'type', 'label' => 'Type: ' . ucfirst($typeFilter)];
        }
        if ($selectedCategory) {
            $activeFilters[] = ['key' => 'category', 'label' => 'Category: ' . $selectedCategory->name];
        }
        if ($selectedIncomeSource) {
            $activeFilters[] = ['key' => 'income_source', 'label' => 'Source: ' . $selectedIncomeSource->name];
        }

        $isFiltered = ($periodType === 'custom') || ($typeFilter !== 'all') || !empty($categoryId) || !empty($incomeSourceId) || ($selectedMonth !== Carbon::now()->format('Y-m'));

        // Available Months for dropdown selector
        $availableMonths = $this->getAvailableMonths();

        return [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'periodType' => $periodType,
            'selectedMonth' => $selectedMonth,
            'periodLabel' => $periodLabel,
            'totalDays' => $totalDays,
            'typeFilter' => $typeFilter,
            'categoryId' => $categoryId,
            'incomeSourceId' => $incomeSourceId,
            'selectedCategory' => $selectedCategory,
            'selectedIncomeSource' => $selectedIncomeSource,
            'activeFilters' => $activeFilters,
            'isFiltered' => $isFiltered,
            'availableMonths' => $availableMonths,
            'expenseCategories' => $expenseCategories,
            'incomeCategories' => $incomeCategories,
            // KPIs
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'netBalance' => $netBalance,
            'totalTransactionsCount' => $totalTransactionsCount,
            'incomeCount' => $incomeCount,
            'expenseCount' => $expenseCount,
            'savingsRate' => $savingsRate,
            // Analytics
            'categoryBreakdown' => $categoryBreakdown,
            'topCategory' => $topCategory,
            'incomeVsExpense' => $incomeVsExpense,
            'sixMonthTrend' => $sixMonthTrend,
            'dailySpending' => $dailySpending,
            'highestSpendingDay' => $highestSpendingDay,
            'averageDailySpending' => $averageDailySpending,
            'weeklySpending' => $weeklySpending,
            'monthOverMonth' => $monthOverMonth,
            'budgetComparison' => $budgetComparison,
            'highlights' => $highlights,
            // Query for pagination/list
            'txQuery' => $txQuery,
        ];
    }

    /**
     * Resolve start date, end date, and metadata from input filters.
     */
    protected function resolveDateRange(array $filters): array
    {
        $periodType = $filters['period_type'] ?? 'month';
        $now = Carbon::now();

        if ($periodType === 'custom' && !empty($filters['start_date']) && !empty($filters['end_date'])) {
            try {
                $start = Carbon::createFromFormat('Y-m-d', $filters['start_date'])->startOfDay();
                $end = Carbon::createFromFormat('Y-m-d', $filters['end_date'])->endOfDay();

                // Validate start <= end
                if ($start->gt($end)) {
                    // Swap if user inverted dates
                    $temp = $start;
                    $start = $end->copy()->startOfDay();
                    $end = $temp->copy()->endOfDay();
                }

                $totalDays = $start->diffInDays($end) + 1;
                $periodLabel = $start->format('M d, Y') . ' — ' . $end->format('M d, Y');
                $selectedMonth = $start->format('Y-m');

                return [
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                    'period_type' => 'custom',
                    'selected_month' => $selectedMonth,
                    'period_label' => $periodLabel,
                    'total_days' => max(1, $totalDays),
                ];
            } catch (\Exception $e) {
                // Fall back to month mode if malformed
            }
        }

        // Monthly Mode
        $monthInput = $filters['month'] ?? $now->format('Y-m');
        if (!preg_match('/^\d{4}-\d{2}$/', $monthInput)) {
            $monthInput = $now->format('Y-m');
        }

        try {
            $monthDate = Carbon::createFromFormat('Y-m', $monthInput);
        } catch (\Exception $e) {
            $monthDate = $now->copy();
            $monthInput = $now->format('Y-m');
        }

        $start = $monthDate->copy()->startOfMonth();
        $end = $monthDate->copy()->endOfMonth();
        $totalDays = $start->daysInMonth;
        $periodLabel = $monthDate->format('F Y');

        return [
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'period_type' => 'month',
            'selected_month' => $monthInput,
            'period_label' => $periodLabel,
            'total_days' => $totalDays,
        ];
    }

    /**
     * Calculate category-wise expense breakdown with amounts and percentages.
     */
    protected function calculateCategoryBreakdown(User $user, string $startDate, string $endDate, ?int $categoryId = null): Collection
    {
        $query = DB::table('transactions')
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->where('transactions.user_id', $user->id)
            ->where('transactions.type', 'expense')
            ->whereBetween('transactions.transaction_date', [$startDate, $endDate]);

        if ($categoryId) {
            $query->where('transactions.category_id', $categoryId);
        }

        $rawBreakdown = $query->select(
            'categories.id as category_id',
            'categories.name as category_name',
            'categories.icon as category_icon',
            DB::raw('SUM(transactions.amount) as total_amount'),
            DB::raw('COUNT(transactions.id) as transaction_count')
        )
        ->groupBy('categories.id', 'categories.name', 'categories.icon')
        ->orderByDesc('total_amount')
        ->get();

        $totalExpenses = $rawBreakdown->sum('total_amount');

        // Color palette for charts
        $defaultColors = [
            '#6366f1', '#10b981', '#f59e0b', '#ef4444',
            '#8b5cf6', '#06b6d4', '#ec4899', '#3b82f6',
            '#14b8a6', '#f97316', '#a855f7', '#64748b'
        ];

        return $rawBreakdown->map(function ($item, $index) use ($totalExpenses, $defaultColors) {
            $amount = (float) $item->total_amount;
            $percent = $totalExpenses > 0 ? round(($amount / $totalExpenses) * 100, 1) : 0.0;
            $color = $defaultColors[$index % count($defaultColors)];

            return [
                'category_id' => $item->category_id,
                'name' => $item->category_name,
                'icon' => $item->category_icon ?? 'bi-tag',
                'color' => $color,
                'amount' => $amount,
                'percentage' => $percent,
                'transaction_count' => (int) $item->transaction_count,
            ];
        });
    }

    /**
     * Calculate consecutive 6-month financial trend (Income vs Expense).
     * Includes all 6 months even if zero transactions exist.
     */
    protected function calculateSixMonthTrend(User $user, string $selectedMonth): array
    {
        try {
            $baseDate = Carbon::createFromFormat('Y-m', $selectedMonth);
        } catch (\Exception $e) {
            $baseDate = Carbon::now();
        }

        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = $baseDate->copy()->subMonths($i);
            $months[] = [
                'key' => $m->format('Y-m'),
                'short_label' => $m->format('M'),
                'full_label' => $m->format('F Y'),
                'year' => $m->format('Y'),
                'start_date' => $m->copy()->startOfMonth()->toDateString(),
                'end_date' => $m->copy()->endOfMonth()->toDateString(),
            ];
        }

        $rangeStart = $months[0]['start_date'];
        $rangeEnd = $months[5]['end_date'];

        $driver = DB::connection()->getDriverName();
        $monthSql = $driver === 'sqlite' 
            ? "strftime('%Y-%m', transaction_date)" 
            : "DATE_FORMAT(transaction_date, '%Y-%m')";

        // Aggregate income by month
        $incomeByMonth = DB::table('transactions')
            ->where('user_id', $user->id)
            ->where('type', 'income')
            ->whereBetween('transaction_date', [$rangeStart, $rangeEnd])
            ->select(DB::raw("{$monthSql} as m_key"), DB::raw('SUM(amount) as total'))
            ->groupBy('m_key')
            ->pluck('total', 'm_key')
            ->toArray();

        // Aggregate expenses by month
        $expenseByMonth = DB::table('transactions')
            ->where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$rangeStart, $rangeEnd])
            ->select(DB::raw("{$monthSql} as m_key"), DB::raw('SUM(amount) as total'))
            ->groupBy('m_key')
            ->pluck('total', 'm_key')
            ->toArray();

        $labels = [];
        $incomeData = [];
        $expenseData = [];
        $balanceData = [];
        $detailedSeries = [];

        foreach ($months as $m) {
            $inc = (float) ($incomeByMonth[$m['key']] ?? 0.0);
            $exp = (float) ($expenseByMonth[$m['key']] ?? 0.0);
            $bal = $inc - $exp;

            $labels[] = $m['short_label'];
            $incomeData[] = $inc;
            $expenseData[] = $exp;
            $balanceData[] = $bal;

            $detailedSeries[] = [
                'month_key' => $m['key'],
                'label' => $m['short_label'],
                'full_label' => $m['full_label'],
                'income' => $inc,
                'expense' => $exp,
                'balance' => $bal,
                'is_current' => ($m['key'] === Carbon::now()->format('Y-m')),
            ];
        }

        $avgIncome = count($incomeData) > 0 ? round(array_sum($incomeData) / count($incomeData), 2) : 0.0;
        $avgExpense = count($expenseData) > 0 ? round(array_sum($expenseData) / count($expenseData), 2) : 0.0;
        $avgSavings = $avgIncome - $avgExpense;

        return [
            'labels' => $labels,
            'income' => $incomeData,
            'expense' => $expenseData,
            'balance' => $balanceData,
            'detailed' => $detailedSeries,
            'avg_income' => $avgIncome,
            'avg_expense' => $avgExpense,
            'avg_savings' => $avgSavings,
        ];
    }

    /**
     * Calculate daily spending summary for the active period.
     */
    protected function calculateDailySpending(User $user, string $startDate, string $endDate, ?int $categoryId = null): array
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $driver = DB::connection()->getDriverName();
        $daySql = $driver === 'sqlite' 
            ? "strftime('%Y-%m-%d', transaction_date)" 
            : "DATE_FORMAT(transaction_date, '%Y-%m-%d')";

        // Fetch daily expense aggregates
        $dailyExpensesQuery = DB::table('transactions')
            ->where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$startDate, $endDate]);

        if ($categoryId) {
            $dailyExpensesQuery->where('category_id', $categoryId);
        }

        $dailyExpenses = $dailyExpensesQuery->select(
            DB::raw("{$daySql} as tx_day"),
            DB::raw('SUM(amount) as total')
        )
        ->groupBy('tx_day')
        ->pluck('total', 'tx_day')
        ->toArray();

        $labels = [];
        $data = [];
        $daysList = [];

        // Build array for each day in range
        $period = CarbonPeriod::create($start, $end);
        foreach ($period as $date) {
            $dayKey = $date->format('Y-m-d');
            $spent = (float) ($dailyExpenses[$dayKey] ?? 0.0);

            $label = $date->format('M d');
            $labels[] = $label;
            $data[] = $spent;

            $daysList[] = [
                'raw_date' => $dayKey,
                'label' => $label,
                'day_number' => $date->format('j'),
                'expense' => $spent,
            ];
        }

        return [
            'labels' => $labels,
            'data' => $data,
            'days' => $daysList,
        ];
    }

    /**
     * Calculate weekly spending summary for the month/range.
     */
    protected function calculateWeeklySpending(User $user, string $startDate, string $endDate, ?int $categoryId = null): array
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $weeks = [
            'Week 1' => ['label' => 'Week 1 (Days 1–7)', 'start_day' => 1, 'end_day' => 7, 'amount' => 0.0],
            'Week 2' => ['label' => 'Week 2 (Days 8–14)', 'start_day' => 8, 'end_day' => 14, 'amount' => 0.0],
            'Week 3' => ['label' => 'Week 3 (Days 15–21)', 'start_day' => 15, 'end_day' => 21, 'amount' => 0.0],
            'Week 4' => ['label' => 'Week 4 (Days 22–28)', 'start_day' => 22, 'end_day' => 28, 'amount' => 0.0],
            'Week 5' => ['label' => 'Week 5 (Days 29–31)', 'start_day' => 29, 'end_day' => 31, 'amount' => 0.0],
        ];

        // Retrieve transactions in period
        $txs = Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->when($categoryId, fn($q) => $q->where('category_id', $categoryId))
            ->get(['amount', 'transaction_date']);

        foreach ($txs as $tx) {
            $day = (int) Carbon::parse($tx->transaction_date)->format('j');
            if ($day <= 7) {
                $weeks['Week 1']['amount'] += (float) $tx->amount;
            } elseif ($day <= 14) {
                $weeks['Week 2']['amount'] += (float) $tx->amount;
            } elseif ($day <= 21) {
                $weeks['Week 3']['amount'] += (float) $tx->amount;
            } elseif ($day <= 28) {
                $weeks['Week 4']['amount'] += (float) $tx->amount;
            } else {
                $weeks['Week 5']['amount'] += (float) $tx->amount;
            }
        }

        $labels = [];
        $data = [];
        $detailed = [];

        foreach ($weeks as $key => $w) {
            $labels[] = $key;
            $data[] = round($w['amount'], 2);
            $detailed[] = [
                'week' => $key,
                'label' => $w['label'],
                'amount' => round($w['amount'], 2),
            ];
        }

        return [
            'labels' => $labels,
            'data' => $data,
            'detailed' => $detailed,
        ];
    }

    /**
     * Calculate month-over-month comparison against previous calendar month.
     */
    protected function calculateMonthOverMonth(User $user, string $selectedMonth, float $currentExpense, float $currentIncome): ?array
    {
        try {
            $curDate = Carbon::createFromFormat('Y-m', $selectedMonth);
        } catch (\Exception $e) {
            return null;
        }

        $prevDate = $curDate->copy()->subMonth();
        $prevStart = $prevDate->startOfMonth()->toDateString();
        $prevEnd = $prevDate->endOfMonth()->toDateString();

        $prevExpense = (float) Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$prevStart, $prevEnd])
            ->sum('amount');

        $prevIncome = (float) Transaction::where('user_id', $user->id)
            ->where('type', 'income')
            ->whereBetween('transaction_date', [$prevStart, $prevEnd])
            ->sum('amount');

        $hasPrevData = ($prevExpense > 0 || $prevIncome > 0);

        if (!$hasPrevData) {
            return [
                'has_data' => false,
                'message' => 'No previous-period data.',
                'prev_month_label' => $prevDate->format('F Y'),
                'prev_expense' => 0.0,
                'prev_income' => 0.0,
                'diff_expense' => 0.0,
                'diff_expense_percent' => 0.0,
            ];
        }

        $diffExpense = $currentExpense - $prevExpense;
        $diffPercent = $prevExpense > 0 ? round(($diffExpense / $prevExpense) * 100, 2) : 0.0;

        return [
            'has_data' => true,
            'prev_month_label' => $prevDate->format('F Y'),
            'prev_month_short' => $prevDate->format('M'),
            'prev_expense' => $prevExpense,
            'prev_income' => $prevIncome,
            'diff_expense' => $diffExpense,
            'diff_expense_abs' => abs($diffExpense),
            'diff_expense_percent' => $diffPercent,
            'is_lower' => ($diffExpense < 0),
        ];
    }

    /**
     * Integrate with Phase 4 Budgets for selected month.
     */
    protected function calculateBudgetComparison(User $user, string $selectedMonth): array
    {
        $budgets = Budget::with('category')
            ->where('user_id', $user->id)
            ->where('month', $selectedMonth)
            ->get();

        if ($budgets->isEmpty()) {
            return [
                'has_budgets' => false,
                'items' => [],
                'total_budgeted' => 0.0,
                'total_spent' => 0.0,
                'near_limit_count' => 0,
                'over_budget_count' => 0,
            ];
        }

        $items = [];
        $totalBudgeted = 0.0;
        $totalSpent = 0.0;
        $nearLimitCount = 0;
        $overBudgetCount = 0;

        foreach ($budgets as $b) {
            $limit = (float) $b->limit_amount;
            $spent = $b->getActualSpending();
            $percent = $b->getUsagePercentage($spent);
            $status = $b->getStatus($spent);

            $totalBudgeted += $limit;
            $totalSpent += $spent;

            if ($status === Budget::STATUS_NEAR_LIMIT) {
                $nearLimitCount++;
            } elseif ($status === Budget::STATUS_OVER_BUDGET) {
                $overBudgetCount++;
            }

            $items[] = [
                'id' => $b->id,
                'category_name' => $b->category->name ?? 'Uncategorized',
                'category_icon' => $b->category->icon ?? 'bi-tag',
                'limit_amount' => $limit,
                'spent_amount' => $spent,
                'usage_percent' => $percent,
                'status' => $status,
                'remaining' => max(0, $limit - $spent),
            ];
        }

        return [
            'has_budgets' => true,
            'items' => $items,
            'total_budgeted' => $totalBudgeted,
            'total_spent' => $totalSpent,
            'near_limit_count' => $nearLimitCount,
            'over_budget_count' => $overBudgetCount,
        ];
    }

    /**
     * Generate deterministic report highlights from real calculations.
     */
    protected function generateDeterministicHighlights(
        ?array $topCategory,
        ?array $monthOverMonth,
        float $avgDaily,
        int $totalDays,
        float $savingsRate,
        float $netBalance,
        array $budgetComparison
    ): array {
        $highlights = [];

        // 1. Top Category Highlight
        if ($topCategory && $topCategory['amount'] > 0) {
            $highlights[] = [
                'icon' => 'bi-fire',
                'color' => 'var(--cc-primary)',
                'title' => 'Top Spending Category',
                'text' => "{$topCategory['name']} was your highest spending category at Rs. " . number_format($topCategory['amount'], 2) . " ({$topCategory['percentage']}% of total expenses).",
            ];
        }

        // 2. Month-over-Month Highlight
        if ($monthOverMonth && $monthOverMonth['has_data']) {
            if ($monthOverMonth['diff_expense'] < 0) {
                $highlights[] = [
                    'icon' => 'bi-graph-down-arrow',
                    'color' => 'var(--cc-income)',
                    'title' => 'Monthly Expense Drop',
                    'text' => "Expenses were Rs. " . number_format($monthOverMonth['diff_expense_abs'], 2) . " lower (" . abs($monthOverMonth['diff_expense_percent']) . "%) than {$monthOverMonth['prev_month_label']}.",
                ];
            } elseif ($monthOverMonth['diff_expense'] > 0) {
                $highlights[] = [
                    'icon' => 'bi-graph-up-arrow',
                    'color' => 'var(--cc-expense)',
                    'title' => 'Monthly Expense Rise',
                    'text' => "Expenses were Rs. " . number_format($monthOverMonth['diff_expense_abs'], 2) . " higher (+" . $monthOverMonth['diff_expense_percent'] . "%) than {$monthOverMonth['prev_month_label']}.",
                ];
            }
        }

        // 3. Average Daily Spending Highlight
        if ($avgDaily > 0) {
            $highlights[] = [
                'icon' => 'bi-calendar-day',
                'color' => 'var(--cc-accent-teal)',
                'title' => 'Daily Spending Pace',
                'text' => "Your average daily spending was Rs. " . number_format($avgDaily, 2) . " per day across {$totalDays} calendar days.",
            ];
        }

        // 4. Net Savings Health
        if ($netBalance > 0) {
            $highlights[] = [
                'icon' => 'bi-piggy-bank',
                'color' => 'var(--cc-income)',
                'title' => 'Positive Net Savings',
                'text' => "You achieved a {$savingsRate}% net savings rate with a surplus of Rs. " . number_format($netBalance, 2) . ".",
            ];
        } elseif ($netBalance < 0) {
            $highlights[] = [
                'icon' => 'bi-exclamation-triangle',
                'color' => 'var(--cc-expense)',
                'title' => 'Deficit Alert',
                'text' => "Expenses exceeded recorded income by Rs. " . number_format(abs($netBalance), 2) . " for this period.",
            ];
        }

        // 5. Budget Health Context
        if ($budgetComparison['has_budgets']) {
            if ($budgetComparison['over_budget_count'] > 0) {
                $highlights[] = [
                    'icon' => 'bi-shield-exclamation',
                    'color' => 'var(--cc-expense)',
                    'title' => 'Budget Limit Exceeded',
                    'text' => "{$budgetComparison['over_budget_count']} active budget categories have exceeded their limit.",
                ];
            } elseif ($budgetComparison['near_limit_count'] > 0) {
                $highlights[] = [
                    'icon' => 'bi-shield-shaded',
                    'color' => 'var(--cc-warning)',
                    'title' => 'Budget Warning',
                    'text' => "{$budgetComparison['near_limit_count']} categories are currently near (>=75%) their spending limit.",
                ];
            }
        }

        return $highlights;
    }

    /**
     * Get list of last 12 calendar months for selector.
     */
    protected function getAvailableMonths(): array
    {
        $months = [];
        $current = Carbon::now();
        for ($i = 0; $i < 12; $i++) {
            $m = $current->copy()->subMonths($i);
            $months[] = [
                'value' => $m->format('Y-m'),
                'label' => $m->format('F Y'),
                'short' => $m->format('M Y'),
            ];
        }
        return $months;
    }
}
