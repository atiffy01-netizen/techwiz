<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminTipTemplate;
use App\Models\AiMonthlyInsight;
use App\Models\Budget;
use App\Models\Category;
use App\Models\SavingTip;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    /**
     * Show the main admin overview dashboard.
     */
    public function index(): View
    {
        // 1. Core Summary Metrics (Efficient SQL aggregations)
        $totalUsers = User::count();
        $activeUsers = User::where('is_active', true)->count();
        $inactiveUsers = $totalUsers - $activeUsers;

        $totalTransactions = Transaction::count();
        $totalIncome = (float) Transaction::where('type', 'income')->sum('amount');
        $totalExpenses = (float) Transaction::where('type', 'expense')->sum('amount');
        $netPlatformBalance = $totalIncome - $totalExpenses;

        $totalBudgets = Budget::count();
        $totalSavingTips = SavingTip::count();
        $totalAiInsights = AiMonthlyInsight::count();
        $totalSystemCategories = Category::where('is_default', true)->count();
        $totalAnnouncements = AdminTipTemplate::where('status', 'active')->count();

        // 2. Recent System Activity
        $recentUsers = User::latest()
            ->take(5)
            ->get(['id', 'name', 'email', 'role', 'is_active', 'program', 'created_at']);

        $recentInsights = AiMonthlyInsight::with('user:id,name,email')
            ->latest()
            ->take(5)
            ->get();

        $recentCategories = Category::where('is_default', true)
            ->latest()
            ->take(5)
            ->get();

        $recentTemplates = AdminTipTemplate::with('creator:id,name')
            ->latest()
            ->take(4)
            ->get();

        return view('admin.dashboard', compact(
            'totalUsers',
            'activeUsers',
            'inactiveUsers',
            'totalTransactions',
            'totalIncome',
            'totalExpenses',
            'netPlatformBalance',
            'totalBudgets',
            'totalSavingTips',
            'totalAiInsights',
            'totalSystemCategories',
            'totalAnnouncements',
            'recentUsers',
            'recentInsights',
            'recentCategories',
            'recentTemplates'
        ));
    }
}
