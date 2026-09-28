<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiMonthlyInsight;
use App\Models\Budget;
use App\Models\Category;
use App\Models\SavingTip;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminStatisticsController extends Controller
{
    /**
     * Display comprehensive system-wide financial and usage analytics.
     */
    public function index(Request $request): View
    {
        $timeframe = $request->query('timeframe', 'last_6_months');
        $customStart = $request->query('start_date');
        $customEnd = $request->query('end_date');

        $now = Carbon::now();
        $startDate = match ($timeframe) {
            'current_month' => $now->copy()->startOfMonth(),
            'last_3_months' => $now->copy()->subMonths(2)->startOfMonth(),
            'year_to_date'  => $now->copy()->startOfYear(),
            'all_time'      => Carbon::create(2020, 1, 1),
            'custom'        => (!empty($customStart) && strtotime($customStart)) ? Carbon::parse($customStart)->startOfDay() : $now->copy()->subMonths(5)->startOfMonth(),
            default         => $now->copy()->subMonths(5)->startOfMonth(), // last_6_months
        };

        $endDate = match ($timeframe) {
            'custom' => (!empty($customEnd) && strtotime($customEnd)) ? Carbon::parse($customEnd)->endOfDay() : $now->copy()->endOfMonth(),
            default  => $now->copy()->endOfMonth(),
        };

        if ($startDate->gt($endDate)) {
            $temp = $startDate;
            $startDate = $endDate->copy()->startOfMonth();
            $endDate = $temp->copy()->endOfMonth();
        }

        // 1. User Metrics
        $totalUsers = User::count();
        $newUsersInPeriod = User::whereBetween('created_at', [$startDate, $endDate])->count();
        $activeUsers = User::where('is_active', true)->count();
        $inactiveUsers = $totalUsers - $activeUsers;

        // 2. Transaction Metrics in Period
        $txQuery = Transaction::whereBetween('transaction_date', [$startDate->toDateString(), $endDate->toDateString()]);

        $totalTransactionsCount = (clone $txQuery)->count();
        $incomeCount = (clone $txQuery)->where('type', 'income')->count();
        $expenseCount = (clone $txQuery)->where('type', 'expense')->count();

        $totalIncome = (float) (clone $txQuery)->where('type', 'income')->sum('amount');
        $totalExpense = (float) (clone $txQuery)->where('type', 'expense')->sum('amount');
        $netBalance = $totalIncome - $totalExpense;

        // 3. 6-Month Trend Data for Charts
        $trendMonths = [];
        $trendIncome = [];
        $trendExpense = [];
        $trendUserGrowth = [];

        for ($i = 5; $i >= 0; $i--) {
            $mStart = $now->copy()->subMonths($i)->startOfMonth();
            $mEnd = $now->copy()->subMonths($i)->endOfMonth();
            $mLabel = $mStart->format('M Y');
            $trendMonths[] = $mLabel;

            $mIncome = (float) Transaction::whereBetween('transaction_date', [$mStart->toDateString(), $mEnd->toDateString()])
                ->where('type', 'income')
                ->sum('amount');
            $mExpense = (float) Transaction::whereBetween('transaction_date', [$mStart->toDateString(), $mEnd->toDateString()])
                ->where('type', 'expense')
                ->sum('amount');

            $mUsers = User::whereBetween('created_at', [$mStart, $mEnd])->count();

            $trendIncome[] = round($mIncome, 2);
            $trendExpense[] = round($mExpense, 2);
            $trendUserGrowth[] = $mUsers;
        }

        // 4. Category Spending Breakdown in Period
        $categoryBreakdown = Transaction::whereBetween('transaction_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->where('transactions.type', 'expense')
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->select('categories.name', 'categories.icon', DB::raw('SUM(transactions.amount) as total_amount'))
            ->groupBy('categories.name', 'categories.icon')
            ->orderByDesc('total_amount')
            ->take(6)
            ->get();

        $categoryChartLabels = [];
        $categoryChartData = [];
        $totalCatExpense = $totalExpense > 0 ? $totalExpense : 1;

        foreach ($categoryBreakdown as $cat) {
            $categoryChartLabels[] = $cat->name;
            $categoryChartData[] = round(($cat->total_amount / $totalCatExpense) * 100, 1);
        }

        // 5. Budgets Overview
        $totalBudgets = Budget::count();
        $avgBudgetLimit = (float) Budget::avg('limit_amount');

        // 6. Saving Tips Overview
        $totalSavingTips = SavingTip::count();
        $pinnedSavingTips = SavingTip::where('is_pinned', true)->count();
        $dismissedSavingTips = SavingTip::whereNotNull('dismissed_at')->count();

        // 7. AI Feature Insights Usage
        $totalAiInsights = AiMonthlyInsight::count();
        $aiGeneratedCount = AiMonthlyInsight::where('status', AiMonthlyInsight::STATUS_AI_GENERATED)->count();
        $aiFallbackCount = AiMonthlyInsight::where('status', AiMonthlyInsight::STATUS_FALLBACK)->count();

        return view('admin.statistics.index', compact(
            'timeframe',
            'startDate',
            'endDate',
            'customStart',
            'customEnd',
            'totalUsers',
            'newUsersInPeriod',
            'activeUsers',
            'inactiveUsers',
            'totalTransactionsCount',
            'incomeCount',
            'expenseCount',
            'totalIncome',
            'totalExpense',
            'netBalance',
            'trendMonths',
            'trendIncome',
            'trendExpense',
            'trendUserGrowth',
            'categoryBreakdown',
            'categoryChartLabels',
            'categoryChartData',
            'totalBudgets',
            'avgBudgetLimit',
            'totalSavingTips',
            'pinnedSavingTips',
            'dismissedSavingTips',
            'totalAiInsights',
            'aiGeneratedCount',
            'aiFallbackCount'
        ));
    }
}
