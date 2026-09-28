<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Services\BudgetAlertService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BudgetController extends Controller
{
    /**
     * Display the authenticated user's budgets for a selected month.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        // Month Selection (Default to current month 'YYYY-MM')
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

        $startOfMonth = $selectedDate->copy()->startOfMonth()->toDateString();
        $endOfMonth = $selectedDate->copy()->endOfMonth()->toDateString();

        // Check & update alerts for this month
        BudgetAlertService::checkUserBudgets($user, $currentMonthKey);

        // Fetch User Budgets for selected month
        $budgets = Budget::with('category')
            ->where('user_id', $user->id)
            ->where('month', $currentMonthKey)
            ->orderBy('created_at', 'asc')
            ->get();

        // Fetch all expense transactions for the user in this month grouped by category
        $categoryExpenses = Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->selectRaw('category_id, SUM(amount) as total_spent')
            ->groupBy('category_id')
            ->pluck('total_spent', 'category_id')
            ->toArray();

        // Calculate progress and status for each budget
        $totalBudgetLimit = 0.0;
        $totalBudgetSpent = 0.0;
        $nearLimitCount = 0;
        $overBudgetCount = 0;
        $onTrackCount = 0;
        $noSpendingCount = 0;

        $budgetItems = $budgets->map(function ($budget) use (
            $categoryExpenses,
            &$totalBudgetLimit,
            &$totalBudgetSpent,
            &$nearLimitCount,
            &$overBudgetCount,
            &$onTrackCount,
            &$noSpendingCount
        ) {
            $limit = (float) $budget->limit_amount;
            $spent = (float) ($categoryExpenses[$budget->category_id] ?? 0.0);
            $usagePercent = $limit > 0 ? round(($spent / $limit) * 100, 1) : 0.0;
            $remaining = $limit - $spent;
            $overage = $spent > $limit ? $spent - $limit : 0.0;

            if ($spent <= 0) {
                $status = Budget::STATUS_NO_SPENDING;
                $statusLabel = 'No spending yet';
                $statusBadgeClass = 'cc-badge-secondary';
                $noSpendingCount++;
            } elseif ($usagePercent >= Budget::THRESHOLD_OVER_BUDGET) {
                $status = Budget::STATUS_OVER_BUDGET;
                $statusLabel = 'Over Budget (' . $usagePercent . '%)';
                $statusBadgeClass = 'cc-badge-expense';
                $overBudgetCount++;
            } elseif ($usagePercent >= Budget::THRESHOLD_NEAR_LIMIT) {
                $status = Budget::STATUS_NEAR_LIMIT;
                $statusLabel = 'Near Limit (' . $usagePercent . '%)';
                $statusBadgeClass = 'cc-badge-warning';
                $nearLimitCount++;
            } else {
                $status = Budget::STATUS_ON_TRACK;
                $statusLabel = 'On Track (' . $usagePercent . '%)';
                $statusBadgeClass = 'cc-badge-income';
                $onTrackCount++;
            }

            $totalBudgetLimit += $limit;
            $totalBudgetSpent += $spent;

            return (object) [
                'id' => $budget->id,
                'category_id' => $budget->category_id,
                'category_name' => $budget->category ? $budget->category->name : 'Uncategorized',
                'category_icon' => $budget->category ? $budget->category->icon : 'bi-tag',
                'limit_amount' => $limit,
                'spent_amount' => $spent,
                'remaining_amount' => $remaining,
                'overage_amount' => $overage,
                'usage_percentage' => $usagePercent,
                'display_percentage' => min(100, $usagePercent),
                'status' => $status,
                'status_label' => $statusLabel,
                'status_badge_class' => $statusBadgeClass,
                'created_at' => $budget->created_at,
            ];
        });

        $totalRemaining = max(0, $totalBudgetLimit - $totalBudgetSpent);
        $overallUsagePercent = $totalBudgetLimit > 0 ? min(100, round(($totalBudgetSpent / $totalBudgetLimit) * 100, 1)) : 0.0;
        $rawOverallUsagePercent = $totalBudgetLimit > 0 ? round(($totalBudgetSpent / $totalBudgetLimit) * 100, 1) : 0.0;

        // Available expense categories for Add/Edit budget dropdown
        $availableCategories = Category::forUser($user->id)
            ->where('type', 'expense')
            ->orderBy('name')
            ->get();

        $existingCategoryIds = $budgets->pluck('category_id')->toArray();

        return view('budgets', compact(
            'user',
            'currentMonthKey',
            'monthLabel',
            'prevMonthKey',
            'nextMonthKey',
            'budgetItems',
            'totalBudgetLimit',
            'totalBudgetSpent',
            'totalRemaining',
            'overallUsagePercent',
            'rawOverallUsagePercent',
            'nearLimitCount',
            'overBudgetCount',
            'onTrackCount',
            'noSpendingCount',
            'availableCategories',
            'existingCategoryIds'
        ));
    }

    /**
     * Store a newly created budget or update existing if same user, category, and month.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where(function ($query) use ($user) {
                    $query->where('type', 'expense')
                          ->where(function ($sub) use ($user) {
                              $sub->where('is_default', true)
                                  ->orWhere('user_id', $user->id);
                          });
                }),
            ],
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'limit_amount' => ['required', 'numeric', 'min:1'],
        ], [
            'category_id.exists' => 'The selected category is invalid or is an income category. Budgets are for expense categories only.',
            'month.regex' => 'The month must be in YYYY-MM format.',
            'limit_amount.min' => 'The budget limit must be at least Rs. 1.',
        ]);

        $budget = Budget::updateOrCreate(
            [
                'user_id' => $user->id,
                'category_id' => $validated['category_id'],
                'month' => $validated['month'],
            ],
            [
                'limit_amount' => $validated['limit_amount'],
            ]
        );

        // Check alerts and saving tips
        BudgetAlertService::checkUserBudgets($user, $validated['month']);
        \App\Services\SavingTipService::generateTipsForUser($user, $validated['month']);

        return redirect()->route('budgets', ['month' => $validated['month']])
            ->with('success', 'Budget set successfully for ' . Carbon::createFromFormat('Y-m', $validated['month'])->format('F Y') . '!');
    }

    /**
     * Update the specified budget limit amount.
     */
    public function update(Request $request, Budget $budget): RedirectResponse
    {
        // Authorization check: User can only update their own budgets
        if ($budget->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this budget.');
        }

        $validated = $request->validate([
            'limit_amount' => ['required', 'numeric', 'min:1'],
        ], [
            'limit_amount.min' => 'The budget limit must be at least Rs. 1.',
        ]);

        $budget->update([
            'limit_amount' => $validated['limit_amount'],
        ]);

        // Check alerts and saving tips after updating limit
        BudgetAlertService::checkUserBudgets(Auth::user(), $budget->month);
        \App\Services\SavingTipService::generateTipsForUser(Auth::user(), $budget->month);

        return redirect()->route('budgets', ['month' => $budget->month])
            ->with('success', 'Budget updated successfully!');
    }

    /**
     * Remove the specified budget. (Transactions are preserved intact).
     */
    public function destroy(Budget $budget): RedirectResponse
    {
        // Authorization check: User can only delete their own budgets
        if ($budget->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this budget.');
        }

        $month = $budget->month;
        $budget->delete();

        \App\Services\SavingTipService::generateTipsForUser(Auth::user(), $month);

        return redirect()->route('budgets', ['month' => $month])
            ->with('success', 'Budget removed successfully. Your transactions remain untouched.');
    }
}
