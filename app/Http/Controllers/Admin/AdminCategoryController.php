<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminCategoryController extends Controller
{
    /**
     * Display a list of system/default categories.
     */
    public function index(Request $request): View
    {
        $type = $request->query('type', 'all');
        $search = trim($request->query('search', ''));

        $query = Category::where('is_default', true)
            ->withCount('transactions');

        if ($type === 'income' || $type === 'expense') {
            $query->where('type', $type);
        }

        if (!empty($search)) {
            $query->where('name', 'like', "%{$search}%");
        }

        $categories = $query->orderBy('type')->orderBy('name')->get();

        $incomeCount = Category::where('is_default', true)->where('type', 'income')->count();
        $expenseCount = Category::where('is_default', true)->where('type', 'expense')->count();
        $activeCount = Category::where('is_default', true)->where('is_active', true)->count();
        $inactiveCount = Category::where('is_default', true)->where('is_active', false)->count();

        return view('admin.categories.index', compact(
            'categories',
            'type',
            'search',
            'incomeCount',
            'expenseCount',
            'activeCount',
            'inactiveCount'
        ));
    }

    /**
     * Store a new system default category.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'string', 'in:income,expense'],
            'icon' => ['nullable', 'string', 'max:50'],
        ], [
            'name.required' => 'Please enter a category name.',
            'type.required' => 'Please select whether this is an income or expense category.',
        ]);

        // Prevent duplicate system category of same name and type
        $exists = Category::where('is_default', true)
            ->where('type', $validated['type'])
            ->where('name', $validated['name'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['name' => "A system category named '{$validated['name']}' already exists for {$validated['type']}."])->withInput();
        }

        Category::create([
            'user_id' => null,
            'name' => trim($validated['name']),
            'type' => $validated['type'],
            'icon' => trim($validated['icon'] ?? 'bi-tag'),
            'is_default' => true,
            'is_active' => true,
        ]);

        return redirect()->route('admin.categories.index')->with('success', "System category '{$validated['name']}' created successfully.");
    }

    /**
     * Update an existing system category.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $category = Category::where('is_default', true)->findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'string', 'in:income,expense'],
            'icon' => ['nullable', 'string', 'max:50'],
        ]);

        // Duplicate check excluding self
        $exists = Category::where('is_default', true)
            ->where('type', $validated['type'])
            ->where('name', $validated['name'])
            ->where('id', '!=', $category->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['name' => "Another system category named '{$validated['name']}' already exists."])->withInput();
        }

        $category->update([
            'name' => trim($validated['name']),
            'type' => $validated['type'],
            'icon' => trim($validated['icon'] ?? 'bi-tag'),
        ]);

        return redirect()->route('admin.categories.index')->with('success', "System category '{$category->name}' updated successfully.");
    }

    /**
     * Toggle active/inactive status for a system category.
     */
    public function toggleStatus(Request $request, int $id): RedirectResponse
    {
        $category = Category::where('is_default', true)->findOrFail($id);
        $category->is_active = !$category->is_active;
        $category->save();

        $statusLabel = $category->is_active ? 'enabled' : 'disabled';
        return back()->with('success', "System category '{$category->name}' has been {$statusLabel}.");
    }

    /**
     * Safely delete a system category only if no historical transactions depend on it.
     */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        $category = Category::where('is_default', true)->findOrFail($id);

        $txCount = Transaction::where('category_id', $category->id)->count();

        if ($txCount > 0) {
            return back()->with('error', "Cannot delete system category '{$category->name}' because {$txCount} student transactions depend on it. Please disable/deactivate the category instead to preserve historical records.");
        }

        $name = $category->name;
        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', "System category '{$name}' was permanently deleted.");
    }
}
