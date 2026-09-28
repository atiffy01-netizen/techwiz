<?php

namespace App\Http\Controllers;

use App\Models\AiMonthlyInsight;
use App\Models\AppNotification;
use App\Models\Transaction;
use App\Services\Ai\AiInsightService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class InsightController extends Controller
{
    /**
     * Display the AI Monthly Spending Insights page.
     */
    public function index(Request $request, ?string $month = null): View
    {
        $user = Auth::user();

        // 1. Month Handling
        $monthParam = $month ?: $request->query('month');
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
        $prevMonthKey = $selectedDate->copy()->subMonth()->format('Y-m');
        $nextMonthKey = $selectedDate->copy()->addMonth()->format('Y-m');

        // 2. Fetch or Generate Insight for Selected Month
        $currentInsight = AiInsightService::getOrCreateInsight($user, $currentMonthKey);

        // 3. Past Stored Monthly Insights
        $pastInsights = AiInsightService::getPastInsights($user, $currentMonthKey, 6);

        // 4. Financial Context Metrics for Selected Month
        $startOfMonth = $selectedDate->copy()->startOfMonth()->toDateString();
        $endOfMonth = $selectedDate->copy()->endOfMonth()->toDateString();

        $monthlyIncome = (float) Transaction::where('user_id', $user->id)
            ->where('type', 'income')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $monthlyExpense = (float) Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $monthlyNetSavings = $monthlyIncome - $monthlyExpense;
        $savingsRate = $monthlyIncome > 0 ? round(($monthlyNetSavings / $monthlyIncome) * 100, 1) : 0.0;
        $totalTxnsCount = Transaction::where('user_id', $user->id)
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->count();

        // 5. Unread Notifications Count for Topbar
        $unreadNotificationsCount = AppNotification::forUser($user->id)->unread()->count();
        $recentNotifications = AppNotification::forUser($user->id)
            ->orderBy('created_at', 'desc')
            ->take(6)
            ->get();

        return view('insights', compact(
            'user',
            'currentMonthKey',
            'monthLabel',
            'prevMonthKey',
            'nextMonthKey',
            'currentInsight',
            'pastInsights',
            'monthlyIncome',
            'monthlyExpense',
            'monthlyNetSavings',
            'savingsRate',
            'totalTxnsCount',
            'unreadNotificationsCount',
            'recentNotifications'
        ));
    }

    /**
     * Regenerate AI Monthly Insight for a user.
     */
    public function generate(Request $request): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        $month = $request->input('month', Carbon::now()->format('Y-m'));

        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = Carbon::now()->format('Y-m');
        }

        $insight = AiInsightService::generateInsight($user, $month, true);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Monthly insight regenerated successfully.',
                'insight' => $insight,
            ]);
        }

        return redirect()->route('insights', ['month' => $month])
            ->with('success', 'Monthly spending insight refreshed from your latest transactions.');
    }
}
