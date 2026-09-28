<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories for the authenticated user.
     */
    public function index()
    {
        $user = Auth::user();
        $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
        $endOfMonth = Carbon::now()->endOfMonth()->toDateString();

        // Retrieve default and user's custom categories
        $categories = Category::forUser($user->id)
            ->orderBy('is_default', 'desc')
            ->orderBy('name', 'asc')
            ->get();

        // Calculate transaction statistics per category for the current user
        $stats = Transaction::where('user_id', $user->id)
            ->select(
                'category_id',
                DB::raw('COUNT(*) as total_count'),
                DB::raw("SUM(CASE WHEN transaction_date BETWEEN '{$startOfMonth}' AND '{$endOfMonth}' THEN amount ELSE 0 END) as month_amount")
            )
            ->groupBy('category_id')
            ->get()
            ->keyBy('category_id');

        // Attach stats to each category
        foreach ($categories as $category) {
            $catStat = $stats->get($category->id);
            $category->month_amount = $catStat ? (float) $catStat->month_amount : 0.0;
            $category->total_count = $catStat ? (int) $catStat->total_count : 0;
        }

        // Split into Expense and Income categories
        $expenseCategories = $categories->where('type', 'expense');
        $incomeCategories = $categories->where('type', 'income');

        $totalCategoriesCount = $categories->count();
        $defaultCount = $categories->where('is_default', true)->count();
        $customCount = $categories->where('is_default', false)->count();

        // Most spent category this month
        $mostSpent = $expenseCategories->sortByDesc('month_amount')->first();
        $mostSpentName = ($mostSpent && $mostSpent->month_amount > 0) ? $mostSpent->name : 'None';
        $mostSpentAmount = ($mostSpent && $mostSpent->month_amount > 0) ? $mostSpent->month_amount : 0;

        return view('categories', compact(
            'user',
            'expenseCategories',
            'incomeCategories',
            'totalCategoriesCount',
            'defaultCount',
            'customCount',
            'mostSpentName',
            'mostSpentAmount'
        ));
    }

    /**
     * Store a newly created custom category in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
            ],
            'type' => 'required|in:income,expense',
            'icon' => 'nullable|string|max:50',
        ]);

        $name = trim($validated['name']);
        $type = $validated['type'];
        $icon = !empty($validated['icon']) ? trim($validated['icon']) : ($type === 'income' ? 'bi-cash-coin' : 'bi-tag');

        // Check if category name already exists for this user in the same type
        $existing = Category::forUser($user->id)
            ->where('name', $name)
            ->where('type', $type)
            ->first();

        if ($existing) {
            return redirect()->back()
                ->withInput()
                ->with('error', "A category named '{$name}' ({$type}) already exists.");
        }

        Category::create([
            'user_id' => $user->id,
            'name' => $name,
            'type' => $type,
            'icon' => $icon,
            'is_default' => false,
        ]);

        return redirect()->route('categories')
            ->with('success', "Category '{$name}' created successfully!");
    }

    /**
     * Update the specified custom category.
     */
    public function update(Request $request, Category $category)
    {
        $user = Auth::user();

        // Authorization check
        if ($category->is_default || $category->user_id !== $user->id) {
            return redirect()->back()->with('error', 'Default system categories cannot be modified.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:income,expense',
            'icon' => 'nullable|string|max:50',
        ]);

        $name = trim($validated['name']);
        $type = $validated['type'];
        $icon = !empty($validated['icon']) ? trim($validated['icon']) : $category->icon;

        // Ensure unique name per user and type (excluding current category)
        $existing = Category::forUser($user->id)
            ->where('name', $name)
            ->where('type', $type)
            ->where('id', '!=', $category->id)
            ->first();

        if ($existing) {
            return redirect()->back()
                ->withInput()
                ->with('error', "Another category named '{$name}' already exists.");
        }

        $category->update([
            'name' => $name,
            'type' => $type,
            'icon' => $icon,
        ]);

        return redirect()->route('categories')
            ->with('success', "Category '{$name}' updated successfully!");
    }

    /**
     * Remove the specified custom category from storage safely.
     */
    public function destroy(Category $category)
    {
        $user = Auth::user();

        // Authorization check
        if ($category->is_default || $category->user_id !== $user->id) {
            return redirect()->back()->with('error', 'Default system categories cannot be deleted.');
        }

        // Safety check: Prevent deletion if category has associated transactions
        $hasTransactions = Transaction::where('user_id', $user->id)
            ->where('category_id', $category->id)
            ->exists();

        if ($hasTransactions) {
            return redirect()->back()->with('error', "This category contains transactions. Move those transactions to another category before deleting it.");
        }

        $categoryName = $category->name;
        $category->delete();

        return redirect()->route('categories')
            ->with('success', "Category '{$categoryName}' deleted successfully.");
    }
}
