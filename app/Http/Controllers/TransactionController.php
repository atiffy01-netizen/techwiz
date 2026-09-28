<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    /**
     * Display a paginated listing of transactions with filters and search.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Transaction::with(['category', 'noteRecord', 'bookmarks'])->where('user_id', $user->id);

        // Search by description or category name
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhereHas('category', function ($catQ) use ($search) {
                      $catQ->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by Type
        if ($request->filled('type') && in_array($request->input('type'), ['income', 'expense'])) {
            $query->where('type', $request->input('type'));
        }

        // Filter by Category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        // Filter by Month (format: YYYY-MM)
        if ($request->filled('month')) {
            $month = $request->input('month');
            try {
                $date = Carbon::createFromFormat('Y-m', $month);
                $query->whereBetween('transaction_date', [
                    $date->startOfMonth()->toDateString(),
                    $date->endOfMonth()->toDateString(),
                ]);
            } catch (\Exception $e) {
                // Invalid month format, ignore
            }
        }

        // Filter by Date Range
        if ($request->filled('date_from')) {
            $query->where('transaction_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->where('transaction_date', '<=', $request->input('date_to'));
        }

        // Sorting
        $sort = $request->input('sort', 'newest');
        switch ($sort) {
            case 'oldest':
                $query->orderBy('transaction_date', 'asc')->orderBy('id', 'asc');
                break;
            case 'highest':
                $query->orderBy('amount', 'desc');
                break;
            case 'lowest':
                $query->orderBy('amount', 'asc');
                break;
            case 'newest':
            default:
                $query->orderBy('transaction_date', 'desc')->orderBy('id', 'desc');
                break;
        }

        // Paginate records
        $transactions = $query->paginate(10)->withQueryString();

        // Calculate summary metrics for the current view (or for current month)
        $currentMonthStart = Carbon::now()->startOfMonth()->toDateString();
        $currentMonthEnd = Carbon::now()->endOfMonth()->toDateString();

        $monthStats = Transaction::where('user_id', $user->id)
            ->whereBetween('transaction_date', [$currentMonthStart, $currentMonthEnd])
            ->select(
                DB::raw("SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income"),
                DB::raw("SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense"),
                DB::raw("COUNT(CASE WHEN type = 'income' THEN 1 END) as income_count"),
                DB::raw("COUNT(CASE WHEN type = 'expense' THEN 1 END) as expense_count")
            )
            ->first();

        $totalIncome = $monthStats ? (float) $monthStats->total_income : 0.0;
        $totalExpense = $monthStats ? (float) $monthStats->total_expense : 0.0;
        $netBalance = $totalIncome - $totalExpense;
        $incomeCount = $monthStats ? (int) $monthStats->income_count : 0;
        $expenseCount = $monthStats ? (int) $monthStats->expense_count : 0;

        // Categories available to current user for filter dropdown
        $categories = Category::forUser($user->id)
            ->orderBy('name', 'asc')
            ->get();

        // Available months list for filter dropdown
        $distinctMonths = Transaction::where('user_id', $user->id)
            ->select(DB::raw("DATE_FORMAT(transaction_date, '%Y-%m') as ym"))
            ->distinct()
            ->orderBy('ym', 'desc')
            ->pluck('ym');

        if ($distinctMonths->isEmpty()) {
            $distinctMonths = collect([Carbon::now()->format('Y-m')]);
        }

        $recentActivities = \App\Services\TransactionActivityService::getRecentActivities($user, 5);

        return view('transactions', compact(
            'user',
            'transactions',
            'totalIncome',
            'totalExpense',
            'netBalance',
            'incomeCount',
            'expenseCount',
            'categories',
            'distinctMonths',
            'recentActivities'
        ));
    }

    /**
     * Show the form for creating a new transaction.
     */
    public function create(Request $request)
    {
        $user = Auth::user();

        // Calculate available balance
        $totalInflow = (float) Transaction::where('user_id', $user->id)->where('type', 'income')->sum('amount');
        $totalOutflow = (float) Transaction::where('user_id', $user->id)->where('type', 'expense')->sum('amount');
        $currentBalance = $totalInflow - $totalOutflow;

        // Fetch categories available for user
        $incomeCategories = Category::forUser($user->id)->where('type', 'income')->orderBy('name')->get();
        $expenseCategories = Category::forUser($user->id)->where('type', 'expense')->orderBy('name')->get();

        $prefillType = $request->query('type', 'expense');
        if (!in_array($prefillType, ['income', 'expense'])) {
            $prefillType = 'expense';
        }

        return view('add-transaction', compact(
            'user',
            'currentBalance',
            'incomeCategories',
            'expenseCategories',
            'prefillType'
        ));
    }

    /**
     * Store a newly created transaction in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0.01',
            'category_id' => 'required|exists:categories,id',
            'description' => 'required|string|max:255',
            'transaction_date' => 'required|date',
            'payment_method' => 'nullable|string|max:50',
            'note' => 'nullable|string|max:1000',
            'is_recurring' => 'nullable|boolean',
            'recurring_frequency' => 'nullable|in:weekly,monthly',
            'recurring_start_date' => 'nullable|date',
            'recurring_end_date' => 'nullable|date|after_or_equal:recurring_start_date',
        ]);

        // Validate Category Ownership & Type Match
        $category = Category::forUser($user->id)->find($validated['category_id']);
        if (!$category) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Selected category is invalid or inaccessible.');
        }

        if ($category->type !== $validated['type']) {
            return redirect()->back()
                ->withInput()
                ->with('error', "Category '{$category->name}' is an {$category->type} category, but transaction type is {$validated['type']}.");
        }

        $isRecurring = $request->boolean('is_recurring');

        $createdTxn = Transaction::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => $validated['amount'],
            'type' => $validated['type'],
            'description' => trim($validated['description']),
            'transaction_date' => $validated['transaction_date'],
            'payment_method' => $validated['payment_method'] ?? 'Cash',
            'note' => $validated['note'] ? trim($validated['note']) : null,
            'is_recurring' => $isRecurring,
            'recurring_frequency' => $isRecurring ? ($validated['recurring_frequency'] ?? 'monthly') : null,
            'recurring_start_date' => $isRecurring ? ($validated['recurring_start_date'] ?? $validated['transaction_date']) : null,
            'recurring_end_date' => $isRecurring ? ($validated['recurring_end_date'] ?? null) : null,
        ]);

        // Evaluate budget alerts and saving tips for the transaction's month
        \App\Services\BudgetAlertService::checkUserBudgets($user, Carbon::parse($validated['transaction_date'])->format('Y-m'));
        \App\Services\SavingTipService::generateTipsForUser($user, Carbon::parse($validated['transaction_date'])->format('Y-m'));

        return redirect()->route('transactions')
            ->with('success', 'Transaction saved successfully!');
    }

    /**
     * Update the specified transaction in storage.
     */
    public function update(Request $request, Transaction $transaction)
    {
        $user = Auth::user();

        // Enforce strict ownership
        if ($transaction->user_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0.01',
            'category_id' => 'required|exists:categories,id',
            'description' => 'required|string|max:255',
            'transaction_date' => 'required|date',
            'payment_method' => 'nullable|string|max:50',
            'note' => 'nullable|string|max:1000',
            'is_recurring' => 'nullable|boolean',
            'recurring_frequency' => 'nullable|in:weekly,monthly',
            'recurring_start_date' => 'nullable|date',
            'recurring_end_date' => 'nullable|date|after_or_equal:recurring_start_date',
        ]);

        // Validate Category Ownership & Type Match
        $category = Category::forUser($user->id)->find($validated['category_id']);
        if (!$category) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Selected category is invalid or inaccessible.');
        }

        if ($category->type !== $validated['type']) {
            return redirect()->back()
                ->withInput()
                ->with('error', "Category '{$category->name}' does not match transaction type {$validated['type']}.");
        }

        $oldMonth = Carbon::parse($transaction->transaction_date)->format('Y-m');
        $isRecurring = $request->boolean('is_recurring');

        $transaction->update([
            'category_id' => $category->id,
            'amount' => $validated['amount'],
            'type' => $validated['type'],
            'description' => trim($validated['description']),
            'transaction_date' => $validated['transaction_date'],
            'payment_method' => $validated['payment_method'] ?? 'Cash',
            'note' => $validated['note'] ? trim($validated['note']) : null,
            'is_recurring' => $isRecurring,
            'recurring_frequency' => $isRecurring ? ($validated['recurring_frequency'] ?? 'monthly') : null,
            'recurring_start_date' => $isRecurring ? ($validated['recurring_start_date'] ?? $validated['transaction_date']) : null,
            'recurring_end_date' => $isRecurring ? ($validated['recurring_end_date'] ?? null) : null,
        ]);

        // Evaluate budget alerts and saving tips for the transaction's month (and previous month if changed)
        $newMonth = Carbon::parse($validated['transaction_date'])->format('Y-m');
        \App\Services\BudgetAlertService::checkUserBudgets($user, $newMonth);
        \App\Services\SavingTipService::generateTipsForUser($user, $newMonth);
        if ($oldMonth !== $newMonth) {
            \App\Services\BudgetAlertService::checkUserBudgets($user, $oldMonth);
            \App\Services\SavingTipService::generateTipsForUser($user, $oldMonth);
        }

        // Record edited activity (Phase 9)
        \App\Services\TransactionActivityService::recordActivity($user, $transaction->id, 'edited');

        return redirect()->route('transactions')
            ->with('success', 'Transaction updated successfully!');
    }

    /**
     * Record a viewed activity for a transaction (Phase 9).
     */
    public function trackView(Request $request, Transaction $transaction)
    {
        $user = Auth::user();

        if ($transaction->user_id === $user->id) {
            \App\Services\TransactionActivityService::recordActivity($user, $transaction->id, 'viewed');
        }

        return response()->json(['success' => true]);
    }

    /**
     * Remove the specified transaction from storage.
     */
    public function destroy(Transaction $transaction)
    {
        $user = Auth::user();

        // Enforce strict ownership
        if ($transaction->user_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        $month = Carbon::parse($transaction->transaction_date)->format('Y-m');
        $transaction->delete();

        \App\Services\BudgetAlertService::checkUserBudgets($user, $month);
        \App\Services\SavingTipService::generateTipsForUser($user, $month);

        return redirect()->route('transactions')
            ->with('success', 'Transaction deleted.');
    }
}
