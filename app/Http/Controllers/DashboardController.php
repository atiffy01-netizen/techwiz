<?php

namespace App\Http\Controllers;

use App\Models\AdminTipTemplate;
use App\Models\AiMonthlyInsight;
use App\Models\AppNotification;
use App\Models\Budget;
use App\Models\Category;
use App\Models\SavingTip;
use App\Models\Transaction;
use App\Services\Ai\AiInsightService;
use App\Services\BudgetAlertService;
use App\Services\SavingTipService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the authenticated user's real-time personalized dashboard.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        // 1. Month Selection (Default to current month 'YYYY-MM')
        $monthParam = $request->query('month');
        if ($monthParam && preg_match('/^\d{4}-\d{2}$/', $monthParam)) {
            try {
                $selectedDate = Carbon::createFromFormat('Y-m', $monthParam);
            } catch (\Exception $e) {
                $selectedDate = Carbon::now();
            }
        } else {
            $selectedDate = Carbon::now();
        }

        $currentMonthKey = $selectedDate->format('Y-m');
        $monthLabel = $selectedDate->format('F Y');
        $shortMonthName = $selectedDate->format('M');
        $prevMonthKey = $selectedDate->copy()->subMonth()->format('Y-m');
        $nextMonthKey = $selectedDate->copy()->addMonth()->format('Y-m');
        $isCurrentCalendarMonth = ($currentMonthKey === Carbon::now()->format('Y-m'));

        $startOfMonth = $selectedDate->copy()->startOfMonth()->toDateString();
        $endOfMonth = $selectedDate->copy()->endOfMonth()->toDateString();

        // 2. Personalized Greeting based on server time and real user name
        $hour = (int) date('H');
        if ($hour >= 5 && $hour < 12) {
            $greetingTime = 'morning';
        } elseif ($hour >= 12 && $hour < 17) {
            $greetingTime = 'afternoon';
        } else {
            $greetingTime = 'evening';
        }
        $greeting = "Good {$greetingTime}, {$user->name}";

        // 3. Real Lifetime Balances (User-isolated)
        $lifetimeIncome = (float) Transaction::where('user_id', $user->id)->where('type', 'income')->sum('amount');
        $lifetimeExpense = (float) Transaction::where('user_id', $user->id)->where('type', 'expense')->sum('amount');
        $availableBalance = $lifetimeIncome - $lifetimeExpense;
        $totalTransactionsCount = Transaction::where('user_id', $user->id)->count();

        // 4. Selected Month Summary (Inflow, Outflow, Net Savings)
        $monthlyIncome = (float) Transaction::where('user_id', $user->id)
            ->where('type', 'income')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $monthlyExpense = (float) Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $monthlyNetSavings = $monthlyIncome - $monthlyExpense;

        // 5. Month-over-Month Comparison (Real database calculation)
        $prevMonthDate = $selectedDate->copy()->subMonth();
        $prevStart = $prevMonthDate->startOfMonth()->toDateString();
        $prevEnd = $prevMonthDate->endOfMonth()->toDateString();
        $prevMonthLabel = $prevMonthDate->format('M');

        $prevIncome = (float) Transaction::where('user_id', $user->id)
            ->where('type', 'income')
            ->whereBetween('transaction_date', [$prevStart, $prevEnd])
            ->sum('amount');

        $prevExpense = (float) Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$prevStart, $prevEnd])
            ->sum('amount');

        // Income Change %
        $hasPrevIncomeData = ($prevIncome > 0);
        $incomeChangePercent = $hasPrevIncomeData ? round((($monthlyIncome - $prevIncome) / $prevIncome) * 100, 1) : null;

        // Expense Change %
        $hasPrevExpenseData = ($prevExpense > 0);
        $expenseChangePercent = $hasPrevExpenseData ? round((($monthlyExpense - $prevExpense) / $prevExpense) * 100, 1) : null;

        // 6. Category Spending Breakdown for Selected Month
        $categorySpendings = Transaction::where('transactions.user_id', $user->id)
            ->where('transactions.type', 'expense')
            ->whereBetween('transactions.transaction_date', [$startOfMonth, $endOfMonth])
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->select('categories.id', 'categories.name', 'categories.icon', DB::raw('SUM(transactions.amount) as cat_total'))
            ->groupBy('categories.id', 'categories.name', 'categories.icon')
            ->orderByDesc('cat_total')
            ->get()
            ->map(function ($row) use ($monthlyExpense) {
                $amount = (float) $row->cat_total;
                $percent = $monthlyExpense > 0 ? round(($amount / $monthlyExpense) * 100, 1) : 0.0;
                return (object) [
                    'id' => $row->id,
                    'name' => $row->name,
                    'icon' => $row->icon ?: 'bi-tag',
                    'amount' => $amount,
                    'percent' => $percent,
                ];
            });

        // 7. Top Spending Category for Selected Month
        $topCategory = $categorySpendings->first();
        $topCategoryName = $topCategory ? $topCategory->name : null;
        $topCategoryIcon = $topCategory ? $topCategory->icon : 'bi-tag';
        $topCategoryAmount = $topCategory ? $topCategory->amount : 0.0;
        $topCategoryPercent = $topCategory ? $topCategory->percent : 0.0;

        // 8. Check Alerts & Evaluate Budget Health
        BudgetAlertService::checkUserBudgets($user, $currentMonthKey);

        // Fetch User Budgets for Selected Month
        $budgets = Budget::with('category')
            ->where('user_id', $user->id)
            ->where('month', $currentMonthKey)
            ->get();

        $categoryExpensesMap = $categorySpendings->pluck('amount', 'id')->toArray();

        $totalBudgetLimit = 0.0;
        $totalBudgetSpent = 0.0;
        $budgetItems = $budgets->map(function ($b) use ($categoryExpensesMap, &$totalBudgetLimit, &$totalBudgetSpent) {
            $limit = (float) $b->limit_amount;
            $spent = (float) ($categoryExpensesMap[$b->category_id] ?? 0.0);
            $usage = $limit > 0 ? round(($spent / $limit) * 100, 1) : 0.0;
            $remaining = $limit - $spent;
            $overage = $spent > $limit ? $spent - $limit : 0.0;

            if ($spent <= 0) {
                $status = Budget::STATUS_NO_SPENDING;
                $statusLabel = 'No spending yet';
                $badgeClass = 'cc-badge-secondary';
                $color = 'var(--cc-text-muted)';
            } elseif ($usage >= Budget::THRESHOLD_OVER_BUDGET) {
                $status = Budget::STATUS_OVER_BUDGET;
                $statusLabel = 'Over Budget (' . $usage . '%)';
                $badgeClass = 'cc-badge-expense';
                $color = 'var(--cc-expense)';
            } elseif ($usage >= Budget::THRESHOLD_NEAR_LIMIT) {
                $status = Budget::STATUS_NEAR_LIMIT;
                $statusLabel = 'Near Limit (' . $usage . '%)';
                $badgeClass = 'cc-badge-warning';
                $color = 'var(--cc-warning)';
            } else {
                $status = Budget::STATUS_ON_TRACK;
                $statusLabel = 'On Track (' . $usage . '%)';
                $badgeClass = 'cc-badge-income';
                $color = 'var(--cc-primary)';
            }

            $totalBudgetLimit += $limit;
            $totalBudgetSpent += $spent;

            return (object) [
                'id' => $b->id,
                'category_name' => $b->category ? $b->category->name : 'Uncategorized',
                'category_icon' => $b->category ? $b->category->icon : 'bi-tag',
                'limit_amount' => $limit,
                'spent_amount' => $spent,
                'remaining_amount' => $remaining,
                'overage_amount' => $overage,
                'usage_percentage' => $usage,
                'display_percentage' => min(100, $usage),
                'status' => $status,
                'status_label' => $statusLabel,
                'status_badge_class' => $badgeClass,
                'color' => $color,
            ];
        });

        $totalBudgetRemaining = max(0, $totalBudgetLimit - $totalBudgetSpent);
        $overallBudgetUsagePercent = $totalBudgetLimit > 0 ? min(100, round(($totalBudgetSpent / $totalBudgetLimit) * 100, 1)) : 0.0;
        $rawOverallBudgetUsagePercent = $totalBudgetLimit > 0 ? round(($totalBudgetSpent / $totalBudgetLimit) * 100, 1) : 0.0;

        // Baseline comparison for Ring Gauge
        $baselineBudget = $totalBudgetLimit > 0
            ? $totalBudgetLimit
            : ($user->monthly_allowance > 0 ? (float) $user->monthly_allowance : ($monthlyIncome > 0 ? $monthlyIncome : 40000.0));
        
        $gaugePercent = $baselineBudget > 0 ? min(100, round(($monthlyExpense / $baselineBudget) * 100)) : 0;
        $gaugeRemaining = max(0, $baselineBudget - $monthlyExpense);

        // 9. Spending Trajectory Chart for Last 5 Months (Actual Database Data)
        $trajectory = [];
        $maxTrajectoryVal = 1;
        for ($i = 4; $i >= 0; $i--) {
            $mDate = $selectedDate->copy()->subMonths($i);
            $mStart = $mDate->copy()->startOfMonth()->toDateString();
            $mEnd = $mDate->copy()->endOfMonth()->toDateString();
            $mLabel = $mDate->format('M');

            $mIncome = (float) Transaction::where('user_id', $user->id)
                ->where('type', 'income')
                ->whereBetween('transaction_date', [$mStart, $mEnd])
                ->sum('amount');

            $mExpense = (float) Transaction::where('user_id', $user->id)
                ->where('type', 'expense')
                ->whereBetween('transaction_date', [$mStart, $mEnd])
                ->sum('amount');

            if ($mIncome > $maxTrajectoryVal) $maxTrajectoryVal = $mIncome;
            if ($mExpense > $maxTrajectoryVal) $maxTrajectoryVal = $mExpense;

            $trajectory[] = [
                'label' => $mLabel,
                'is_current' => ($i === 0),
                'income' => $mIncome,
                'expense' => $mExpense,
            ];
        }

        foreach ($trajectory as &$mItem) {
            $mItem['income_height'] = $maxTrajectoryVal > 0 ? max(4, round(($mItem['income'] / $maxTrajectoryVal) * 150)) : 4;
            $mItem['expense_height'] = $maxTrajectoryVal > 0 ? max(4, round(($mItem['expense'] / $maxTrajectoryVal) * 150)) : 4;
        }

        // 10. Recent Transactions for User
        $recentTransactions = Transaction::with('category')
            ->where('user_id', $user->id)
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->take(5)
            ->get();

        // 11. Personalized Saving Tips Engine (Phase 6)
        SavingTipService::generateTipsForUser($user, $currentMonthKey);
        $topSavingTips = SavingTip::with('category')
            ->where('user_id', $user->id)
            ->where('reference_month', $currentMonthKey)
            ->active()
            ->ranked()
            ->take(3)
            ->get();
        $totalSavingsPotential = SavingTipService::getTotalPotentialSavings($user, $currentMonthKey);
        $hasSufficientHistory = SavingTipService::hasSufficientHistory($user, $currentMonthKey);

        // 12. AI Monthly Spending Insight (Phase 7)
        $monthlyInsight = AiMonthlyInsight::where('user_id', $user->id)
            ->where('month', $currentMonthKey)
            ->first();

        // 13. Unread Notifications Count & Recent Alerts for Topbar
        $unreadNotificationsCount = AppNotification::forUser($user->id)->unread()->count();
        $recentNotifications = AppNotification::forUser($user->id)
            ->orderBy('created_at', 'desc')
            ->take(6)
            ->get();

        // 14. System Announcements from Admin (Phase 8)
        $activeAnnouncements = AdminTipTemplate::active()
            ->where('type', 'announcement')
            ->latest()
            ->take(2)
            ->get();

        // 15. Upcoming Month Spending Forecast & Recent Activity (Phase 9)
        $forecast = \App\Services\SpendingForecastService::generateForecast($user, $currentMonthKey);
        $recentActivities = \App\Services\TransactionActivityService::getRecentActivities($user, 4);

        return view('dashboard', compact(
            'user',
            'greeting',
            'currentMonthKey',
            'monthLabel',
            'shortMonthName',
            'prevMonthKey',
            'nextMonthKey',
            'isCurrentCalendarMonth',
            'availableBalance',
            'lifetimeIncome',
            'lifetimeExpense',
            'monthlyIncome',
            'monthlyExpense',
            'monthlyNetSavings',
            'totalTransactionsCount',
            'hasPrevIncomeData',
            'incomeChangePercent',
            'hasPrevExpenseData',
            'expenseChangePercent',
            'prevMonthLabel',
            'categorySpendings',
            'topCategoryName',
            'topCategoryIcon',
            'topCategoryAmount',
            'topCategoryPercent',
            'budgets',
            'budgetItems',
            'totalBudgetLimit',
            'totalBudgetSpent',
            'totalBudgetRemaining',
            'overallBudgetUsagePercent',
            'rawOverallBudgetUsagePercent',
            'baselineBudget',
            'gaugePercent',
            'gaugeRemaining',
            'trajectory',
            'recentTransactions',
            'topSavingTips',
            'totalSavingsPotential',
            'hasSufficientHistory',
            'monthlyInsight',
            'unreadNotificationsCount',
            'recentNotifications',
            'activeAnnouncements',
            'forecast',
            'recentActivities'
        ));
    }
}
