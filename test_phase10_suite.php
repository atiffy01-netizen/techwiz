<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\Budget;
use App\Models\SavingTip;
use App\Models\AiMonthlyInsight;
use App\Models\AdminTipTemplate;
use App\Models\TransactionBookmark;
use App\Models\TransactionNote;
use App\Models\TransactionShare;
use App\Services\BookmarkService;
use App\Services\TransactionNoteService;
use App\Services\TransactionShareService;
use App\Services\TransactionActivityService;
use App\Services\DuplicateDetectionService;
use App\Services\SpendingForecastService;
use App\Services\SavingTipService;
use App\Services\Ai\AiCategorizationService;
use App\Services\Ai\AiInsightService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Carbon\Carbon;

echo "====================================================\n";
echo "    CAMPUS COIN — PHASE 10 QA & E2E MASTER SUITE    \n";
echo "====================================================\n\n";

$pass = 0;
$fail = 0;

function check($condition, $description) {
    global $pass, $fail;
    if ($condition) {
        echo "[\033[32mPASS\033[0m] {$description}\n";
        $pass++;
    } else {
        echo "[\033[31mFAIL\033[0m] {$description}\n";
        $fail++;
    }
}

// ─────────────────────────────────────────────
// 1. Database Schema & Migration Health
// ─────────────────────────────────────────────
echo "--- TEST 1: Database Schema & Migration Health ---\n";
$tables = [
    'users', 'categories', 'transactions', 'budgets', 'notifications',
    'saving_tips', 'ai_monthly_insights', 'admin_tip_templates',
    'transaction_bookmarks', 'transaction_notes', 'transaction_shares',
    'transaction_activities'
];
foreach ($tables as $t) {
    check(DB::getSchemaBuilder()->hasTable($t), "Table '{$t}' exists in database");
}

// ─────────────────────────────────────────────
// 2. Demo Accounts & Role Verification
// ─────────────────────────────────────────────
echo "\n--- TEST 2: Demo Accounts & Role Verification ---\n";
$admin = User::where('email', 'admin@campuscoin.edu')->first();
$student = User::where('email', 'hunzala@campuscoin.edu')->first();
$studentB = User::where('email', 'ayesha@campuscoin.edu')->first();

check($admin !== null, "Admin account (admin@campuscoin.edu) exists");
check($admin && $admin->isAdmin(), "Admin has isAdmin() == true");
check($student !== null, "Student A (hunzala@campuscoin.edu) exists");
check($student && !$student->isAdmin() && $student->isUser(), "Student A has isUser() == true");
check($studentB !== null, "Student B (ayesha@campuscoin.edu) exists");
check($student && Hash::check('password123', $student->password), "Student password verifies with Hash::check");

// ─────────────────────────────────────────────
// 3. User Journey: Transactions & Real Balances
// ─────────────────────────────────────────────
echo "\n--- TEST 3: User Journey - Transactions & Balance Accuracy ---\n";
DB::beginTransaction();
try {
    $initialIncome = (float) Transaction::where('user_id', $student->id)->where('type', 'income')->sum('amount');
    $initialExpense = (float) Transaction::where('user_id', $student->id)->where('type', 'expense')->sum('amount');

    $catAllowance = Category::where('name', 'Allowance')->where('type', 'income')->first();
    $catFood = Category::where('name', 'Food')->where('type', 'expense')->first();

    $newIncome = Transaction::create([
        'user_id' => $student->id,
        'category_id' => $catAllowance ? $catAllowance->id : null,
        'type' => 'income',
        'amount' => 5000.00,
        'description' => 'Tutoring Stipend',
        'transaction_date' => Carbon::now()->toDateString(),
    ]);

    $newExpense = Transaction::create([
        'user_id' => $student->id,
        'category_id' => $catFood ? $catFood->id : null,
        'type' => 'expense',
        'amount' => 750.00,
        'description' => 'Campus Lunch with Friends',
        'transaction_date' => Carbon::now()->toDateString(),
    ]);

    $afterIncome = (float) Transaction::where('user_id', $student->id)->where('type', 'income')->sum('amount');
    $afterExpense = (float) Transaction::where('user_id', $student->id)->where('type', 'expense')->sum('amount');

    check($afterIncome === $initialIncome + 5000.00, "Income addition accurately updates user total income");
    check($afterExpense === $initialExpense + 750.00, "Expense addition accurately updates user total expense");
    check(($afterIncome - $afterExpense) === ($initialIncome - $initialExpense + 4250.00), "Net balance calculated with 100% precision");
} finally {
    DB::rollBack();
}

// ─────────────────────────────────────────────
// 4. Budgets & Threshold Calculation
// ─────────────────────────────────────────────
echo "\n--- TEST 4: Budgets & Threshold Calculation ---\n";
DB::beginTransaction();
try {
    $testMonth = '2029-12';
    $cat = Category::where('name', 'Food')->where('type', 'expense')->first();
    
    $budget = Budget::updateOrCreate(
        [
            'user_id' => $student->id,
            'category_id' => $cat->id,
            'month' => $testMonth,
        ],
        [
            'limit_amount' => 1000.00,
        ]
    );

    Transaction::create([
        'user_id' => $student->id,
        'category_id' => $cat->id,
        'type' => 'expense',
        'amount' => 850.00,
        'description' => 'Groceries',
        'transaction_date' => '2026-09-10',
    ]);

    $nearStatus = $budget->getStatus(850.00);
    check($nearStatus === Budget::STATUS_NEAR_LIMIT, "Spending at 85% correctly triggers STATUS_NEAR_LIMIT (75-99%)");

    $overStatus = $budget->getStatus(1100.00);
    check($overStatus === Budget::STATUS_OVER_BUDGET, "Spending at 110% correctly triggers STATUS_OVER_BUDGET");
} finally {
    DB::rollBack();
}

// ─────────────────────────────────────────────
// 5. Saving Tips Engine Integration
// ─────────────────────────────────────────────
echo "\n--- TEST 5: Saving Tips Engine Integration ---\n";
$tipsRes = SavingTipService::generateTipsForUser($student, date('Y-m'));
check($tipsRes instanceof \Illuminate\Support\Collection, "SavingTipService returns Collection of active tips");
$activeTips = SavingTip::where('user_id', $student->id)->active()->get();
check($activeTips !== null, "Active saving tips retrieved without query exceptions");

// ─────────────────────────────────────────────
// 6. AI Categorization & Insights
// ─────────────────────────────────────────────
echo "\n--- TEST 6: AI Advisory Categorization & Monthly Insights ---\n";
$aiRes = AiCategorizationService::suggestCategory($student, "Hostel Room Monthly Fee", 'expense');
check($aiRes['success'] === true, "AI Categorization returns success=true");
check(isset($aiRes['suggestion']), "AI Categorization returns suggestion payload (Advisory only)");

$insightRes = AiInsightService::getOrCreateInsight($student, date('Y-m'));
check($insightRes instanceof AiMonthlyInsight, "AI Monthly Insight generated and returned as AiMonthlyInsight");
check(is_array($insightRes->highlights), "AI Insight includes actionable highlights array");

// ─────────────────────────────────────────────
// 7. Phase 9 Intelligence Features
// ─────────────────────────────────────────────
echo "\n--- TEST 7: Phase 9 Advanced Features ---\n";
DB::beginTransaction();
try {
    $txn = Transaction::where('user_id', $student->id)->first();
    if ($txn) {
        // Bookmarks
        $bRes1 = BookmarkService::toggle($student, $txn->id);
        check(BookmarkService::isBookmarked($student, $txn->id) === true, "Bookmark toggle creates bookmark");
        $bRes2 = BookmarkService::toggle($student, $txn->id);
        check(BookmarkService::isBookmarked($student, $txn->id) === false, "Second toggle removes bookmark");

        // Notes
        TransactionNoteService::saveNote($student, $txn->id, "Important receipt for fee reimbursement");
        $savedNote = TransactionNoteService::getNote($student, $txn->id);
        check($savedNote && $savedNote->note === "Important receipt for fee reimbursement", "Transaction note saved and verified");

        // Sharing
        $share = TransactionShareService::createShare($student, $txn->id, 7);
        check($share && strlen($share->token) === 48, "Transaction share link generates secure 48-char token");
        $publicData = TransactionShareService::getValidSharedTransaction($share->token);
        check(is_array($publicData) && isset($publicData['formatted_amount']), "Public share data accessible without credentials");

        // Duplicate Detection
        $dupCheck = DuplicateDetectionService::checkForDuplicates(
            $student,
            (float) $txn->amount,
            $txn->category_id,
            $txn->type,
            $txn->transaction_date ? $txn->transaction_date->toDateString() : null,
            $txn->description
        );
        check($dupCheck['is_duplicate'] === true, "Duplicate detection detects exact existing transaction match");

        // Forecast
        $forecast = SpendingForecastService::generateForecast($student, date('Y-m'));
        check(isset($forecast['upcoming_month']), "Upcoming month forecast calculated");
    }
} finally {
    DB::rollBack();
}

// ─────────────────────────────────────────────
// 8. Admin Security & User Isolation
// ─────────────────────────────────────────────
echo "\n--- TEST 8: Admin Security & Strict User Isolation ---\n";
Auth::login($student);
$middleware = new \App\Http\Middleware\AdminMiddleware();
$req = Request::create('/admin', 'GET');
$blocked = false;
try {
    $resp = $middleware->handle($req, function() { return response('admin-passed'); });
    if ($resp->getStatusCode() === 403) {
        $blocked = true;
    }
} catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
    if ($e->getStatusCode() === 403) {
        $blocked = true;
    }
}
check($blocked, "Non-admin student receives 403 Forbidden from AdminMiddleware");

Auth::login($admin);
$respAdmin = $middleware->handle($req, function() { return response('admin-passed'); });
check($respAdmin->getContent() === 'admin-passed', "Administrator passes AdminMiddleware successfully");

// Cross-user access check
DB::beginTransaction();
try {
    $anyCat = Category::where('type', 'expense')->first();
    $txnA = Transaction::create([
        'user_id' => $student->id,
        'category_id' => $anyCat ? $anyCat->id : 1,
        'type' => 'expense',
        'amount' => 50.00,
        'description' => 'Private Item',
        'transaction_date' => Carbon::now()->toDateString(),
    ]);

    TransactionNoteService::saveNote($student, $txnA->id, "Top secret personal memo");

    $unauthorizedCaught = false;
    try {
        TransactionNoteService::getNote($studentB, $txnA->id);
    } catch (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e) {
        $unauthorizedCaught = true;
    }

    check($unauthorizedCaught, "User B cannot access User A private notes (AccessDeniedHttpException thrown)");
} finally {
    DB::rollBack();
}

// ─────────────────────────────────────────────
// 9. Production Route & Performance Verification
// ─────────────────────────────────────────────
echo "\n--- TEST 9: Production Route & Performance Verification ---\n";
$keyRoutes = [
    'home', 'login', 'register', 'dashboard', 'transactions',
    'categories', 'budgets', 'reports', 'saving-tips', 'insights',
    'forecast', 'bookmarks', 'profile', 'settings',
    'admin.dashboard', 'admin.users.index', 'admin.categories.index',
    'admin.tip-templates.index', 'admin.statistics.index'
];

foreach ($keyRoutes as $rn) {
    check(Route::has($rn), "Named route '{$rn}' is registered and resolvable");
}

// ─────────────────────────────────────────────
// SUMMARY
// ─────────────────────────────────────────────
echo "\n" . str_repeat('=', 52) . "\n";
echo "SUMMARY: PASS = {$pass}, FAIL = {$fail}\n";
echo str_repeat('=', 52) . "\n";

if ($fail === 0) {
    echo "ALL PHASE 10 MASTER QA & E2E TESTS PASSED (100% SUCCESS)!\n\n";
    exit(0);
} else {
    echo "SOME TESTS FAILED. Review log above.\n\n";
    exit(1);
}
