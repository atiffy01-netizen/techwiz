<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\CsvImportController;
use App\Models\User;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;

echo "=== CSV IMPORT AND EXPORT VERIFICATION ===\n\n";

$user = User::where('email', 'hunzala@campuscoin.edu')->first();
Auth::login($user);

// Create a test CSV file
$csvContent = <<<CSV
date,type,category,amount,description
2026-09-01,expense,Food,450,Campus Cafe Lunch
2026-09-02,income,Allowance,25000,Monthly Parents Allowance
2026-09-03,expense,Transport,120,Metro Bus Fare
2026-09-04,expense,Academics,1500,Semester Textbooks
2026-09-05,invalid_type,Food,500,Bad Type Entry
2026-09-06,expense,NonExistentCat,999,Unknown Category Row
2026-09-07,expense,Food,-50,Negative Amount Row
CSV;

$tmpPath = tempnam(sys_get_temp_dir(), 'csv_test');
file_put_contents($tmpPath, $csvContent);

$uploadedFile = new UploadedFile($tmpPath, 'test_txns.csv', 'text/csv', null, true);

$req = Request::create('/transactions/import/preview', 'POST', [], [], ['csv_file' => $uploadedFile]);
$req->headers->set('Accept', 'application/json');
$req->headers->set('X-Requested-With', 'XMLHttpRequest');

$controller = new CsvImportController();
$res = $controller->preview($req);
$data = json_decode($res->getContent(), true);

echo "Total parsed rows: {$data['total_rows']}\n";
echo "Valid rows: {$data['valid_count']}\n";
echo "Invalid rows: {$data['invalid_count']}\n";

assert($data['total_rows'] === 7, "Expected 7 rows parsed");
assert($data['valid_count'] === 4, "Expected exactly 4 valid rows");
assert($data['invalid_count'] === 3, "Expected exactly 3 invalid rows");

// Test confirm import
$confirmReq = Request::create('/transactions/import/confirm', 'POST', ['records' => $data['valid_rows']]);
$confirmReq->headers->set('Accept', 'application/json');
$confirmReq->headers->set('X-Requested-With', 'XMLHttpRequest');

$confirmRes = $controller->confirm($confirmReq);
$confirmData = json_decode($confirmRes->getContent(), true);

echo "Import confirmed count: {$confirmData['count']}\n";
assert($confirmData['count'] === 4, "Expected 4 rows to be imported");

@unlink($tmpPath);

echo "\n[PASS] CSV Import Validation & Execution Verified Successfully!\n";
