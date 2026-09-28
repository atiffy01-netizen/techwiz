<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\TransactionBookmark;
use App\Models\TransactionNote;
use App\Models\TransactionShare;
use App\Models\TransactionActivity;
use App\Services\BookmarkService;
use App\Services\TransactionNoteService;
use App\Services\TransactionShareService;
use App\Services\TransactionActivityService;
use App\Services\DuplicateDetectionService;
use App\Services\SpendingForecastService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

echo "====================================================\n";
echo "    CAMPUS COIN — PHASE 9 AUTOMATED TEST SUITE     \n";
echo "====================================================\n\n";

$passed = 0;
$failed = 0;

function runTest($title, $callback) {
    global $passed, $failed;
    echo "Testing: {$title} ... ";
    try {
        $result = $callback();
        if ($result === true || $result === null) {
            echo "[\033[32mPASSED\033[0m]\n";
            $passed++;
        } else {
            echo "[\033[31mFAILED\033[0m] - " . json_encode($result) . "\n";
            $failed++;
        }
    } catch (\Throwable $e) {
        echo "[\033[31mFAILED WITH EXCEPTION\033[0m] - " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
        $failed++;
    }
}

// Setup test user
$user = User::firstOrCreate(
    ['email' => 'phase9_tester@campuscoin.edu'],
    [
        'name' => 'Phase 9 Tester',
        'password' => Hash::make('password123'),
        'role' => 'student',
        'is_active' => true,
    ]
);

$category = Category::firstOrCreate(
    ['name' => 'Campus Books & Supplies'],
    [
        'type' => 'expense',
        'color' => '#8b5cf6',
        'icon' => 'fas fa-book',
        'is_system' => true,
        'is_active' => true
    ]
);

$transaction = Transaction::create([
    'user_id' => $user->id,
    'category_id' => $category->id,
    'type' => 'expense',
    'amount' => 45.50,
    'description' => 'Calculus Textbook 2026 Edition',
    'transaction_date' => now()->toDateString(),
]);

// 1. Transaction Bookmark Tests
runTest("Bookmark Service - Toggle Bookmark On", function() use ($user, $transaction) {
    $result = BookmarkService::toggle($user, $transaction->id);
    return isset($result['bookmarked']) && $result['bookmarked'] === true;
});

runTest("Bookmark Service - Is Bookmarked Check", function() use ($user, $transaction) {
    return BookmarkService::isBookmarked($user, $transaction->id) === true;
});

runTest("Bookmark Service - Get Bookmarked List", function() use ($user) {
    $paginated = BookmarkService::getBookmarkedTransactions($user);
    return $paginated->total() >= 1;
});

runTest("Bookmark Service - Toggle Bookmark Off", function() use ($user, $transaction) {
    $result = BookmarkService::toggle($user, $transaction->id);
    return isset($result['bookmarked']) && $result['bookmarked'] === false && BookmarkService::isBookmarked($user, $transaction->id) === false;
});

// 2. Transaction Notes Tests
runTest("Transaction Notes - Save Note", function() use ($user, $transaction) {
    $note = TransactionNoteService::saveNote($user, $transaction->id, 'Kept receipt in binder, refundable until Oct 1');
    return $note && $note->note === 'Kept receipt in binder, refundable until Oct 1';
});

runTest("Transaction Notes - Get Note", function() use ($user, $transaction) {
    $note = TransactionNoteService::getNote($user, $transaction->id);
    return $note && str_contains($note->note, 'refundable');
});

runTest("Transaction Notes - Delete Note", function() use ($user, $transaction) {
    $deleted = TransactionNoteService::deleteNote($user, $transaction->id);
    $note = TransactionNoteService::getNote($user, $transaction->id);
    return $deleted && $note === null;
});

// 3. Transaction Sharing Tests
runTest("Transaction Sharing - Create Share Link", function() use ($user, $transaction) {
    $share = TransactionShareService::createShare($user, $transaction->id, 7);
    return !empty($share->token) && $share->expires_at->isFuture();
});

runTest("Transaction Sharing - Get Valid Shared Transaction Public Data", function() use ($transaction) {
    $share = TransactionShare::where('transaction_id', $transaction->id)->where('is_active', true)->first();
    $data = TransactionShareService::getValidSharedTransaction($share->token);
    return isset($data['amount']) && (float) $data['amount'] === 45.50 && isset($data['formatted_amount']);
});

runTest("Transaction Sharing - Revoke Share Link", function() use ($user, $transaction) {
    $share = TransactionShare::where('transaction_id', $transaction->id)->first();
    $revoked = TransactionShareService::revokeShare($user, $share->id);
    try {
        TransactionShareService::getValidSharedTransaction($share->token);
        return false; // should throw NotFoundHttpException
    } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
        return $revoked === true;
    }
});

// 4. Activity Log Tests
runTest("Transaction Activity - Record and Retrieve Logs", function() use ($user, $transaction) {
    $act1 = TransactionActivityService::recordActivity($user, $transaction->id, 'viewed');
    $recent = TransactionActivityService::getRecentActivities($user, 5);
    return $recent->count() >= 1 && $recent->first()->transaction_id === $transaction->id;
});

// 5. Duplicate Detection Tests
runTest("Duplicate Detection - Exact and Fuzzy Match", function() use ($user, $category) {
    // Check for duplicate with matching parameters
    $result = DuplicateDetectionService::checkForDuplicates(
        $user,
        45.50,
        $category->id,
        'expense',
        now()->toDateString(),
        'Calculus Textbook 2026 Edition'
    );

    return isset($result['is_duplicate']) && $result['is_duplicate'] === true && $result['count'] >= 1;
});

runTest("Duplicate Detection - No Match for Distinct Transaction", function() use ($user) {
    $result = DuplicateDetectionService::checkForDuplicates(
        $user,
        999.99,
        null,
        'expense',
        now()->toDateString(),
        'Unique Item With No Prior Match'
    );

    return isset($result['is_duplicate']) && $result['is_duplicate'] === false && $result['count'] === 0;
});

// 6. Spending Forecast Tests
runTest("Spending Forecast Service - Historical Aggregation & Structure", function() use ($user) {
    $forecast = SpendingForecastService::generateForecast($user);
    
    return isset($forecast['has_sufficient_data']) &&
           isset($forecast['upcoming_month']) &&
           isset($forecast['upcoming_month_label']) &&
           isset($forecast['estimated_total_spending']) &&
           isset($forecast['category_forecasts']);
});

// 7. Route and View Check
runTest("Phase 9 Views & Blade Templates exist", function() {
    $views = [
        resource_path('views/forecast.blade.php'),
        resource_path('views/bookmarks.blade.php'),
        resource_path('views/shared-transaction.blade.php'),
    ];
    foreach ($views as $view) {
        if (!file_exists($view)) {
            return "Missing view: {$view}";
        }
    }
    return true;
});

// Clean up test transaction
$transaction->delete();

echo "\n====================================================\n";
echo "SUMMARY: {$passed} Passed, {$failed} Failed\n";
echo "====================================================\n";

exit($failed > 0 ? 1 : 0);
