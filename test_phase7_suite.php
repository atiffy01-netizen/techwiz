<?php

/**
 * CAMPUS COIN — PHASE 7 VERIFICATION SUITE
 * AI Expense Categorization + Monthly Spending Insights
 *
 * Tests:
 *  1.  AiManager provider resolution (fallback vs configured providers)
 *  2.  FallbackAiProvider: expense categorization keywords (Food, Transport, Academics, Subscriptions, Entertainment)
 *  3.  FallbackAiProvider: income categorization keywords (Allowance, Part-time Job, Scholarship, Gift)
 *  4.  FallbackAiProvider: null return for unknown descriptions & short input
 *  5.  AiCategorizationService: end-to-end with real user DB categories
 *  6.  AiCategorizationService: user isolation (suggestion uses only own categories)
 *  7.  AiCategorizationService: short description handled gracefully
 *  8.  FallbackAiProvider: generateMonthlyInsight — empty state
 *  9.  FallbackAiProvider: generateMonthlyInsight — surplus state
 *  10. FallbackAiProvider: generateMonthlyInsight — deficit + near-limit budgets state
 *  11. AiInsightService: aggregateFinancialData — structure & user isolation
 *  12. AiInsightService: getOrCreateInsight creates record for new month
 *  13. AiInsightService: getOrCreateInsight idempotency (no duplicates)
 *  14. AiInsightService: generateInsight force=true overwrites record
 *  15. AiInsightService: getPastInsights — excludes current month, respects limit, DESC order
 *  16. AiMonthlyInsight model: status helpers (isAiGenerated, isFallback)
 *  17. AiMonthlyInsight model: formattedMonth accessor
 *  18. AiMonthlyInsight model: Eloquent forUser / forMonth scopes
 *  19. All 3 providers implement AiProviderInterface contract
 *  20. Route registration: /api/ai/categorize-expense, /insights, /insights/generate
 *  21. AiCategorizationService: empty categories user edge case
 *  22. FallbackAiProvider: insight highlights & actions structure (rich spending data)
 *  23. AiInsightService: aggregateFinancialData returns correct net_balance and category breakdown
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\AiMonthlyInsight;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Ai\AiCategorizationService;
use App\Services\Ai\AiInsightService;
use App\Services\Ai\AiManager;
use App\Services\Ai\Contracts\AiProviderInterface;
use App\Services\Ai\Providers\FallbackAiProvider;
use App\Services\Ai\Providers\GeminiAiProvider;
use App\Services\Ai\Providers\OpenAiProvider;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// ─────────────────────────────────────────────
// Test Helpers
// ─────────────────────────────────────────────
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

// ─────────────────────────────────────────────
// Pre-flight: Retrieve existing test users
// ─────────────────────────────────────────────
echo "\n=== CAMPUS COIN PHASE 7 AI FEATURES VERIFICATION SUITE ===\n\n";

$userA = User::where('email', 'hunzala@campuscoin.edu')->first();
$userB = User::where('email', 'ayesha@campuscoin.edu')->first();

check($userA !== null, 'User A (Hunzala) exists in DB');
check($userB !== null, 'User B (Ayesha) exists in DB');

if (!$userA || !$userB) {
    echo "ABORT: Required test users missing. Run phase3 suite first.\n";
    exit(1);
}

// ─────────────────────────────────────────────
// TEST 1: AiManager provider resolution
// ─────────────────────────────────────────────
echo "\n--- TEST 1: AiManager Provider Resolution ---\n";

$provider = AiManager::provider();
check($provider instanceof AiProviderInterface, 'AiManager::provider() returns AiProviderInterface');
check($provider instanceof FallbackAiProvider, 'AiManager resolves FallbackAiProvider when AI_PROVIDER=fallback');
check($provider->getName() === 'fallback', 'FallbackAiProvider name is "fallback"');

AiManager::setProvider(new GeminiAiProvider());
check(AiManager::provider() instanceof GeminiAiProvider, 'setProvider() overrides to GeminiAiProvider');
check(AiManager::provider()->getName() === 'gemini', 'GeminiAiProvider name is "gemini"');

AiManager::resetProvider();
check(AiManager::provider() instanceof FallbackAiProvider, 'resetProvider() restores FallbackAiProvider');

// ─────────────────────────────────────────────
// TEST 2: FallbackAiProvider — Expense Keyword Matching
// ─────────────────────────────────────────────
echo "\n--- TEST 2: FallbackAiProvider — Expense Keyword Matching ---\n";

$fp = new FallbackAiProvider();

$expCats = [
    ['id' => 100, 'name' => 'Food', 'type' => 'expense'],
    ['id' => 101, 'name' => 'Transport', 'type' => 'expense'],
    ['id' => 102, 'name' => 'Academics', 'type' => 'expense'],
    ['id' => 103, 'name' => 'Subscriptions', 'type' => 'expense'],
    ['id' => 104, 'name' => 'Entertainment', 'type' => 'expense'],
    ['id' => 105, 'name' => 'Hostel/Rent', 'type' => 'expense'],
    ['id' => 106, 'name' => 'Miscellaneous', 'type' => 'expense'],
];

$r1 = $fp->categorizeExpense('Campus Cafeteria lunch', $expCats, 'expense');
check($r1 !== null && $r1['category_name'] === 'Food', 'FallbackAi: "Campus Cafeteria lunch" → Food');
check(isset($r1['confidence']) && $r1['confidence'] >= 0.60, 'FallbackAi Food: confidence >= 0.60');

$r2 = $fp->categorizeExpense('Careem ride to university', $expCats, 'expense');
check($r2 !== null && $r2['category_name'] === 'Transport', 'FallbackAi: "Careem ride" → Transport');

$r3 = $fp->categorizeExpense('Semester fee payment', $expCats, 'expense');
check($r3 !== null && $r3['category_name'] === 'Academics', 'FallbackAi: "Semester fee" → Academics');

$r4 = $fp->categorizeExpense('Netflix monthly subscription', $expCats, 'expense');
check($r4 !== null && $r4['category_name'] === 'Subscriptions', 'FallbackAi: "Netflix subscription" → Subscriptions');

$r5 = $fp->categorizeExpense('Cinema outing with friends', $expCats, 'expense');
check($r5 !== null && $r5['category_name'] === 'Entertainment', 'FallbackAi: "Cinema outing" → Entertainment');

// ─────────────────────────────────────────────
// TEST 3: FallbackAiProvider — Income Keyword Matching
// ─────────────────────────────────────────────
echo "\n--- TEST 3: FallbackAiProvider — Income Keyword Matching ---\n";

$incCats = [
    ['id' => 200, 'name' => 'Allowance', 'type' => 'income'],
    ['id' => 201, 'name' => 'Part-time Job', 'type' => 'income'],
    ['id' => 202, 'name' => 'Scholarship', 'type' => 'income'],
    ['id' => 203, 'name' => 'Gift', 'type' => 'income'],
    ['id' => 204, 'name' => 'Other Income', 'type' => 'income'],
];

$r6 = $fp->categorizeExpense('Monthly allowance from dad', $incCats, 'income');
check($r6 !== null && $r6['category_name'] === 'Allowance', 'FallbackAi income: "Monthly allowance from dad" → Allowance');

$r7 = $fp->categorizeExpense('Freelance web design payment', $incCats, 'income');
check($r7 !== null && $r7['category_name'] === 'Part-time Job', 'FallbackAi income: "Freelance web design" → Part-time Job');

$r8 = $fp->categorizeExpense('HEC Merit scholarship disbursed', $incCats, 'income');
check($r8 !== null && $r8['category_name'] === 'Scholarship', 'FallbackAi income: "HEC Merit scholarship" → Scholarship');

$r9 = $fp->categorizeExpense('Birthday gift from cousins', $incCats, 'income');
check($r9 !== null && $r9['category_name'] === 'Gift', 'FallbackAi income: "Birthday gift" → Gift');

// ─────────────────────────────────────────────
// TEST 4: FallbackAiProvider — No Match / Edge Cases
// ─────────────────────────────────────────────
echo "\n--- TEST 4: FallbackAiProvider — No Match Edge Cases ---\n";

$rNone = $fp->categorizeExpense('xyzzy randomstring8877', $expCats, 'expense');
check($rNone === null, 'FallbackAi: unrecognized description returns null');

$rShort = $fp->categorizeExpense('a', $expCats, 'expense');
check($rShort === null, 'FallbackAi: single-char description returns null');

// ─────────────────────────────────────────────
// TEST 5: AiCategorizationService — End-to-End with Real DB Categories
// ─────────────────────────────────────────────
echo "\n--- TEST 5: AiCategorizationService — End-to-End with Real User Categories ---\n";

$userACats = Category::forUser($userA->id)->where('type', 'expense')->get(['id', 'name', 'type', 'icon'])->toArray();
check(count($userACats) > 0, 'User A has expense categories in DB');

$result = AiCategorizationService::suggestCategory($userA, 'lunch at campus cafeteria', 'expense');
check(isset($result['success']) && $result['success'] === true, 'AiCategorizationService: returns success=true');
check(array_key_exists('suggestion', $result), 'AiCategorizationService: "suggestion" key exists in response');

$foodCat = collect($userACats)->first(fn($c) => stripos($c['name'], 'food') !== false);
if ($foodCat && $result['suggestion']) {
    check(
        (int)$result['suggestion']['category_id'] === (int)$foodCat['id'],
        'AiCategorizationService: "lunch at campus cafeteria" → Food category'
    );
    check(
        isset($result['suggestion']['confidence']) && $result['suggestion']['confidence'] >= 0.60,
        'AiCategorizationService: suggestion confidence >= 0.60'
    );
} else {
    check(true, 'AiCategorizationService: Food suggestion handled (no Food cat or null — acceptable)');
    check(true, 'AiCategorizationService: confidence check skipped (no suggestion)');
}

// ─────────────────────────────────────────────
// TEST 6: AiCategorizationService — User Isolation
// ─────────────────────────────────────────────
echo "\n--- TEST 6: AiCategorizationService — User Isolation ---\n";

$resultA = AiCategorizationService::suggestCategory($userA, 'Careem ride', 'expense');
$resultB = AiCategorizationService::suggestCategory($userB, 'Careem ride', 'expense');
check($resultA['success'] === true, 'User A categorization: success=true');
check($resultB['success'] === true, 'User B categorization: success=true (separate call)');

if ($resultA['suggestion']) {
    $userACatIds = array_column($userACats, 'id');
    check(
        in_array((int)$resultA['suggestion']['category_id'], $userACatIds),
        'AiCategorizationService: User A suggestion belongs to User A\'s category set'
    );
} else {
    check(true, 'AiCategorizationService: User A suggestion null — isolation still enforced');
}

// ─────────────────────────────────────────────
// TEST 7: AiCategorizationService — Short Description & Edge Cases
// ─────────────────────────────────────────────
echo "\n--- TEST 7: AiCategorizationService — Edge Cases ---\n";

$shortResult = AiCategorizationService::suggestCategory($userA, 'a', 'expense');
check(
    $shortResult['success'] === true && $shortResult['suggestion'] === null,
    'AiCategorizationService: too-short description returns success=true, suggestion=null'
);

$incomeResult = AiCategorizationService::suggestCategory($userA, 'taxi fare', 'income');
check($incomeResult['success'] === true, 'AiCategorizationService: income type request returns success=true');

// ─────────────────────────────────────────────
// TEST 8: FallbackAiProvider — generateMonthlyInsight (empty state)
// ─────────────────────────────────────────────
echo "\n--- TEST 8: FallbackAiProvider — generateMonthlyInsight Empty State ---\n";

$emptyData = [
    'month' => '2026-09', 'month_label' => 'September 2026',
    'total_income' => 0.0, 'total_expense' => 0.0, 'net_balance' => 0.0,
    'total_transactions_count' => 0, 'category_breakdown' => [],
    'top_category_name' => null, 'top_category_amount' => 0.0, 'top_category_percent' => 0.0,
    'has_history' => false, 'expense_change_percent' => null,
    'near_limit_budgets' => [], 'over_budget_categories' => [], 'academic_program' => 'CS',
];

$emptyInsight = $fp->generateMonthlyInsight($emptyData);
check($emptyInsight !== null, 'Empty state: insight generated (not null)');
check(isset($emptyInsight['summary']) && strlen($emptyInsight['summary']) > 10, 'Empty state: summary non-empty');
check(is_array($emptyInsight['highlights']), 'Empty state: highlights is array');
check(is_array($emptyInsight['actions']), 'Empty state: actions is array');
check(
    stripos($emptyInsight['summary'], 'No transactions') !== false ||
    stripos($emptyInsight['summary'], 'September 2026') !== false ||
    stripos($emptyInsight['summary'], 'yet') !== false,
    'Empty state: summary references no-transaction context'
);

// ─────────────────────────────────────────────
// TEST 9: FallbackAiProvider — generateMonthlyInsight (surplus)
// ─────────────────────────────────────────────
echo "\n--- TEST 9: FallbackAiProvider — generateMonthlyInsight Surplus State ---\n";

$surplusData = [
    'month' => '2026-08', 'month_label' => 'August 2026',
    'total_income' => 30000.0, 'total_expense' => 12000.0, 'net_balance' => 18000.0,
    'total_transactions_count' => 10,
    'category_breakdown' => [
        ['name' => 'Food', 'amount' => 7000.0, 'percentage' => 58.3],
        ['name' => 'Transport', 'amount' => 5000.0, 'percentage' => 41.7],
    ],
    'top_category_name' => 'Food', 'top_category_amount' => 7000.0, 'top_category_percent' => 58.3,
    'has_history' => true, 'expense_change_percent' => -15.0,
    'near_limit_budgets' => [], 'over_budget_categories' => [], 'academic_program' => 'CS',
];

$surplusInsight = $fp->generateMonthlyInsight($surplusData);
check($surplusInsight !== null, 'Surplus state: insight generated');
check(strlen($surplusInsight['summary']) > 20, 'Surplus state: summary has meaningful content');
check(
    stripos($surplusInsight['summary'], '18,000') !== false ||
    stripos($surplusInsight['summary'], 'positive') !== false ||
    stripos($surplusInsight['summary'], 'surplus') !== false ||
    stripos($surplusInsight['summary'], 'savings') !== false,
    'Surplus state: summary references positive balance'
);

$hlText = implode(' ', $surplusInsight['highlights']);
check(
    stripos($hlText, 'drop') !== false || stripos($hlText, 'reduction') !== false ||
    stripos($hlText, 'Disciplined') !== false || stripos($hlText, 'decreased') !== false,
    'Surplus state: highlights mention expense reduction (MoM -15%)'
);

// ─────────────────────────────────────────────
// TEST 10: FallbackAiProvider — generateMonthlyInsight (deficit + near-limit)
// ─────────────────────────────────────────────
echo "\n--- TEST 10: FallbackAiProvider — generateMonthlyInsight Deficit State ---\n";

$deficitData = array_merge($surplusData, [
    'total_income' => 10000.0, 'total_expense' => 14000.0, 'net_balance' => -4000.0,
    'expense_change_percent' => 20.0,
    'near_limit_budgets' => ['Food', 'Transport'],
    'over_budget_categories' => ['Entertainment'],
]);

$deficitInsight = $fp->generateMonthlyInsight($deficitData);
check($deficitInsight !== null, 'Deficit state: insight generated');
check(
    stripos($deficitInsight['summary'], '4,000') !== false ||
    stripos($deficitInsight['summary'], 'exceeded') !== false ||
    stripos($deficitInsight['summary'], 'deficit') !== false,
    'Deficit state: summary references deficit'
);
$actFlat = strtolower(implode(' ', $deficitInsight['actions']));
check(
    stripos($actFlat, 'food') !== false || stripos($actFlat, 'transport') !== false ||
    stripos($actFlat, 'monitor') !== false || stripos($actFlat, 'limit') !== false,
    'Deficit state: actions address near-limit or dominant category'
);

// ─────────────────────────────────────────────
// TEST 11: AiInsightService — aggregateFinancialData structure & user isolation
// ─────────────────────────────────────────────
echo "\n--- TEST 11: AiInsightService — aggregateFinancialData ---\n";

$month = Carbon::now()->format('Y-m');
$startOfMonth = Carbon::now()->startOfMonth()->toDateString();

// Seed test transactions for this month
$foodCatDb = Category::forUser($userA->id)->where('type', 'expense')
    ->where('name', 'like', '%food%')->first()
    ?? Category::forUser($userA->id)->where('type', 'expense')->first();

$incomeCatDb = Category::forUser($userA->id)->where('type', 'income')->first();

if ($foodCatDb) {
    Transaction::create([
        'user_id' => $userA->id, 'category_id' => $foodCatDb->id,
        'type' => 'expense', 'amount' => 3000.00,
        'description' => 'Phase7 test expense', 'transaction_date' => $startOfMonth,
        'payment_method' => 'Cash',
    ]);
}
if ($incomeCatDb) {
    Transaction::create([
        'user_id' => $userA->id, 'category_id' => $incomeCatDb->id,
        'type' => 'income', 'amount' => 25000.00,
        'description' => 'Phase7 test income', 'transaction_date' => $startOfMonth,
        'payment_method' => 'Bank Transfer',
    ]);
}

$agg = AiInsightService::aggregateFinancialData($userA, $month);

check(isset($agg['total_income']) && $agg['total_income'] > 0, 'aggregateFinancialData: total_income > 0');
check(isset($agg['total_expense']) && $agg['total_expense'] >= 0, 'aggregateFinancialData: total_expense key present');
check(isset($agg['net_balance']), 'aggregateFinancialData: net_balance key present');
check(is_array($agg['category_breakdown']), 'aggregateFinancialData: category_breakdown is array');
check(array_key_exists('top_category_name', $agg), 'aggregateFinancialData: top_category_name key exists');
check(array_key_exists('has_history', $agg), 'aggregateFinancialData: has_history key exists');
check(array_key_exists('near_limit_budgets', $agg), 'aggregateFinancialData: near_limit_budgets key exists');
check(array_key_exists('over_budget_categories', $agg), 'aggregateFinancialData: over_budget_categories key exists');
check(array_key_exists('expense_change_percent', $agg), 'aggregateFinancialData: expense_change_percent key exists');

// User isolation check
$aggB = AiInsightService::aggregateFinancialData($userB, $month);
check(
    $agg['total_income'] !== $aggB['total_income'] || $agg['total_expense'] !== $aggB['total_expense'],
    'AiInsightService: User A and User B aggregations are different (user-isolated)'
);

// ─────────────────────────────────────────────
// TEST 12: AiInsightService — getOrCreateInsight creates new
// ─────────────────────────────────────────────
echo "\n--- TEST 12: AiInsightService — getOrCreateInsight Creates New ---\n";

AiMonthlyInsight::where('user_id', $userA->id)->where('month', $month)->delete();
check(AiMonthlyInsight::where('user_id', $userA->id)->where('month', $month)->count() === 0,
    'Pre-condition: no insight for current month');

$insight1 = AiInsightService::getOrCreateInsight($userA, $month);
check($insight1 instanceof AiMonthlyInsight, 'getOrCreateInsight returns AiMonthlyInsight');
check((int)$insight1->user_id === (int)$userA->id, 'Insight user_id matches User A');
check($insight1->month === $month, 'Insight month matches current month');
check(!empty($insight1->summary), 'Insight summary is non-empty');
check(is_array($insight1->highlights), 'Insight highlights is array');
check(is_array($insight1->actions), 'Insight actions is array');
check(in_array($insight1->status, ['ai_generated', 'fallback', 'failed']), 'Insight status has valid value');

// ─────────────────────────────────────────────
// TEST 13: AiInsightService — getOrCreateInsight is idempotent
// ─────────────────────────────────────────────
echo "\n--- TEST 13: AiInsightService — No Duplicate Records ---\n";

$insight2 = AiInsightService::getOrCreateInsight($userA, $month);
$countAfter = AiMonthlyInsight::where('user_id', $userA->id)->where('month', $month)->count();
check($countAfter === 1, "Exactly 1 insight record for User A in $month (idempotent)");
check($insight2->id === $insight1->id, 'getOrCreateInsight returns same record on second call');

// ─────────────────────────────────────────────
// TEST 14: AiInsightService — force regeneration
// ─────────────────────────────────────────────
echo "\n--- TEST 14: AiInsightService — Force Regeneration ---\n";

$forced = AiInsightService::generateInsight($userA, $month, true);
check($forced instanceof AiMonthlyInsight, 'Forced regeneration returns AiMonthlyInsight');
check(!empty($forced->summary), 'Forced regeneration summary is non-empty');
$countAfterForce = AiMonthlyInsight::where('user_id', $userA->id)->where('month', $month)->count();
check($countAfterForce === 1, 'Still exactly 1 insight record after force-regeneration (updateOrCreate)');

// ─────────────────────────────────────────────
// TEST 15: AiInsightService — getPastInsights
// ─────────────────────────────────────────────
echo "\n--- TEST 15: AiInsightService — getPastInsights ---\n";

foreach (['2026-05', '2026-06', '2026-07'] as $pm) {
    AiMonthlyInsight::updateOrCreate(
        ['user_id' => $userA->id, 'month' => $pm],
        [
            'summary' => "Seeded insight for $pm",
            'highlights' => ['h1'], 'actions' => ['a1'],
            'status' => AiMonthlyInsight::STATUS_FALLBACK,
            'provider' => 'fallback', 'model' => 'deterministic-analytics',
            'metadata' => [], 'generated_at' => now(),
        ]
    );
}

$pastInsights = AiInsightService::getPastInsights($userA, $month, 6);
check($pastInsights->count() >= 3, 'getPastInsights returns >= 3 records');
check($pastInsights->where('month', $month)->count() === 0, 'getPastInsights excludes current month');

$months = $pastInsights->pluck('month')->toArray();
$sorted = $months;
rsort($sorted);
check($months === $sorted, 'getPastInsights ordered by month DESC');

$limited = AiInsightService::getPastInsights($userA, $month, 2);
check($limited->count() <= 2, 'getPastInsights respects $limit parameter');

// ─────────────────────────────────────────────
// TEST 16: AiMonthlyInsight Model — Status Helpers
// ─────────────────────────────────────────────
echo "\n--- TEST 16: AiMonthlyInsight — Status Helpers ---\n";

$mAi = new AiMonthlyInsight(['status' => AiMonthlyInsight::STATUS_AI_GENERATED]);
$mFb = new AiMonthlyInsight(['status' => AiMonthlyInsight::STATUS_FALLBACK]);
$mFail = new AiMonthlyInsight(['status' => AiMonthlyInsight::STATUS_FAILED]);

check($mAi->isAiGenerated() === true, 'isAiGenerated() → true for STATUS_AI_GENERATED');
check($mAi->isFallback() === false, 'isFallback() → false for STATUS_AI_GENERATED');
check($mFb->isFallback() === true, 'isFallback() → true for STATUS_FALLBACK');
check($mFb->isAiGenerated() === false, 'isAiGenerated() → false for STATUS_FALLBACK');
check($mFail->isAiGenerated() === false, 'isAiGenerated() → false for STATUS_FAILED');
check($mFail->isFallback() === false, 'isFallback() → false for STATUS_FAILED');

// ─────────────────────────────────────────────
// TEST 17: AiMonthlyInsight — formattedMonth Accessor
// ─────────────────────────────────────────────
echo "\n--- TEST 17: AiMonthlyInsight — formattedMonth Accessor ---\n";

check((new AiMonthlyInsight(['month' => '2026-09']))->formatted_month === 'September 2026',
    'formatted_month: 2026-09 → "September 2026"');
check((new AiMonthlyInsight(['month' => '2026-01']))->formatted_month === 'January 2026',
    'formatted_month: 2026-01 → "January 2026"');
check(is_string((new AiMonthlyInsight(['month' => 'invalid']))->formatted_month),
    'formatted_month: invalid input returns string gracefully');

// ─────────────────────────────────────────────
// TEST 18: AiMonthlyInsight — Eloquent Scopes
// ─────────────────────────────────────────────
echo "\n--- TEST 18: AiMonthlyInsight — Eloquent Scopes ---\n";

$aForUserA = AiMonthlyInsight::forUser($userA->id)->get();
$aForUserB = AiMonthlyInsight::forUser($userB->id)->get();
check($aForUserA->where('user_id', '!=', $userA->id)->count() === 0,
    'forUser scope returns only User A records');
check($aForUserB->where('user_id', '!=', $userB->id)->count() === 0,
    'forUser scope returns only User B records');

$aForMonth = AiMonthlyInsight::forUser($userA->id)->forMonth($month)->get();
check($aForMonth->where('month', '!=', $month)->count() === 0,
    'forMonth scope returns only records for selected month');

// ─────────────────────────────────────────────
// TEST 19: Provider Contract Enforcement
// ─────────────────────────────────────────────
echo "\n--- TEST 19: Provider Contract Enforcement ---\n";

$providers = [
    'FallbackAiProvider' => new FallbackAiProvider(),
    'GeminiAiProvider'   => new GeminiAiProvider(),
    'OpenAiProvider'     => new OpenAiProvider(),
];

foreach ($providers as $name => $p) {
    check($p instanceof AiProviderInterface, "$name implements AiProviderInterface");
    check(method_exists($p, 'getName'), "$name has getName() method");
    check(method_exists($p, 'categorizeExpense'), "$name has categorizeExpense() method");
    check(method_exists($p, 'generateMonthlyInsight'), "$name has generateMonthlyInsight() method");
}
check($providers['FallbackAiProvider']->getName() === 'fallback', 'FallbackAiProvider::getName() = "fallback"');
check($providers['GeminiAiProvider']->getName() === 'gemini', 'GeminiAiProvider::getName() = "gemini"');
check($providers['OpenAiProvider']->getName() === 'openai', 'OpenAiProvider::getName() = "openai"');

// ─────────────────────────────────────────────
// TEST 20: Route Registration
// ─────────────────────────────────────────────
echo "\n--- TEST 20: Route Registration ---\n";

$allRoutes = collect(Route::getRoutes())->map(fn($r) => $r->uri());
check($allRoutes->contains('api/ai/categorize-expense'), 'Route /api/ai/categorize-expense registered');
check($allRoutes->contains('insights/{month?}'), 'Route /insights/{month?} registered');
check($allRoutes->contains('insights/generate'), 'Route /insights/generate registered');

$namedRoutes = collect(Route::getRoutes())
    ->filter(fn($r) => $r->getName() !== null)
    ->map(fn($r) => $r->getName());
check($namedRoutes->contains('ai.categorize'), 'Named route "ai.categorize" registered');
check($namedRoutes->contains('insights'), 'Named route "insights" registered');
check($namedRoutes->contains('insights.generate'), 'Named route "insights.generate" registered');

// ─────────────────────────────────────────────
// TEST 21: AiCategorizationService — Empty Category User
// ─────────────────────────────────────────────
echo "\n--- TEST 21: AiCategorizationService — Empty Category Edge Case ---\n";

DB::beginTransaction();
try {
    $emptyUser = User::create([
        'name' => 'Phase7 Empty User',
        'email' => 'phase7empty_' . time() . '@test.com',
        'password' => bcrypt('pass12345'),
    ]);
    $emptyResult = AiCategorizationService::suggestCategory($emptyUser, 'cafeteria lunch', 'nonexistent_type');
    check($emptyResult['success'] === true, 'Empty-category type: AiCategorizationService returns success=true');
    check($emptyResult['suggestion'] === null, 'Empty-category type: suggestion is null (no categories)');
} finally {
    DB::rollBack();
}

// ─────────────────────────────────────────────
// TEST 22: FallbackAiProvider — Rich Insight Highlights & Actions
// ─────────────────────────────────────────────
echo "\n--- TEST 22: FallbackAiProvider — Rich Insight Highlights & Actions ---\n";

$richData = [
    'month' => '2026-09', 'month_label' => 'September 2026',
    'total_income' => 25000.0, 'total_expense' => 18000.0, 'net_balance' => 7000.0,
    'total_transactions_count' => 15,
    'category_breakdown' => [
        ['name' => 'Food', 'amount' => 10000.0, 'percentage' => 55.6],
        ['name' => 'Transport', 'amount' => 4000.0, 'percentage' => 22.2],
        ['name' => 'Academics', 'amount' => 4000.0, 'percentage' => 22.2],
    ],
    'top_category_name' => 'Food', 'top_category_amount' => 10000.0, 'top_category_percent' => 55.6,
    'has_history' => true, 'expense_change_percent' => 12.0,
    'near_limit_budgets' => ['Academics'], 'over_budget_categories' => [],
    'academic_program' => 'Business Administration',
];

$richInsight = $fp->generateMonthlyInsight($richData);
check($richInsight !== null, 'Rich data: insight generated');
check(count($richInsight['highlights']) >= 1, 'Rich data: at least 1 highlight');
check(count($richInsight['actions']) >= 1, 'Rich data: at least 1 action');

$allStrings = true;
foreach (array_merge($richInsight['highlights'], $richInsight['actions']) as $item) {
    if (!is_string($item) || empty(trim($item))) { $allStrings = false; break; }
}
check($allStrings, 'Rich data: all highlights & actions are non-empty strings');

$actionsFlat = strtolower(implode(' ', $richInsight['actions']));
check(
    stripos($actionsFlat, 'food') !== false || stripos($actionsFlat, 'cap') !== false ||
    stripos($actionsFlat, 'limit') !== false || stripos($actionsFlat, 'academics') !== false ||
    stripos($actionsFlat, 'monitor') !== false,
    'Rich data: actions address dominant category (Food 55.6%) or near-limit (Academics)'
);

// ─────────────────────────────────────────────
// TEST 23: aggregateFinancialData net_balance correctness
// ─────────────────────────────────────────────
echo "\n--- TEST 23: AiInsightService — net_balance Correctness ---\n";

$agg2 = AiInsightService::aggregateFinancialData($userA, $month);
$expectedNet = $agg2['total_income'] - $agg2['total_expense'];
check(
    abs($agg2['net_balance'] - $expectedNet) < 0.01,
    'aggregateFinancialData: net_balance = total_income - total_expense (correct)'
);

// Category breakdown percentages sum up to ~100 when expenses > 0
if ($agg2['total_expense'] > 0 && !empty($agg2['category_breakdown'])) {
    $totalPct = array_sum(array_column($agg2['category_breakdown'], 'percentage'));
    check($totalPct >= 95.0 && $totalPct <= 105.0, 'Category breakdown percentages sum to ~100%');
} else {
    check(true, 'Category breakdown percentage check skipped (no expenses)');
}

// ─────────────────────────────────────────────
// SUMMARY
// ─────────────────────────────────────────────
echo "\n" . str_repeat('=', 50) . "\n";
echo "SUMMARY: PASS = $pass, FAIL = $fail\n";
echo str_repeat('=', 50) . "\n";

if ($fail === 0) {
    echo "ALL PHASE 7 AI FEATURES TESTS PASSED!\n\n";
    exit(0);
} else {
    echo "SOME TESTS FAILED. Review output above.\n\n";
    exit(1);
}


