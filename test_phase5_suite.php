<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\Budget;
use App\Services\ReportAnalyticsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

echo "=== CAMPUS COIN PHASE 5 VERIFICATION SUITE ===\n\n";

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

// 1. Setup Test Users
$userA = User::firstOrCreate(
    ['email' => 'hunzala@campuscoin.edu'],
    ['name' => 'Hunzala Khan', 'password' => Hash::make('password123'), 'monthly_allowance' => 35000]
);
$userB = User::firstOrCreate(
    ['email' => 'ayesha@campuscoin.edu'],
    ['name' => 'Ayesha Khan', 'password' => Hash::make('password123'), 'monthly_allowance' => 28000]
);

assertTest("User A exists (Hunzala)", $userA->id > 0);
assertTest("User B exists (Ayesha)", $userB->id > 0);

// 2. Setup Categories
$foodCat = Category::where('name', 'Food')->where('type', 'expense')->first();
$transportCat = Category::where('name', 'Transport')->where('type', 'expense')->first();
$academicsCat = Category::where('name', 'Academics')->where('type', 'expense')->first();
$entertainmentCat = Category::where('name', 'Entertainment')->where('type', 'expense')->first();
$allowanceCat = Category::where('name', 'Allowance')->where('type', 'income')->first();
$jobCat = Category::where('name', 'Part-time Job')->where('type', 'income')->first();

assertTest("Expense & Income default categories exist", $foodCat && $transportCat && $allowanceCat);

// Clean previous test data for User A and User B
Transaction::whereIn('user_id', [$userA->id, $userB->id])->delete();
Budget::whereIn('user_id', [$userA->id, $userB->id])->delete();

$now = Carbon::now();
$currentMonthKey = $now->format('Y-m');
$prevMonthKey = $now->copy()->subMonth()->format('Y-m');

// 3. Seed Realistic Multi-Month Data for User A
echo "\n--- Seeding 6 Months Data for User A ---\n";

// Current Month (e.g. Month 0)
Transaction::create([
    'user_id' => $userA->id,
    'category_id' => $allowanceCat->id,
    'amount' => 40000.00,
    'type' => 'income',
    'description' => 'Monthly Allowance from Parents',
    'transaction_date' => $now->copy()->startOfMonth()->addDays(1)->toDateString(),
]);
Transaction::create([
    'user_id' => $userA->id,
    'category_id' => $foodCat->id,
    'amount' => 8450.00,
    'type' => 'expense',
    'description' => 'Campus Cafeteria & Groceries',
    'transaction_date' => $now->copy()->startOfMonth()->addDays(5)->toDateString(),
]);
Transaction::create([
    'user_id' => $userA->id,
    'category_id' => $transportCat->id,
    'amount' => 3200.00,
    'type' => 'expense',
    'description' => 'Monthly Metro Card Reload',
    'transaction_date' => $now->copy()->startOfMonth()->addDays(8)->toDateString(),
]);
Transaction::create([
    'user_id' => $userA->id,
    'category_id' => $entertainmentCat ? $entertainmentCat->id : $foodCat->id,
    'amount' => 2850.00,
    'type' => 'expense',
    'description' => 'Weekend Outing with Friends',
    'transaction_date' => $now->copy()->startOfMonth()->addDays(14)->toDateString(),
]);

// Previous Month (Month -1)
$prevDate = $now->copy()->subMonth();
Transaction::create([
    'user_id' => $userA->id,
    'category_id' => $allowanceCat->id,
    'amount' => 40000.00,
    'type' => 'income',
    'description' => 'Monthly Allowance (Prev)',
    'transaction_date' => $prevDate->copy()->startOfMonth()->addDays(1)->toDateString(),
]);
Transaction::create([
    'user_id' => $userA->id,
    'category_id' => $foodCat->id,
    'amount' => 12000.00,
    'type' => 'expense',
    'description' => 'Food & Dining (Prev)',
    'transaction_date' => $prevDate->copy()->startOfMonth()->addDays(10)->toDateString(),
]);
Transaction::create([
    'user_id' => $userA->id,
    'category_id' => $transportCat->id,
    'amount' => 5000.00,
    'type' => 'expense',
    'description' => 'Transport (Prev)',
    'transaction_date' => $prevDate->copy()->startOfMonth()->addDays(12)->toDateString(),
]);

// Month -2
$m2Date = $now->copy()->subMonths(2);
Transaction::create([
    'user_id' => $userA->id,
    'category_id' => $allowanceCat->id,
    'amount' => 35000.00,
    'type' => 'income',
    'description' => 'Allowance (M-2)',
    'transaction_date' => $m2Date->copy()->startOfMonth()->addDays(1)->toDateString(),
]);
Transaction::create([
    'user_id' => $userA->id,
    'category_id' => $foodCat->id,
    'amount' => 7500.00,
    'type' => 'expense',
    'description' => 'Food (M-2)',
    'transaction_date' => $m2Date->copy()->startOfMonth()->addDays(15)->toDateString(),
]);

// Seed Data for User B (User Isolation Check)
Transaction::create([
    'user_id' => $userB->id,
    'category_id' => $allowanceCat->id,
    'amount' => 77777.00,
    'type' => 'income',
    'description' => 'User B Exclusive Income',
    'transaction_date' => $now->copy()->startOfMonth()->addDays(2)->toDateString(),
]);
Transaction::create([
    'user_id' => $userB->id,
    'category_id' => $foodCat->id,
    'amount' => 9999.00,
    'type' => 'expense',
    'description' => 'User B Exclusive Expense',
    'transaction_date' => $now->copy()->startOfMonth()->addDays(3)->toDateString(),
]);

// Seed Phase 4 Budgets for User A
Budget::create([
    'user_id' => $userA->id,
    'category_id' => $foodCat->id,
    'month' => $currentMonthKey,
    'limit_amount' => 10000.00,
]);
Budget::create([
    'user_id' => $userA->id,
    'category_id' => $transportCat->id,
    'month' => $currentMonthKey,
    'limit_amount' => 3500.00,
]);

// 4. Test Report Analytics Service
echo "\n--- Testing Report Analytics Service ---\n";
$service = new ReportAnalyticsService();

$reportA = $service->getReportData($userA, ['month' => $currentMonthKey]);

assertTest("Report generated successfully for User A", is_array($reportA));
assertTest("User A Total Income is 40,000.00", (float) $reportA['totalIncome'] === 40000.00);
$expectedExpenseA = 8450.00 + 3200.00 + 2850.00; // 14,500.00
assertTest("User A Total Expenses is 14,500.00", (float) $reportA['totalExpense'] === $expectedExpenseA);
assertTest("User A Net Balance is 25,500.00", (float) $reportA['netBalance'] === (40000.00 - $expectedExpenseA));
assertTest("User A Total Transactions count is 4", $reportA['totalTransactionsCount'] === 4);

// 5. Test User Isolation
echo "\n--- Testing User Isolation ---\n";
$reportB = $service->getReportData($userB, ['month' => $currentMonthKey]);
assertTest("User B Income is 77,777.00 (Isolated from A)", (float) $reportB['totalIncome'] === 77777.00);
assertTest("User B Expense is 9,999.00 (Isolated from A)", (float) $reportB['totalExpense'] === 9999.00);
assertTest("User A does NOT see User B data in total income", (float) $reportA['totalIncome'] !== 77777.00);

// 6. Test Category Breakdown
echo "\n--- Testing Category-Wise Spending Breakdown ---\n";
$catBreakdown = $reportA['categoryBreakdown'];
assertTest("Category breakdown contains categories", $catBreakdown->count() >= 2);
$foodItem = $catBreakdown->firstWhere('category_id', $foodCat->id);
assertTest("Food category amount is 8,450.00", $foodItem && (float) $foodItem['amount'] === 8450.00);
$expectedFoodPct = round((8450.00 / $expectedExpenseA) * 100, 1);
assertTest("Food category percentage calculated correctly ({$expectedFoodPct}%)", $foodItem && $foodItem['percentage'] === $expectedFoodPct);
assertTest("Top category is Food", $reportA['topCategory'] && $reportA['topCategory']['name'] === 'Food');

// 7. Test 6-Month Consecutive Financial Trend
echo "\n--- Testing 6-Month Consecutive Financial Trend ---\n";
$trend = $reportA['sixMonthTrend'];
assertTest("6-Month Trend has exactly 6 consecutive labels", count($trend['labels']) === 6);
assertTest("6-Month Trend has exactly 6 income entries", count($trend['income']) === 6);
assertTest("6-Month Trend has exactly 6 expense entries", count($trend['expense']) === 6);
// The last item in 6-month trend is current month
$lastIncome = end($trend['income']);
$lastExpense = end($trend['expense']);
assertTest("Current month income in trend is 40,000.00", (float) $lastIncome === 40000.00);
assertTest("Current month expense in trend is 14,500.00", (float) $lastExpense === $expectedExpenseA);

// 8. Test Daily & Weekly Spending Calculations
echo "\n--- Testing Daily & Weekly Spending Summaries ---\n";
$daily = $reportA['dailySpending'];
assertTest("Daily spending contains points for all days in month", count($daily['days']) === $reportA['totalDays']);
assertTest("Highest spending day identified", $reportA['highestSpendingDay'] !== null);
assertTest("Highest spending day amount is 8,450.00", (float) $reportA['highestSpendingDay']['amount'] === 8450.00);

$weekly = $reportA['weeklySpending'];
assertTest("Weekly spending has 5 week slots", count($weekly['labels']) === 5);
assertTest("Weekly spending detailed contains amounts", count($weekly['detailed']) === 5);

// 9. Test Month-over-Month Comparison
echo "\n--- Testing Month-over-Month Comparison ---\n";
$mom = $reportA['monthOverMonth'];
assertTest("MoM has previous month data", $mom['has_data'] === true);
// Prev expense = 12000 + 5000 = 17000. Current expense = 14500. Diff = 14500 - 17000 = -2500.
$expectedDiff = 14500.00 - 17000.00;
assertTest("MoM expense difference is -2,500.00", (float) $mom['diff_expense'] === $expectedDiff);
assertTest("MoM correctly flags expense drop (is_lower = true)", $mom['is_lower'] === true);

// 10. Test Budget Performance Context
echo "\n--- Testing Phase 4 Budget Performance Context ---\n";
$budgets = $reportA['budgetComparison'];
assertTest("Budget comparison has active budgets", $budgets['has_budgets'] === true);
assertTest("Budget items count is 2", count($budgets['items']) === 2);
$foodBudget = collect($budgets['items'])->firstWhere('category_name', 'Food');
assertTest("Food budget limit is 10,000.00 and spent is 8,450.00", $foodBudget && (float)$foodBudget['limit_amount'] === 10000.00 && (float)$foodBudget['spent_amount'] === 8450.00);
assertTest("Food budget usage is 84.5% (Near Limit)", $foodBudget && $foodBudget['usage_percent'] === 84.5 && $foodBudget['status'] === 'near_limit');

// 11. Test Custom Date Range & Inverted Date Handling
echo "\n--- Testing Custom Date Range Filtering ---\n";
$customStart = $now->copy()->startOfMonth()->addDays(4)->toDateString();
$customEnd = $now->copy()->startOfMonth()->addDays(9)->toDateString();
$customReport = $service->getReportData($userA, [
    'period_type' => 'custom',
    'start_date' => $customStart,
    'end_date' => $customEnd,
]);
assertTest("Custom date range filter applied", $customReport['periodType'] === 'custom');
// Within days 4..9: Food (8450) on day 5 and Transport (3200) on day 8 = 11,650
assertTest("Custom range expense is 11,650.00", (float) $customReport['totalExpense'] === (8450.00 + 3200.00));

// Test Inverted Date Handling (Start > End)
$invertedReport = $service->getReportData($userA, [
    'period_type' => 'custom',
    'start_date' => $customEnd,
    'end_date' => $customStart,
]);
assertTest("Inverted dates handled gracefully by swapping", $invertedReport['startDate'] === $customStart && $invertedReport['endDate'] === $customEnd);

// 12. Test PDF Generation
echo "\n--- Testing PDF Export Generation ---\n";
$transactions = $reportA['txQuery']->get();
$viewData = array_merge($reportA, [
    'user' => $userA,
    'transactions' => $transactions,
    'generatedAt' => now()->format('F d, Y - h:i A'),
]);

if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
    $pdf = Pdf::loadView('reports.pdf', $viewData);
    $pdfOutput = $pdf->output();
} else {
    $dompdf = new \Dompdf\Dompdf(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);
    $html = view('reports.pdf', $viewData)->render();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('a4', 'portrait');
    $dompdf->render();
    $pdfOutput = $dompdf->output();
}
assertTest("PDF output generated successfully and is not empty", !empty($pdfOutput));
assertTest("PDF output starts with '%PDF-' header", str_starts_with($pdfOutput, '%PDF-'));

// 13. Test Empty State Handling
echo "\n--- Testing Empty State Report ---\n";
$emptyUser = User::firstOrCreate(
    ['email' => 'newstudent@campuscoin.edu'],
    ['name' => 'New Student', 'password' => Hash::make('password123')]
);
Transaction::where('user_id', $emptyUser->id)->delete();
$emptyReport = $service->getReportData($emptyUser, ['month' => $currentMonthKey]);
assertTest("Empty user total transactions count is 0", $emptyReport['totalTransactionsCount'] === 0);
assertTest("Empty user total expense is 0", (float) $emptyReport['totalExpense'] === 0.0);
assertTest("Empty user average daily spending is 0", (float) $emptyReport['averageDailySpending'] === 0.0);
assertTest("Empty user MoM comparison reports no previous-period data", $emptyReport['monthOverMonth']['has_data'] === false);

echo "\n=== PHASE 5 VERIFICATION COMPLETED ===\n";
echo "Total Passed: $pass\n";
echo "Total Failed: $fail\n";

if ($fail === 0) {
    echo "RESULT: ALL TESTS PASSED (100% SUCCESS)\n";
} else {
    echo "RESULT: $fail TEST(S) FAILED\n";
}
