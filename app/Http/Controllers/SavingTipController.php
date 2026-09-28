<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\Category;
use App\Models\SavingTip;
use App\Models\Transaction;
use App\Services\SavingTipService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SavingTipController extends Controller
{
    /**
     * Display personalized saving tips for the authenticated student.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        // 1. Month Handling
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
        $prevMonthKey = $selectedDate->copy()->subMonth()->format('Y-m');
        $nextMonthKey = $selectedDate->copy()->addMonth()->format('Y-m');

        // 2. Generate or refresh deterministic tips for this user and month
        SavingTipService::generateTipsForUser($user, $currentMonthKey);

        // 3. Category & Status Filters
        $categoryFilter = $request->query('category', 'all');
        $statusFilter = $request->query('status', 'all');

        // 4. Fetch all user tips for this month
        $tipsQuery = SavingTip::with('category')
            ->where('user_id', $user->id)
            ->where('reference_month', $currentMonthKey);

        if ($categoryFilter !== 'all' && is_numeric($categoryFilter)) {
            $tipsQuery->where('category_id', (int) $categoryFilter);
        }

        $allTips = $tipsQuery->ranked()->get();

        $pinnedTips = $allTips->filter(fn ($t) => $t->is_pinned);
        $activeTips = $allTips->filter(fn ($t) => is_null($t->dismissed_at));
        $recommendedTips = $allTips->filter(fn ($t) => is_null($t->dismissed_at) && !$t->is_pinned);
        $dismissedTips = $allTips->filter(fn ($t) => !is_null($t->dismissed_at));

        // 5. Calculate Metrics
        $totalPotentialSavings = SavingTipService::getTotalPotentialSavings($user, $currentMonthKey);
        $hasSufficientHistory = SavingTipService::hasSufficientHistory($user, $currentMonthKey);
        $totalTransactionsCount = Transaction::where('user_id', $user->id)->count();

        $appliedCount = $pinnedTips->count();
        $totalActiveCount = $activeTips->count();
        $appliedPercentage = $totalActiveCount > 0 ? min(100, round(($appliedCount / $totalActiveCount) * 100)) : 0;

        // 6. User categories for filtering
        $userCategories = Category::forUser($user->id)->orderBy('name')->get();

        // 7. Unread Notifications Count for Topbar
        $unreadNotificationsCount = AppNotification::forUser($user->id)->unread()->count();
        $recentNotifications = AppNotification::forUser($user->id)
            ->orderBy('created_at', 'desc')
            ->take(6)
            ->get();

        return view('saving-tips', compact(
            'user',
            'currentMonthKey',
            'monthLabel',
            'prevMonthKey',
            'nextMonthKey',
            'allTips',
            'pinnedTips',
            'activeTips',
            'recommendedTips',
            'dismissedTips',
            'totalPotentialSavings',
            'hasSufficientHistory',
            'totalTransactionsCount',
            'appliedCount',
            'totalActiveCount',
            'appliedPercentage',
            'userCategories',
            'categoryFilter',
            'statusFilter',
            'unreadNotificationsCount',
            'recentNotifications'
        ));
    }

    /**
     * Pin a saving tip.
     */
    public function pin(Request $request, SavingTip $savingTip): JsonResponse|RedirectResponse
    {
        if ($savingTip->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this saving tip.');
        }

        $savingTip->pin();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Tip pinned successfully.',
                'is_pinned' => true,
            ]);
        }

        return back()->with('success', 'Tip pinned to your saved tips.');
    }

    /**
     * Unpin a saving tip.
     */
    public function unpin(Request $request, SavingTip $savingTip): JsonResponse|RedirectResponse
    {
        if ($savingTip->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this saving tip.');
        }

        $savingTip->unpin();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Tip unpinned successfully.',
                'is_pinned' => false,
            ]);
        }

        return back()->with('success', 'Tip unpinned.');
    }

    /**
     * Dismiss a saving tip.
     */
    public function dismiss(Request $request, SavingTip $savingTip): JsonResponse|RedirectResponse
    {
        if ($savingTip->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this saving tip.');
        }

        $savingTip->dismiss();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Tip dismissed.',
                'dismissed' => true,
            ]);
        }

        return back()->with('success', 'Tip dismissed.');
    }

    /**
     * Restore a dismissed saving tip.
     */
    public function restore(Request $request, SavingTip $savingTip): JsonResponse|RedirectResponse
    {
        if ($savingTip->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this saving tip.');
        }

        $savingTip->restore();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Tip restored.',
                'restored' => true,
            ]);
        }

        return back()->with('success', 'Tip restored to active tips.');
    }

    /**
     * Force regenerate saving tips for the authenticated student.
     */
    public function regenerate(Request $request): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        $month = $request->input('month', Carbon::now()->format('Y-m'));

        SavingTipService::generateTipsForUser($user, $month);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Saving tips regenerated from your latest data.',
            ]);
        }

        return back()->with('success', 'Saving tips updated based on your latest financial activity.');
    }
}
