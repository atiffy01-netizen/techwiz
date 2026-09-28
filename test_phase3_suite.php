<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

echo "=== CAMPUS COIN PHASE 3 VERIFICATION SUITE ===\n\n";

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

// 1. Check Default Categories
$defaultCategories = Category::where('is_default', true)->get();
assertTest("Default categories exist in database", $defaultCategories->count() >= 12);

$foodCat = Category::where('name', 'Food')->where('type', 'expense')->first();
assertTest("Default 'Food' expense category exists", $foodCat !== null);

$allowanceCat = Category::where('name', 'Allowance')->where('type', 'income')->first();
assertTest("Default 'Allowance' income category exists", $allowanceCat !== null);

// 2. Setup Test Users
$userA = User::firstOrCreate(
    ['email' => 'hunzala@campuscoin.edu'],
    [
        'name' => 'Hunzala Khan',
        'password' => Hash::make('password123'),
        'monthly_allowance' => 35000,
    ]
);

$userB = User::firstOrCreate(
    ['email' => 'ayesha@campuscoin.edu'],
    [
        'name' => 'Ayesha Khan',
        'password' => Hash::make('password123'),
        'monthly_allowance' => 28000,
    ]
);

// Clean previous test transactions for clean slate
Transaction::whereIn('user_id', [$userA->id, $userB->id])->delete();
Category::whereIn('user_id', [$userA->id, $userB->id])->delete();

// 3. User A creates custom categories
$customCatA = Category::create([
    'user_id' => $userA->id,
    'name' => 'Campus Gym & Fitness',
    'type' => 'expense',
    'icon' => 'bi-heart-pulse',
    'is_default' => false,
]);
assertTest("User A can create a personal category", $customCatA->id > 0 && $customCatA->user_id === $userA->id);

$customIncomeA = Category::create([
    'user_id' => $userA->id,
    'name' => 'Freelance Web Dev',
    'type' => 'income',
    'icon' => 'bi-laptop',
    'is_default' => false,
]);
assertTest("User A can create a personal income category", $customIncomeA->id > 0 && $customIncomeA->type === 'income');

// 4. Test Category Scopes & User Isolation
$userACategories = Category::forUser($userA->id)->pluck('id');
$userBCategories = Category::forUser($userB->id)->pluck('id');

assertTest("User A sees personal category", $userACategories->contains($customCatA->id));
assertTest("User B DOES NOT see User A's personal category", !$userBCategories->contains($customCatA->id));

// 5. Create Transactions for User A
$tx1 = Transaction::create([
    'user_id' => $userA->id,
    'category_id' => $allowanceCat->id,
    'amount' => 25000.00,
    'type' => 'income',
    'description' => 'September Monthly Allowance',
    'transaction_date' => Carbon::now()->toDateString(),
    'payment_method' => 'Bank Transfer',
]);

$tx2 = Transaction::create([
    'user_id' => $userA->id,
    'category_id' => $foodCat->id,
    'amount' => 1500.00,
    'type' => 'expense',
    'description' => 'Campus Cafe Lunch with Friends',
    'transaction_date' => Carbon::now()->toDateString(),
    'payment_method' => 'JazzCash',
]);

$tx3 = Transaction::create([
    'user_id' => $userA->id,
    'category_id' => $customCatA->id,
    'amount' => 3000.00,
    'type' => 'expense',
    'description' => 'Semester Gym Pass',
    'transaction_date' => Carbon::now()->toDateString(),
    'payment_method' => 'Cash',
]);

// 6. Create Transactions for User B
$txB = Transaction::create([
    'user_id' => $userB->id,
    'category_id' => $foodCat->id,
    'amount' => 800.00,
    'type' => 'expense',
    'description' => 'Ayesha Canteen Snack',
    'transaction_date' => Carbon::now()->toDateString(),
    'payment_method' => 'Cash',
]);

// 7. Verify Data Isolation
$userATransactions = Transaction::where('user_id', $userA->id)->get();
$userBTransactions = Transaction::where('user_id', $userB->id)->get();

assertTest("User A has exactly 3 transactions", $userATransactions->count() === 3);
assertTest("User B has exactly 1 transaction", $userBTransactions->count() === 1);
assertTest("User A's query does not contain User B's transaction", !$userATransactions->pluck('id')->contains($txB->id));
assertTest("User B's query does not contain User A's transaction", !$userBTransactions->pluck('id')->contains($tx1->id));

// 8. Verify Balances
$balanceA = Transaction::where('user_id', $userA->id)->where('type', 'income')->sum('amount') 
          - Transaction::where('user_id', $userA->id)->where('type', 'expense')->sum('amount');
assertTest("User A balance calculation is accurate (25,000 - 4,500 = 20,500)", (float)$balanceA === 20500.00);

$balanceB = Transaction::where('user_id', $userB->id)->where('type', 'income')->sum('amount') 
          - Transaction::where('user_id', $userB->id)->where('type', 'expense')->sum('amount');
assertTest("User B balance calculation is accurate (0 - 800 = -800)", (float)$balanceB === -800.00);

// 9. Safety Deletion Test: Category with transactions cannot be deleted
$hasTxForCustomCat = Transaction::where('user_id', $userA->id)->where('category_id', $customCatA->id)->exists();
assertTest("Safety Check: Custom category Gym has active transactions", $hasTxForCustomCat === true);

// 10. Recurring Transactions Test
$recurringTx = Transaction::create([
    'user_id' => $userA->id,
    'category_id' => $foodCat->id,
    'amount' => 500.00,
    'type' => 'expense',
    'description' => 'Weekly Mess Subscription',
    'transaction_date' => Carbon::now()->subDays(10)->toDateString(),
    'is_recurring' => true,
    'recurring_frequency' => 'weekly',
    'recurring_start_date' => Carbon::now()->subDays(10)->toDateString(),
]);

$initialCount = Transaction::where('user_id', $userA->id)->count();
Artisan::call('campuscoin:process-recurring');
$afterCount = Transaction::where('user_id', $userA->id)->count();

assertTest("Recurring transaction generator creates next due occurrence", $afterCount === $initialCount + 1);

// Running again immediately should NOT generate duplicate
Artisan::call('campuscoin:process-recurring');
$duplicateCheckCount = Transaction::where('user_id', $userA->id)->count();
assertTest("Recurring transaction generator is deterministic (no duplicates created)", $duplicateCheckCount === $afterCount);

echo "\n=== SUMMARY: {$pass} PASSED, {$fail} FAILED ===\n";
