<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    /**
     * Display a paginated list of all users with search and filtering.
     */
    public function index(Request $request): View
    {
        $search = trim($request->query('search', ''));
        $role = $request->query('role', 'all');
        $status = $request->query('status', 'all');
        $sort = $request->query('sort', 'latest');

        $query = User::query()
            ->withCount(['transactions', 'budgets', 'savingTips', 'aiMonthlyInsights']);

        // Search by name or email
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('student_id', 'like', "%{$search}%")
                  ->orWhere('university', 'like', "%{$search}%");
            });
        }

        // Role filter
        if ($role === 'admin') {
            $query->where('role', 'admin');
        } elseif ($role === 'user') {
            $query->where(function ($q) {
                $q->where('role', 'user')->orWhereNull('role');
            });
        }

        // Status filter
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        // Sorting
        match ($sort) {
            'oldest' => $query->oldest('created_at'),
            'name' => $query->orderBy('name', 'asc'),
            'transactions' => $query->orderByDesc('transactions_count'),
            default => $query->latest('created_at'),
        };

        $users = $query->paginate(15)->withQueryString();

        // Summary counts for filter chips
        $totalCount = User::count();
        $activeCount = User::where('is_active', true)->count();
        $inactiveCount = $totalCount - $activeCount;
        $adminCount = User::where('role', 'admin')->count();

        return view('admin.users.index', compact(
            'users',
            'search',
            'role',
            'status',
            'sort',
            'totalCount',
            'activeCount',
            'inactiveCount',
            'adminCount'
        ));
    }

    /**
     * Display detailed non-sensitive statistics for a specific user.
     */
    public function show(int $id): View
    {
        $user = User::withCount(['transactions', 'budgets', 'savingTips', 'aiMonthlyInsights'])
            ->findOrFail($id);

        // Real aggregate calculations from DB
        $totalIncome = (float) $user->transactions()->where('type', 'income')->sum('amount');
        $totalExpense = (float) $user->transactions()->where('type', 'expense')->sum('amount');
        $netBalance = $totalIncome - $totalExpense;

        $incomeCount = $user->transactions()->where('type', 'income')->count();
        $expenseCount = $user->transactions()->where('type', 'expense')->count();

        // Recent transactions preview (safe non-sensitive fields)
        $recentTransactions = $user->transactions()
            ->with('category:id,name,icon,type')
            ->latest('transaction_date')
            ->latest('id')
            ->take(8)
            ->get(['id', 'user_id', 'category_id', 'type', 'amount', 'transaction_date', 'description']);

        // User budgets summary
        $budgets = $user->budgets()
            ->with('category:id,name,icon')
            ->get();

        // Saving tips summary
        $savingTips = $user->savingTips()
            ->latest('id')
            ->take(5)
            ->get();

        // AI insights summary
        $aiInsights = $user->aiMonthlyInsights()
            ->latest('month')
            ->take(5)
            ->get();

        return view('admin.users.show', compact(
            'user',
            'totalIncome',
            'totalExpense',
            'netBalance',
            'incomeCount',
            'expenseCount',
            'recentTransactions',
            'budgets',
            'savingTips',
            'aiInsights'
        ));
    }

    /**
     * Toggle active/deactivated status for a user.
     */
    public function toggleStatus(Request $request, int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot deactivate your own administrator account.');
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $actionText = $user->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "User account for {$user->name} has been {$actionText} successfully.");
    }

    /**
     * Update a user's role (user/admin).
     */
    public function updateRole(Request $request, int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'role' => ['required', 'string', 'in:user,admin'],
        ]);

        if ($user->id === Auth::id() && $validated['role'] !== 'admin') {
            return back()->with('error', 'You cannot remove your own administrator role.');
        }

        $user->role = $validated['role'];
        $user->save();

        return back()->with('success', "Role for {$user->name} has been updated to {$user->role}.");
    }
}
