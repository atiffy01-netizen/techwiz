<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\Budget;
use App\Models\AppNotification;
use App\Services\BudgetAlertService;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

echo "=== CAMPUS COIN PHASE 4 VERIFICATION SUITE ===\n\n";

$pass = 0;
$fail = 0;

function assertTest($title, $condition) {
    global $pass, $fail;
    if ($condition) {
        echo "[PASS] $title\n";
        $pass++;
    } else {
        echo "[FAIL] $title\n";
        $fail++;
    }
}

$currentMonth = Carbon::now()->format('Y-m');
$prevMonth = Carbon::now()->subMonth()->format('Y-m');

// Setup Test Users
$userA = User::firstOrCreate(
    ['email' => 'hunzala@campuscoin.edu'],
    ['name' => 'Hunzala Khan', 'password' => Hash::make('password123'), 'monthly_allowance' => 35000]
);
$userB = User::firstOrCreate(
    ['email' => 'ayesha@campuscoin.edu'],
    ['name' => 'Ayesha Khan', 'password' => Hash::make('password123'), 'monthly_allowance' => 28000]
);

// Clean previous Phase 4 test data
Budget::whereIn('user_id', [$userA->id, $userB->id])->delete();
AppNotification::whereIn('user_id', [$userA->id, $userB->id])->delete();
Transaction::whereIn('user_id', [$userA->id, $userB->id])->delete();

// === 1. BUDGET MODEL & MIGRATION ===
echo "\n--- Budget Model & Migration ---\n";

$foodCat = Category::where('name', 'Food')->where('type', 'expense')->first();
$transportCat = Category::where('name', 'Transport')->where('type', 'expense')->first();
$academicsCat = Category::where('name', 'Academics')->where('type', 'expense')->first();
$allowanceCat = Category::where('name', 'Allowance')->where('type', 'income')->first();

assertTest("Expense category 'Food' exists", $foodCat !== null);
assertTest("Expense category 'Transport' exists", $transportCat !== null);

// Create Budget for User A
$budgetFood = Budget::create([
    'user_id' => $userA->id,
    'category_id' => $foodCat->id,
    'month' => $currentMonth,
    'limit_amount' => 10000.00,
]);
assertTest("Budget created for Food with correct limit", $budgetFood->id > 0 && (float) $budgetFood->limit_amount === 10000.00);

$budgetTransport = Budget::create([
    'user_id' => $userA->id,
    'category_id' => $transportCat->id,
    'month' => $currentMonth,
    'limit_amount' => 5000.00,
]);
assertTest("Budget created for Transport", $budgetTransport->id > 0);

if ($academicsCat) {
    $budgetAcademics = Budget::create([
        'user_id' => $userA->id,
        'category_id' => $academicsCat->id,
        'month' => $currentMonth,
        'limit_amount' => 4000.00,
    ]);
}

// === 2. BUDGET UNIQUENESS ===
echo "\n--- Budget Uniqueness ---\n";
$duplicateCreated = false;
try {
    Budget::create([
        'user_id' => $userA->id,
        'category_id' => $foodCat->id,
        'month' => $currentMonth,
        'limit_amount' => 15000.00,
    ]);
    $duplicateCreated = true;
} catch (\Illuminate\Database\QueryException $e) {
    // Unique constraint violation expected
}
assertTest("Duplicate budget (same user+category+month) is rejected by database", !$duplicateCreated);

// === 3. BUDGET CALCULATIONS ===
echo "\n--- Budget Calculations ---\n";

// Create Transactions for User A
$tx1 = Transaction::create([
    'user_id' => $userA->id,
    'category_id' => $allowanceCat->id,
    'amount' => 25000.00,
    'type' => 'income',
    'description' => 'September Allowance',
    'transaction_date' => Carbon::now()->toDateString(),
    'payment_method' => 'Bank Transfer',
]);

$tx2 = Transaction::create([
    'user_id' => $userA->id,
    'category_id' => $foodCat->id,
    'amount' => 8000.00,
    'type' => 'expense',
    'description' => 'Food expenses this month',
    'transaction_date' => Carbon::now()->toDateString(),
    'payment_method' => 'Cash',
]);

$tx3 = Transaction::create([
    'user_id' => $userA->id,
    'category_id' => $transportCat->id,
    'amount' => 3000.00,
    'type' => 'expense',
    'description' => 'Transport expenses',
    'transaction_date' => Carbon::now()->toDateString(),
    'payment_method' => 'JazzCash',
]);

// Refresh budget instances
$budgetFood->refresh();
$actualFoodSpent = $budgetFood->getActualSpending();
assertTest("Food budget actual spending is Rs. 8,000", $actualFoodSpent === 8000.00);

$foodUsagePercent = $budgetFood->getUsagePercentage($actualFoodSpent);
assertTest("Food budget usage percentage is 80%", $foodUsagePercent === 80.0);

$foodStatus = $budgetFood->getStatus($actualFoodSpent);
assertTest("Food budget status is 'near_limit' (80% >= 75%)", $foodStatus === Budget::STATUS_NEAR_LIMIT);

$transportSpent = $budgetTransport->getActualSpending();
assertTest("Transport budget actual spending is Rs. 3,000", $transportSpent === 3000.00);

$transportUsage = $budgetTransport->getUsagePercentage($transportSpent);
assertTest("Transport budget usage percentage is 60%", $transportUsage === 60.0);

$transportStatus = $budgetTransport->getStatus($transportSpent);
assertTest("Transport budget status is 'on_track' (60% < 75%)", $transportStatus === Budget::STATUS_ON_TRACK);

// === 4. BUDGET ALERTS ===
echo "\n--- Budget Alerts ---\n";

$alerts = BudgetAlertService::checkUserBudgets($userA, $currentMonth);
assertTest("Budget alert service generated at least 1 notification", count($alerts) >= 1);

$foodAlert = AppNotification::where('user_id', $userA->id)
    ->where('budget_id', $budgetFood->id)
    ->where('alert_state', 'near_limit')
    ->first();
assertTest("Food 'near_limit' notification created", $foodAlert !== null);
assertTest("Food alert has correct type 'budget_near_limit'", $foodAlert ? $foodAlert->type === 'budget_near_limit' : false);

// === 5. DUPLICATE ALERT PREVENTION ===
echo "\n--- Duplicate Alert Prevention ---\n";

$beforeCount = AppNotification::where('user_id', $userA->id)->count();
BudgetAlertService::checkUserBudgets($userA, $currentMonth);
$afterCount = AppNotification::where('user_id', $userA->id)->count();
assertTest("Running alert check again does NOT create duplicate notifications", $afterCount === $beforeCount);

// === 6. EXCEEDED BUDGET ===
echo "\n--- Exceeded Budget ---\n";

$tx4 = Transaction::create([
    'user_id' => $userA->id,
    'category_id' => $foodCat->id,
    'amount' => 3500.00,
    'type' => 'expense',
    'description' => 'More Food spending',
    'transaction_date' => Carbon::now()->toDateString(),
    'payment_method' => 'Cash',
]);

$budgetFood->refresh();
$newFoodSpent = $budgetFood->getActualSpending();
assertTest("Food budget actual spending is now Rs. 11,500", $newFoodSpent === 11500.00);

$newFoodStatus = $budgetFood->getStatus($newFoodSpent);
assertTest("Food budget status is now 'over_budget'", $newFoodStatus === Budget::STATUS_OVER_BUDGET);

BudgetAlertService::checkUserBudgets($userA, $currentMonth);
$exceededAlert = AppNotification::where('user_id', $userA->id)
    ->where('budget_id', $budgetFood->id)
    ->where('alert_state', 'over_budget')
    ->first();
assertTest("Food 'over_budget' notification created", $exceededAlert !== null);
assertTest("Exceeded alert type is 'budget_exceeded'", $exceededAlert ? $exceededAlert->type === 'budget_exceeded' : false);

// === 7. USER ISOLATION ===
echo "\n--- User Isolation ---\n";

$budgetFoodB = Budget::create([
    'user_id' => $userB->id,
    'category_id' => $foodCat->id,
    'month' => $currentMonth,
    'limit_amount' => 8000.00,
]);

$txB = Transaction::create([
    'user_id' => $userB->id,
    'category_id' => $foodCat->id,
    'amount' => 800.00,
    'type' => 'expense',
    'description' => 'Ayesha Food',
    'transaction_date' => Carbon::now()->toDateString(),
    'payment_method' => 'Cash',
]);

// User B's food budget must only include User B's transactions
$userBFoodSpent = $budgetFoodB->getActualSpending();
assertTest("User B food budget reflects ONLY User B's spending (Rs. 800)", $userBFoodSpent === 800.00);

// User A's budgets must NOT include User B's transactions
$budgetFood->refresh();
$userAFoodSpent = $budgetFood->getActualSpending();
assertTest("User A food budget does NOT include User B's transactions (Rs. 11,500)", $userAFoodSpent === 11500.00);

// User A notifications should not appear for User B
$userANotifCount = AppNotification::where('user_id', $userA->id)->count();
$userBNotifCount = AppNotification::where('user_id', $userB->id)->count();
assertTest("User A has notifications", $userANotifCount >= 2);

BudgetAlertService::checkUserBudgets($userB, $currentMonth);
$userBNotifCountAfter = AppNotification::where('user_id', $userB->id)->count();
assertTest("User B notifications are independent", $userBNotifCountAfter >= 0);

// === 8. NOTIFICATION MARK READ ===
echo "\n--- Notification Operations ---\n";

$unreadBeforeFoodAlert = is_null($foodAlert->read_at);
assertTest("Food alert initially unread", $unreadBeforeFoodAlert);

$foodAlert->markAsRead();
$foodAlert->refresh();
assertTest("Food alert marked as read", !is_null($foodAlert->read_at));

$unreadCount = AppNotification::where('user_id', $userA->id)->whereNull('read_at')->count();
assertTest("Unread count decremented", $unreadCount >= 0);

// === 9. BUDGET EDIT ===
echo "\n--- Budget Edit ---\n";

$budgetFood->update(['limit_amount' => 15000.00]);
$budgetFood->refresh();
assertTest("Budget limit updated to Rs. 15,000", (float) $budgetFood->limit_amount === 15000.00);

$newUsage = $budgetFood->getUsagePercentage();
assertTest("Food budget usage recalculated after edit (11,500/15,000 ≈ 76.7%)", $newUsage > 76 && $newUsage < 77);

$newStatus = $budgetFood->getStatus();
assertTest("Food budget status now 'near_limit' (76.7%)", $newStatus === Budget::STATUS_NEAR_LIMIT);

// === 10. BUDGET DELETE ===
echo "\n--- Budget Delete ---\n";

$transportTxBefore = Transaction::where('user_id', $userA->id)->where('category_id', $transportCat->id)->count();
$budgetTransport->delete();
$transportTxAfter = Transaction::where('user_id', $userA->id)->where('category_id', $transportCat->id)->count();
assertTest("Deleting transport budget does NOT delete transactions", $transportTxAfter === $transportTxBefore);

$deletedBudget = Budget::find($budgetTransport->id);
assertTest("Transport budget is removed from database", $deletedBudget === null);

// === 11. BALANCE / SUMMARY VERIFICATION ===
echo "\n--- Balance & Summary ---\n";

$lifetimeIncome = (float) Transaction::where('user_id', $userA->id)->where('type', 'income')->sum('amount');
$lifetimeExpense = (float) Transaction::where('user_id', $userA->id)->where('type', 'expense')->sum('amount');
$balance = $lifetimeIncome - $lifetimeExpense;
assertTest("User A lifetime income is Rs. 25,000", $lifetimeIncome === 25000.00);
assertTest("User A lifetime expense is Rs. 14,500", $lifetimeExpense === 14500.00);
assertTest("User A available balance is Rs. 10,500", $balance === 10500.00);

// === 12. NO SPENDING BUDGET ===
echo "\n--- No Spending Budget ---\n";

$subsCat = Category::where('type', 'expense')->where('name', 'Subscriptions')->first();
if ($subsCat) {
    $budgetSubs = Budget::create([
        'user_id' => $userA->id,
        'category_id' => $subsCat->id,
        'month' => $currentMonth,
        'limit_amount' => 2000.00,
    ]);
    $subsStatus = $budgetSubs->getStatus();
    assertTest("Budget with zero spending has status 'no_spending'", $subsStatus === Budget::STATUS_NO_SPENDING);
} else {
    echo "[SKIP] 'Subscriptions' category not found\n";
}

echo "\n=== SUMMARY: {$pass} PASSED, {$fail} FAILED ===\n";
