<?php

use App\Http\Controllers\Admin\AdminCategoryController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminStatisticsController;
use App\Http\Controllers\Admin\AdminTipTemplateController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\AiCategorizationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CsvImportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DuplicateCheckController;
use App\Http\Controllers\ForecastController;
use App\Http\Controllers\InsightController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SavingTipController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TransactionNoteController;
use App\Http\Controllers\TransactionShareController;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return view('index');
})->name('home');

// Public Share Link (Phase 9 — No Login Required)
Route::get('/shared/transaction/{token}', [TransactionShareController::class, 'showPublic'])->name('transactions.shared');

/*
|--------------------------------------------------------------------------
| Guest Authentication Routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    // Registration
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->name('register.submit');

    // Login
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.submit');

    // Password Recovery
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Transaction Management
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions');
    Route::get('/add-transaction', [TransactionController::class, 'create'])->name('transactions.create');
    Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
    Route::put('/transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
    Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');

    // CSV Import & Export
    Route::post('/transactions/import/preview', [CsvImportController::class, 'preview'])->name('transactions.import.preview');
    Route::post('/transactions/import/confirm', [CsvImportController::class, 'confirm'])->name('transactions.import.confirm');
    Route::get('/transactions/export', [CsvImportController::class, 'export'])->name('transactions.export');

    // Category Management
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    // Budget Management
    Route::get('/budgets', [BudgetController::class, 'index'])->name('budgets');
    Route::post('/budgets', [BudgetController::class, 'store'])->name('budgets.store');
    Route::put('/budgets/{budget}', [BudgetController::class, 'update'])->name('budgets.update');
    Route::delete('/budgets/{budget}', [BudgetController::class, 'destroy'])->name('budgets.destroy');

    // Notification Center
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

    // Profile Management
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Settings & Security
    Route::get('/settings', [SettingsController::class, 'show'])->name('settings');
    Route::post('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password.update');

    // Logout
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/logout', [LoginController::class, 'logout'])->name('logout.get');

    // Financial Reports & Analytics (Phase 5)
    Route::get('/reports', [ReportController::class, 'index'])->name('reports');
    Route::get('/reports/export/pdf', [ReportController::class, 'exportPdf'])->name('reports.export.pdf');
    Route::get('/reports/print', [ReportController::class, 'printReport'])->name('reports.print');

    // Personalized Saving Tips Engine (Phase 6)
    Route::get('/saving-tips', [SavingTipController::class, 'index'])->name('saving-tips');
    Route::post('/saving-tips/{savingTip}/pin', [SavingTipController::class, 'pin'])->name('saving-tips.pin');
    Route::post('/saving-tips/{savingTip}/unpin', [SavingTipController::class, 'unpin'])->name('saving-tips.unpin');
    Route::post('/saving-tips/{savingTip}/dismiss', [SavingTipController::class, 'dismiss'])->name('saving-tips.dismiss');
    Route::post('/saving-tips/{savingTip}/restore', [SavingTipController::class, 'restore'])->name('saving-tips.restore');
    Route::post('/saving-tips/regenerate', [SavingTipController::class, 'regenerate'])->name('saving-tips.regenerate');

    // AI Features (Phase 7)
    Route::post('/api/ai/categorize-expense', [AiCategorizationController::class, 'categorize'])->middleware('throttle:60,1')->name('ai.categorize');
    Route::get('/insights/{month?}', [InsightController::class, 'index'])->name('insights');
    Route::post('/insights/generate', [InsightController::class, 'generate'])->name('insights.generate');

    // Advanced Features & System Intelligence (Phase 9)
    // 1. Transaction Bookmarks
    Route::get('/bookmarks', [BookmarkController::class, 'index'])->name('bookmarks');
    Route::post('/transactions/{id}/bookmark', [BookmarkController::class, 'toggle'])->name('transactions.bookmark');
    Route::delete('/transactions/{id}/bookmark', [BookmarkController::class, 'destroy'])->name('transactions.unbookmark');

    // 2. Transaction Notes
    Route::get('/transactions/{id}/notes', [TransactionNoteController::class, 'show'])->name('transactions.notes.show');
    Route::post('/transactions/{id}/notes', [TransactionNoteController::class, 'store'])->name('transactions.notes.store');
    Route::delete('/transactions/{id}/notes', [TransactionNoteController::class, 'destroy'])->name('transactions.notes.destroy');

    // 3. Transaction Sharing (Authenticated actions)
    Route::post('/transactions/{id}/share', [TransactionShareController::class, 'store'])->name('transactions.share');
    Route::delete('/transactions/share/{id}', [TransactionShareController::class, 'revoke'])->name('transactions.share.revoke');

    // 4. Duplicate Transaction Live Check
    Route::post('/api/transactions/check-duplicate', [DuplicateCheckController::class, 'check'])->name('transactions.check-duplicate');

    // 5. Transaction Activity Tracking
    Route::post('/transactions/{transaction}/track-view', [TransactionController::class, 'trackView'])->name('transactions.track-view');

    // 6. Upcoming-Month Deterministic Spending Forecast
    Route::get('/forecast', [ForecastController::class, 'index'])->name('forecast');
});

/*
|--------------------------------------------------------------------------
| Administrator Routes (Phase 8)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Admin Overview Dashboard
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    // User Management
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{id}', [AdminUserController::class, 'show'])->name('users.show');
    Route::patch('/users/{id}/status', [AdminUserController::class, 'toggleStatus'])->name('users.status');
    Route::patch('/users/{id}/role', [AdminUserController::class, 'updateRole'])->name('users.role');

    // System Categories Management
    Route::get('/categories', [AdminCategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [AdminCategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{id}', [AdminCategoryController::class, 'update'])->name('categories.update');
    Route::patch('/categories/{id}/status', [AdminCategoryController::class, 'toggleStatus'])->name('categories.status');
    Route::delete('/categories/{id}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');

    // System-wide Tip / Announcement Templates
    Route::get('/tip-templates', [AdminTipTemplateController::class, 'index'])->name('tip-templates.index');
    Route::post('/tip-templates', [AdminTipTemplateController::class, 'store'])->name('tip-templates.store');
    Route::put('/tip-templates/{id}', [AdminTipTemplateController::class, 'update'])->name('tip-templates.update');
    Route::patch('/tip-templates/{id}/status', [AdminTipTemplateController::class, 'toggleStatus'])->name('tip-templates.status');
    Route::delete('/tip-templates/{id}', [AdminTipTemplateController::class, 'destroy'])->name('tip-templates.destroy');

    // Usage Statistics
    Route::get('/statistics', [AdminStatisticsController::class, 'index'])->name('statistics.index');
});

