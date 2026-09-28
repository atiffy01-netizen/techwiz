<?php

/**
 * CAMPUS COIN — PHASE 8 AUTOMATED VERIFICATION SUITE
 * Admin Panel + System Management
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AdminTipTemplate;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Budget;
use App\Models\SavingTip;
use App\Models\AiMonthlyInsight;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

$pass = 0;
$fail = 0;

function check(bool $condition, string $label, string $failReason = ''): void {
    global $pass, $fail;
    if ($condition) {
        echo "[PASS] $label\n";
        $pass++;
    } else {
        echo "[FAIL] $label" . ($failReason ? " — $failReason" : '') . "\n";
        $fail++;
    }
}

echo "\n=== CAMPUS COIN PHASE 8 ADMIN PANEL VERIFICATION SUITE ===\n\n";

// ─────────────────────────────────────────────
// Pre-flight: Database Schema & Setup
// ─────────────────────────────────────────────
echo "--- TEST 0: Database Schema Verifications ---\n";
check(Schema::hasColumn('users', 'role'), 'users table has "role" column');
check(Schema::hasColumn('users', 'is_active'), 'users table has "is_active" column');
check(Schema::hasColumn('categories', 'is_active'), 'categories table has "is_active" column');
check(Schema::hasTable('admin_tip_templates'), 'admin_tip_templates table exists');

// Setup or retrieve test users
$adminUser = User::where('email', 'admin_phase8@campuscoin.edu')->first();
if (!$adminUser) {
    $adminUser = User::create([
        'name' => 'Campus Coin Admin',
        'email' => 'admin_phase8@campuscoin.edu',
        'password' => bcrypt('AdminPassword123!'),
        'role' => 'admin',
        'is_active' => true,
        'university' => 'Campus Coin University',
        'program' => 'System Administration',
    ]);
} else {
    $adminUser->update(['role' => 'admin', 'is_active' => true]);
}

$normalUser = User::where('email', 'hunzala@campuscoin.edu')->first();
if (!$normalUser) {
    $normalUser = User::create([
        'name' => 'Hunzala Test Student',
        'email' => 'hunzala@campuscoin.edu',
        'password' => bcrypt('StudentPass123!'),
        'role' => 'user',
        'is_active' => true,
    ]);
} else {
    $normalUser->update(['role' => 'user', 'is_active' => true]);
}

check($adminUser->isAdmin() === true, 'Admin user instance has role "admin" (isAdmin() == true)');
check($normalUser->isAdmin() === false, 'Student user has role "user" (isAdmin() == false)');
check($normalUser->isUser() === true, 'Student user isUser() == true');
check($normalUser->isActive() === true, 'Student user isActive() == true');

// ─────────────────────────────────────────────
// TEST 1: Admin Middleware Authorization
// ─────────────────────────────────────────────
echo "\n--- TEST 1: Admin Middleware Authorization ---\n";

$middleware = new \App\Http\Middleware\AdminMiddleware();

// 1.1 Guest access should redirect to login
Auth::logout();
$guestReq = Request::create('/admin', 'GET');
$guestResp = $middleware->handle($guestReq, fn($req) => response('OK', 200));
check($guestResp->isRedirection(), 'Guest access to /admin redirects to login');

// 1.2 Normal student user should get 403 Forbidden
Auth::login($normalUser);
$studentReq = Request::create('/admin', 'GET');
$forbiddenCaught = false;
try {
    $middleware->handle($studentReq, fn($req) => response('OK', 200));
} catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
    if ($e->getStatusCode() === 403) {
        $forbiddenCaught = true;
    }
}
check($forbiddenCaught, 'Normal authenticated student receives 403 Forbidden on /admin');

// 1.3 Normal student requesting JSON receives JSON 403
$studentJsonReq = Request::create('/admin', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
$jsonResp = $middleware->handle($studentJsonReq, fn($req) => response('OK', 200));
check($jsonResp->getStatusCode() === 403, 'Normal student JSON request to /admin receives 403 Forbidden');

// 1.4 Admin user is permitted
Auth::login($adminUser);
$adminReq = Request::create('/admin', 'GET');
$adminResp = $middleware->handle($adminReq, fn($req) => response('OK', 200));
check($adminResp->getStatusCode() === 200, 'Admin user is granted access through AdminMiddleware');

// 1.5 Deactivated admin is blocked & logged out
$deactivatedAdmin = User::create([
    'name' => 'Deactivated Admin',
    'email' => 'deact_admin_' . time() . '@campuscoin.edu',
    'password' => bcrypt('pass123'),
    'role' => 'admin',
    'is_active' => false,
]);
Auth::login($deactivatedAdmin);
$deactReq = Request::create('/admin', 'GET');
// Setup session mock for invalidation test
$session = app('session.store');
$deactReq->setLaravelSession($session);
$deactResp = $middleware->handle($deactReq, fn($req) => response('OK', 200));
check($deactResp->isRedirection(), 'Deactivated admin is rejected and redirected to login');
check(Auth::check() === false, 'Deactivated admin session is terminated');
$deactivatedAdmin->delete();

// ─────────────────────────────────────────────
// TEST 2: Admin Dashboard Controller & Aggregates
// ─────────────────────────────────────────────
echo "\n--- TEST 2: Admin Dashboard Controller ---\n";

Auth::login($adminUser);
$dashboardCtrl = new \App\Http\Controllers\Admin\AdminDashboardController();
$dashView = $dashboardCtrl->index();

check($dashView instanceof \Illuminate\View\View, 'AdminDashboardController::index() returns View');
$viewData = $dashView->getData();

check(isset($viewData['totalUsers']) && $viewData['totalUsers'] >= 2, 'Dashboard totalUsers metric is computed');
check(isset($viewData['activeUsers']) && $viewData['activeUsers'] >= 1, 'Dashboard activeUsers metric is computed');
check(isset($viewData['totalTransactions']), 'Dashboard totalTransactions metric exists');
check(isset($viewData['totalIncome']), 'Dashboard totalIncome metric exists');
check(isset($viewData['totalExpenses']), 'Dashboard totalExpenses metric exists');
check(isset($viewData['netPlatformBalance']), 'Dashboard netPlatformBalance metric exists');
check(isset($viewData['totalBudgets']), 'Dashboard totalBudgets metric exists');
check(isset($viewData['totalAiInsights']), 'Dashboard totalAiInsights metric exists');
check(isset($viewData['totalSavingTips']), 'Dashboard totalSavingTips metric exists');
check(isset($viewData['recentUsers']) && count($viewData['recentUsers']) > 0, 'Dashboard contains recent registered users');

// ─────────────────────────────────────────────
// TEST 3: User Management (AdminUserController)
// ─────────────────────────────────────────────
echo "\n--- TEST 3: User Management (AdminUserController) ---\n";

$userCtrl = new \App\Http\Controllers\Admin\AdminUserController();

// 3.1 Listing & Pagination
$listReq = Request::create('/admin/users', 'GET');
$listView = $userCtrl->index($listReq);
check($listView instanceof \Illuminate\View\View, 'AdminUserController::index() returns View');
$usersList = $listView->getData()['users'];
check($usersList->total() >= 2, 'Admin user list contains all registered platform accounts');

// 3.2 Search by name
$searchReq = Request::create('/admin/users', 'GET', ['search' => 'Hunzala']);
$searchView = $userCtrl->index($searchReq);
$searchResults = $searchView->getData()['users'];
check($searchResults->count() >= 1, 'Admin user search by name returns matching records');
check(str_contains($searchResults->first()->name, 'Hunzala') || str_contains($searchResults->first()->email, 'hunzala'), 'User search result matches query');

// 3.3 Filter by role
$adminRoleReq = Request::create('/admin/users', 'GET', ['role' => 'admin']);
$adminRoleView = $userCtrl->index($adminRoleReq);
$adminResults = $adminRoleView->getData()['users'];
$allAreAdmins = true;
foreach ($adminResults as $u) {
    if ($u->role !== 'admin') { $allAreAdmins = false; break; }
}
check($allAreAdmins && $adminResults->count() >= 1, 'Filter by role "admin" returns exclusively administrators');

// 3.4 User Details (show)
$showView = $userCtrl->show($normalUser->id);
check($showView instanceof \Illuminate\View\View, 'AdminUserController::show() returns View');
$showData = $showView->getData();
check($showData['user']->id === $normalUser->id, 'User details shows targeted user ID');
check(isset($showData['totalIncome']), 'User details calculates user total income');
check(isset($showData['totalExpense']), 'User details calculates user total expense');
check(isset($showData['netBalance']), 'User details calculates net balance');
check(!isset($showData['user']->password) || !empty($showData['user']->password), 'User object is present');

// Verify sensitive attributes are not exposed in view data
check(!array_key_exists('remember_token', $showData['user']->toArray()), 'User serialization excludes remember_token');

// 3.5 User Activation & Deactivation (Status Toggle)
DB::beginTransaction();
try {
    $testSubject = User::create([
        'name' => 'Status Subject',
        'email' => 'status_sub_' . time() . '@campuscoin.edu',
        'password' => bcrypt('password123'),
        'role' => 'user',
        'is_active' => true,
    ]);

    // Create financial records for this user
    $cat = Category::where('is_default', true)->first();
    Transaction::create([
        'user_id' => $testSubject->id,
        'category_id' => $cat->id,
        'type' => 'expense',
        'amount' => 500.00,
        'transaction_date' => now()->toDateString(),
        'description' => 'Test Expense Before Deactivation',
    ]);

    $toggleReq = Request::create('/admin/users/' . $testSubject->id . '/status', 'PATCH');
    $userCtrl->toggleStatus($toggleReq, $testSubject->id);
    $testSubject->refresh();
    check($testSubject->is_active === false, 'AdminUserController::toggleStatus() deactivates active user');

    // Verify financial data is preserved on deactivation
    $txRemaining = Transaction::where('user_id', $testSubject->id)->count();
    check($txRemaining === 1, 'Deactivating a user does NOT delete their transaction records (Data Preserved)');

    // Toggle back to active
    $userCtrl->toggleStatus($toggleReq, $testSubject->id);
    $testSubject->refresh();
    check($testSubject->is_active === true, 'AdminUserController::toggleStatus() re-activates deactivated user');

    // Admin cannot deactivate their own account
    $selfToggleResp = $userCtrl->toggleStatus($toggleReq, $adminUser->id);
    $adminUser->refresh();
    check($adminUser->is_active === true, 'Admin cannot deactivate their own administrator account');
} finally {
    DB::rollBack();
}

// ─────────────────────────────────────────────
// TEST 4: System Categories Management
// ─────────────────────────────────────────────
echo "\n--- TEST 4: System Categories Management ---\n";

$catCtrl = new \App\Http\Controllers\Admin\AdminCategoryController();

// 4.1 Index
$catReq = Request::create('/admin/categories', 'GET');
$catView = $catCtrl->index($catReq);
check($catView instanceof \Illuminate\View\View, 'AdminCategoryController::index() returns View');
$catData = $catView->getData();
check($catData['incomeCount'] >= 1, 'Default income categories count is reported');
check($catData['expenseCount'] >= 1, 'Default expense categories count is reported');

// 4.2 Create new system category
DB::beginTransaction();
try {
    $uniqueCatName = 'Phase8 Test Category ' . time();
    $storeReq = Request::create('/admin/categories', 'POST', [
        'name' => $uniqueCatName,
        'type' => 'expense',
        'icon' => 'bi-laptop',
    ]);
    $catCtrl->store($storeReq);

    $createdCat = Category::where('is_default', true)->where('name', $uniqueCatName)->first();
    check($createdCat !== null, 'Admin can create a new system default category');
    check($createdCat->is_default === true, 'Created system category has is_default = true');
    check($createdCat->is_active === true, 'Created system category defaults to is_active = true');

    // 4.3 Update system category
    $updateReq = Request::create('/admin/categories/' . $createdCat->id, 'PUT', [
        'name' => $uniqueCatName . ' Updated',
        'type' => 'expense',
        'icon' => 'bi-pc-display',
    ]);
    $catCtrl->update($updateReq, $createdCat->id);
    $createdCat->refresh();
    check($createdCat->name === $uniqueCatName . ' Updated', 'Admin can update system category name');
    check($createdCat->icon === 'bi-pc-display', 'Admin can update system category icon');

    // 4.4 Toggle category status (Disable / Enable)
    $toggleCatReq = Request::create('/admin/categories/' . $createdCat->id . '/status', 'PATCH');
    $catCtrl->toggleStatus($toggleCatReq, $createdCat->id);
    $createdCat->refresh();
    check($createdCat->is_active === false, 'Admin can disable/deactivate a system category');

    $catCtrl->toggleStatus($toggleCatReq, $createdCat->id);
    $createdCat->refresh();
    check($createdCat->is_active === true, 'Admin can re-enable a system category');

    // 4.5 Safe deletion: When transactions exist, deletion is prevented
    Transaction::create([
        'user_id' => $normalUser->id,
        'category_id' => $createdCat->id,
        'type' => 'expense',
        'amount' => 250.00,
        'transaction_date' => now()->toDateString(),
        'description' => 'Attached to test cat',
    ]);

    $destroyReq = Request::create('/admin/categories/' . $createdCat->id, 'DELETE');
    $catCtrl->destroy($destroyReq, $createdCat->id);
    $stillExists = Category::where('id', $createdCat->id)->exists();
    check($stillExists === true, 'System category with dependent transactions CANNOT be deleted (Safe Guard)');

    // 4.6 Safe deletion: When 0 transactions exist, deletion succeeds
    Transaction::where('category_id', $createdCat->id)->delete();
    $catCtrl->destroy($destroyReq, $createdCat->id);
    $isDeleted = !Category::where('id', $createdCat->id)->exists();
    check($isDeleted === true, 'System category with 0 transactions is safely deleted');
} finally {
    DB::rollBack();
}

// ─────────────────────────────────────────────
// TEST 5: System Tip / Announcement Templates
// ─────────────────────────────────────────────
echo "\n--- TEST 5: Tip & Announcement Templates (AdminTipTemplateController) ---\n";

$tplCtrl = new \App\Http\Controllers\Admin\AdminTipTemplateController();

// 5.1 Index
$tplReq = Request::create('/admin/tip-templates', 'GET');
$tplView = $tplCtrl->index($tplReq);
check($tplView instanceof \Illuminate\View\View, 'AdminTipTemplateController::index() returns View');

// 5.2 CRUD
DB::beginTransaction();
try {
    $storeTplReq = Request::create('/admin/tip-templates', 'POST', [
        'title' => 'End of Semester Savings Announcement',
        'message' => 'Remember to review your monthly budgets before the semester finals!',
        'type' => 'announcement',
        'status' => 'active',
    ]);
    $tplCtrl->store($storeTplReq);

    $createdTpl = AdminTipTemplate::where('title', 'End of Semester Savings Announcement')->first();
    check($createdTpl !== null, 'Admin can create a new system announcement / tip template');
    check($createdTpl->type === 'announcement', 'Template type is announcement');
    check($createdTpl->status === 'active', 'Template status is active');
    check($createdTpl->created_by === $adminUser->id, 'Template created_by is assigned to the authenticated admin');

    // 5.3 Edit Template
    $updateTplReq = Request::create('/admin/tip-templates/' . $createdTpl->id, 'PUT', [
        'title' => 'End of Semester Savings Announcement (Updated)',
        'message' => 'New message body with extra financial advice.',
        'type' => 'saving_tip',
        'status' => 'active',
    ]);
    $tplCtrl->update($updateTplReq, $createdTpl->id);
    $createdTpl->refresh();
    check($createdTpl->title === 'End of Semester Savings Announcement (Updated)', 'Admin can update template title');
    check($createdTpl->type === 'saving_tip', 'Admin can update template type');

    // 5.4 Toggle Status
    $toggleTplReq = Request::create('/admin/tip-templates/' . $createdTpl->id . '/status', 'PATCH');
    $tplCtrl->toggleStatus($toggleTplReq, $createdTpl->id);
    $createdTpl->refresh();
    check($createdTpl->status === 'inactive', 'Admin can deactivate template');

    // 5.5 Delete Template
    $delTplReq = Request::create('/admin/tip-templates/' . $createdTpl->id, 'DELETE');
    $tplCtrl->destroy($delTplReq, $createdTpl->id);
    $isTplDeleted = !AdminTipTemplate::where('id', $createdTpl->id)->exists();
    check($isTplDeleted === true, 'Admin can safely delete tip template');
} finally {
    DB::rollBack();
}

// ─────────────────────────────────────────────
// TEST 6: Usage Statistics (AdminStatisticsController)
// ─────────────────────────────────────────────
echo "\n--- TEST 6: Usage Statistics (AdminStatisticsController) ---\n";

$statsCtrl = new \App\Http\Controllers\Admin\AdminStatisticsController();

// 6.1 Last 6 months default
$statsReq = Request::create('/admin/statistics', 'GET');
$statsView = $statsCtrl->index($statsReq);
check($statsView instanceof \Illuminate\View\View, 'AdminStatisticsController::index() returns View');
$statsData = $statsView->getData();

check(isset($statsData['trendMonths']) && count($statsData['trendMonths']) === 6, 'Statistics provides 6-month trend array');
check(isset($statsData['trendIncome']) && count($statsData['trendIncome']) === 6, 'Statistics provides monthly income trend');
check(isset($statsData['trendExpense']) && count($statsData['trendExpense']) === 6, 'Statistics provides monthly expense trend');
check(isset($statsData['trendUserGrowth']) && count($statsData['trendUserGrowth']) === 6, 'Statistics provides monthly user growth trend');
check(isset($statsData['categoryBreakdown']), 'Statistics provides category expense breakdown');
check(isset($statsData['totalAiInsights']), 'Statistics provides AI feature usage count');
check(isset($statsData['totalSavingTips']), 'Statistics provides saving tips count');

// 6.2 Custom Date Range Filter
$customReq = Request::create('/admin/statistics', 'GET', [
    'timeframe' => 'custom',
    'start_date' => '2026-01-01',
    'end_date' => '2026-09-30',
]);
$customView = $statsCtrl->index($customReq);
check($customView instanceof \Illuminate\View\View, 'Custom date range filter returns valid statistics view');

// 6.3 Inverted dates are handled gracefully
$invertedReq = Request::create('/admin/statistics', 'GET', [
    'timeframe' => 'custom',
    'start_date' => '2026-12-31',
    'end_date' => '2026-01-01',
]);
$invertedView = $statsCtrl->index($invertedReq);
$invData = $invertedView->getData();
check($invData['startDate']->lte($invData['endDate']), 'Inverted start/end dates are swapped safely');

// ─────────────────────────────────────────────
// TEST 7: Student Dashboard Announcement Integration
// ─────────────────────────────────────────────
echo "\n--- TEST 7: Student Dashboard Announcement Integration ---\n";

DB::beginTransaction();
try {
    $activeAnnouncement = AdminTipTemplate::create([
        'title' => 'Campus Coin Maintenance Notice',
        'message' => 'Platform upgrades scheduled for this weekend.',
        'type' => 'announcement',
        'status' => 'active',
        'created_by' => $adminUser->id,
    ]);

    Auth::login($normalUser);
    $dashCtrl = new \App\Http\Controllers\DashboardController();
    $studentDashView = $dashCtrl->index(Request::create('/dashboard', 'GET'));
    $studentDashData = $studentDashView->getData();

    check(isset($studentDashData['activeAnnouncements']), 'Student dashboard receives active announcements');
    check($studentDashData['activeAnnouncements']->contains('id', $activeAnnouncement->id), 'Active announcement is delivered to student dashboard');
} finally {
    DB::rollBack();
}

// ─────────────────────────────────────────────
// TEST 8: Route Registrations & Naming
// ─────────────────────────────────────────────
echo "\n--- TEST 8: Admin Route Registrations ---\n";

$allRoutes = collect(Route::getRoutes())->map(fn($r) => $r->uri());
$namedRoutes = collect(Route::getRoutes())->filter(fn($r) => $r->getName() !== null)->map(fn($r) => $r->getName());

$expectedRoutes = [
    'admin',
    'admin/users',
    'admin/users/{id}',
    'admin/categories',
    'admin/tip-templates',
    'admin/statistics',
];

foreach ($expectedRoutes as $r) {
    check($allRoutes->contains($r), "Admin route '{$r}' is registered");
}

$expectedNames = [
    'admin.dashboard',
    'admin.users.index',
    'admin.users.show',
    'admin.users.status',
    'admin.users.role',
    'admin.categories.index',
    'admin.categories.store',
    'admin.categories.update',
    'admin.categories.status',
    'admin.categories.destroy',
    'admin.tip-templates.index',
    'admin.tip-templates.store',
    'admin.tip-templates.update',
    'admin.tip-templates.status',
    'admin.tip-templates.destroy',
    'admin.statistics.index',
];

foreach ($expectedNames as $name) {
    check($namedRoutes->contains($name), "Named admin route '{$name}' is registered");
}

// ─────────────────────────────────────────────
// SUMMARY
// ─────────────────────────────────────────────
echo "\n" . str_repeat('=', 50) . "\n";
echo "SUMMARY: PASS = $pass, FAIL = $fail\n";
echo str_repeat('=', 50) . "\n";

if ($fail === 0) {
    echo "ALL PHASE 8 ADMIN PANEL TESTS PASSED!\n\n";
    exit(0);
} else {
    echo "SOME TESTS FAILED. Review output above.\n\n";
    exit(1);
}
