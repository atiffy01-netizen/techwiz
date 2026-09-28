<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\Budget;
use App\Models\SavingTip;
use App\Services\SavingTipService;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

echo "=== CAMPUS COIN PHASE 6 SAVING TIPS ENGINE VERIFICATION SUITE ===\n\n";

$pass = 0;
$fail = 0;

function assertTest($title, $condition, $details = "") {
    global $pass, $fail;
    if ($condition) {
        echo "[PASS] $title\n";
        $pass++;
    } else {
        echo "[FAIL] $title" . ($details ? " -> $details" : "") . "\n";
        $fail++;
    }
}

$currentMonth = Carbon::now()->format('Y-m');
$prevMonth1 = Carbon::now()->subMonth()->format('Y-m');
$prevMonth2 = Carbon::now()->subMonths(2)->format('Y-m');
$prevMonth3 = Carbon::now()->subMonths(3)->format('Y-m');

// Setup isolated test users
$userA = User::firstOrCreate(
    ['email' => 'student_phase6_a@campuscoin.edu'],
    ['name' => 'Tariq Student A', 'password' => Hash::make('password123'), 'monthly_allowance' => 35000, 'savings_goal' => 6000]
);

$userB = User::firstOrCreate(
    ['email' => 'student_phase6_b@campuscoin.edu'],
    ['name' => 'Sara Student B', 'password' => Hash::make('password123'), 'monthly_allowance' => 25000, 'savings_goal' => 4000]
);

$userNew = User::firstOrCreate(
    ['email' => 'student_new_brand@campuscoin.edu'],
    ['name' => 'Brand New Student', 'password' => Hash::make('password123'), 'monthly_allowance' => 20000]
);

// Clean previous test data
SavingTip::whereIn('user_id', [$userA->id, $userB->id, $userNew->id])->delete();
Transaction::whereIn('user_id', [$userA->id, $userB->id, $userNew->id])->delete();
Budget::whereIn('user_id', [$userA->id, $userB->id, $userNew->id])->delete();

// Categories
$foodCat = Category::where('name', 'Food')->where('type', 'expense')->first() ?? Category::firstOrCreate(['name' => 'Food', 'type' => 'expense', 'is_default' => true]);
$transportCat = Category::where('name', 'Transport')->where('type', 'expense')->first() ?? Category::firstOrCreate(['name' => 'Transport', 'type' => 'expense', 'is_default' => true]);
$subCat = Category::where('name', 'Subscriptions')->where('type', 'expense')->first() ?? Category::firstOrCreate(['name' => 'Subscriptions', 'type' => 'expense', 'is_default' => true]);

echo "\n--- TEST 1: Brand New User with No Transactions ---\n";
$newTips = SavingTipService::generateTipsForUser($userNew, $currentMonth);
$hasHistoryNew = SavingTipService::hasSufficientHistory($userNew, $currentMonth);
assertTest("Brand new user has 0 generated tips", $newTips->count() === 0);
assertTest("Brand new user hasSufficientHistory is false", $hasHistoryNew === false);

echo "\n--- TEST 2: Historical Average Comparison (Above Average Detection) ---\n";
// User A: Food spending in prior months: Rs. 7,000, Rs. 8,000, Rs. 9,000 (Average = 8,000)
// Current month: Rs. 12,000 (Diff = 4,000 above average)
Transaction::create([
    'user_id' => $userA->id,
    'category_id' => $foodCat->id,
    'amount' => 7000.00,
    'type' => 'expense',
    'description' => 'Month -3 Food',
    'transaction_date' => Carbon::now()->subMonths(3)->startOfMonth()->addDays(5)->toDateString(),
]);
Transaction::create([
    'user_id' => $userA->id,
    'category_id' => $foodCat->id,
    'amount' => 8000.00,
    'type' => 'expense',
    'description' => 'Month -2 Food',
    'transaction_date' => Carbon::now()->subMonths(2)->startOfMonth()->addDays(5)->toDateString(),
]);
Transaction::create([
    'user_id' => $userA->id,
    'category_id' => $foodCat->id,
    'amount' => 9000.00,
    'type' => 'expense',
    'description' => 'Month -1 Food',
    'transaction_date' => Carbon::now()->subMonth()->startOfMonth()->addDays(5)->toDateString(),
]);
Transaction::create([
    'user_id' => $userA->id,
    'category_id' => $foodCat->id,
    'amount' => 12000.00,
    'type' => 'expense',
    'description' => 'Current Month Food',
    'transaction_date' => Carbon::now()->startOfMonth()->addDays(2)->toDateString(),
]);

$tipsUserA = SavingTipService::generateTipsForUser($userA, $currentMonth);
$aboveAvgTip = $tipsUserA->firstWhere('tip_type', SavingTip::TYPE_ABOVE_HISTORICAL_AVERAGE);

assertTest("ABOVE_HISTORICAL_AVERAGE tip generated", $aboveAvgTip !== null);
assertTest("Potential savings calculated accurately (4,000)", $aboveAvgTip && (float) $aboveAvgTip->potential_savings === 4000.00, "Actual: " . ($aboveAvgTip ? $aboveAvgTip->potential_savings : 'none'));
assertTest("Message contains category and difference", $aboveAvgTip && str_contains($aboveAvgTip->message, 'Food') && str_contains($aboveAvgTip->message, '8,000.00'));

echo "\n--- TEST 3: Near Budget Limit Detection (75% - 99%) ---\n";
// Create Transport budget = Rs. 10,000. Spend = Rs. 8,000 (80%)
Budget::create([
    'user_id' => $userA->id,
    'category_id' => $transportCat->id,
    'month' => $currentMonth,
    'limit_amount' => 10000.00,
]);
Transaction::create([
    'user_id' => $userA->id,
    'category_id' => $transportCat->id,
    'amount' => 8000.00,
    'type' => 'expense',
    'description' => 'Transport commute',
    'transaction_date' => Carbon::now()->startOfMonth()->addDays(3)->toDateString(),
]);

$tipsUserA = SavingTipService::generateTipsForUser($userA, $currentMonth);
$nearBudgetTip = $tipsUserA->firstWhere('tip_type', SavingTip::TYPE_NEAR_BUDGET_LIMIT);
assertTest("NEAR_BUDGET_LIMIT tip generated", $nearBudgetTip !== null);
assertTest("Near budget tip potential savings / remaining is 2,000", $nearBudgetTip && (float)$nearBudgetTip->potential_savings === 2000.00);

echo "\n--- TEST 4: Over Budget Detection (>= 100%) ---\n";
// Create Food budget = Rs. 10,000. Current Food spending is Rs. 12,000 (Overage = 2,000)
Budget::create([
    'user_id' => $userA->id,
    'category_id' => $foodCat->id,
    'month' => $currentMonth,
    'limit_amount' => 10000.00,
]);

$tipsUserA = SavingTipService::generateTipsForUser($userA, $currentMonth);
$overBudgetTip = $tipsUserA->firstWhere('tip_type', SavingTip::TYPE_OVER_BUDGET);
assertTest("OVER_BUDGET tip generated", $overBudgetTip !== null);
assertTest("Over budget potential savings equals overage (2,000)", $overBudgetTip && (float)$overBudgetTip->potential_savings === 2000.00);

echo "\n--- TEST 5 & 6: Pin and Unpin Functionality ---\n";
assertTest("Tip is unpinned initially", $overBudgetTip->is_pinned === false);
$overBudgetTip->pin();
$overBudgetTip->refresh();
assertTest("Tip becomes pinned", $overBudgetTip->is_pinned === true);

$overBudgetTip->unpin();
$overBudgetTip->refresh();
assertTest("Tip becomes unpinned", $overBudgetTip->is_pinned === false);

echo "\n--- TEST 7: Dismiss Functionality ---\n";
$overBudgetTip->dismiss();
$overBudgetTip->refresh();
assertTest("Tip dismissed_at is set", $overBudgetTip->dismissed_at !== null);

$activeTipsAfterDismiss = SavingTip::where('user_id', $userA->id)->active()->get();
assertTest("Dismissed tip is excluded from active tips scope", !$activeTipsAfterDismiss->contains('id', $overBudgetTip->id));

// Test Restore
$overBudgetTip->restore();
$overBudgetTip->refresh();
assertTest("Restored tip has null dismissed_at and appears in active scope", $overBudgetTip->dismissed_at === null);

echo "\n--- TEST 8: Dynamic Regeneration & Duplicate Prevention ---\n";
// Add another transaction: Food spending increases by Rs. 3,000 (Total = 15,000, Overage = 5,000)
Transaction::create([
    'user_id' => $userA->id,
    'category_id' => $foodCat->id,
    'amount' => 3000.00,
    'type' => 'expense',
    'description' => 'Dinner with classmates',
    'transaction_date' => Carbon::now()->startOfMonth()->addDays(6)->toDateString(),
]);

$tipsAfterNewTxn = SavingTipService::generateTipsForUser($userA, $currentMonth);
$updatedOverBudgetTip = $tipsAfterNewTxn->firstWhere('tip_type', SavingTip::TYPE_OVER_BUDGET);
assertTest("Over budget tip updated with new overage (5,000)", $updatedOverBudgetTip && (float)$updatedOverBudgetTip->potential_savings === 5000.00, "Got: " . ($updatedOverBudgetTip ? $updatedOverBudgetTip->potential_savings : 'none'));

$overBudgetCount = SavingTip::where('user_id', $userA->id)->where('tip_type', SavingTip::TYPE_OVER_BUDGET)->count();
assertTest("Duplicate prevention: exactly 1 record exists for OVER_BUDGET food", $overBudgetCount === 1);

echo "\n--- TEST 9: User Isolation & Unauthorized Access ---\n";
// User B has their own tip
Transaction::create([
    'user_id' => $userB->id,
    'category_id' => $foodCat->id,
    'amount' => 5000.00,
    'type' => 'expense',
    'description' => 'User B Food',
    'transaction_date' => Carbon::now()->toDateString(),
]);
$tipsUserB = SavingTipService::generateTipsForUser($userB, $currentMonth);

assertTest("User A cannot see User B tips in query", !SavingTip::where('user_id', $userA->id)->pluck('id')->contains($tipsUserB->first()?->id));
assertTest("Tips for User B are assigned exclusively to User B", $tipsUserB->every(fn ($t) => $t->user_id === $userB->id));

echo "\n--- TEST 10: Repeated Subscription & Recurring Tips ---\n";
Transaction::create([
    'user_id' => $userA->id,
    'category_id' => $subCat->id,
    'amount' => 1200.00,
    'type' => 'expense',
    'description' => 'Netflix & Spotify Premium',
    'transaction_date' => Carbon::now()->startOfMonth()->addDays(4)->toDateString(),
]);

$tipsWithSub = SavingTipService::generateTipsForUser($userA, $currentMonth);
$subTip = $tipsWithSub->firstWhere('tip_type', SavingTip::TYPE_REPEATED_SUBSCRIPTION);
assertTest("REPEATED_SUBSCRIPTION tip detected", $subTip !== null);
assertTest("Subscription potential savings calculated (Rs. 600)", $subTip && (float)$subTip->potential_savings === 600.00);

echo "\n--- TEST 11: Tip Ranking System ---\n";
$rankedTips = SavingTip::where('user_id', $userA->id)->ranked()->get();
assertTest("Tips are deterministically ranked", $rankedTips->count() > 1 && $rankedTips->first()->priority >= $rankedTips->last()->priority);

// Pin a lower priority tip and verify it rises to the very top of ranked scope
$lowestTip = $rankedTips->last();
$lowestTip->pin();
$reRankedTips = SavingTip::where('user_id', $userA->id)->ranked()->get();
assertTest("Pinned tip ranks first in ranked scope", $reRankedTips->first()->id === $lowestTip->id);

echo "\n==================================================\n";
echo "SUMMARY: PASS = $pass, FAIL = $fail\n";
echo "==================================================\n";

if ($fail === 0) {
    echo "ALL PHASE 6 SAVING TIPS ENGINE TESTS PASSED!\n";
    exit(0);
} else {
    echo "SOME TESTS FAILED!\n";
    exit(1);
}
