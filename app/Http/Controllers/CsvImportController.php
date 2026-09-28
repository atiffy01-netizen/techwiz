<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class CsvImportController extends Controller
{
    /**
     * Preview and validate uploaded CSV file.
     */
    public function preview(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $user = Auth::user();
        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');

        if (!$handle) {
            return response()->json(['error' => 'Unable to open uploaded CSV file.'], 422);
        }

        $categories = Category::forUser($user->id)->get();
        $categoriesMap = [];
        foreach ($categories as $cat) {
            $key = strtolower(trim($cat->name)) . '|' . strtolower($cat->type);
            $categoriesMap[$key] = $cat;
        }

        $rows = [];
        $validRows = [];
        $invalidRows = [];
        $rowNumber = 0;
        $header = null;

        while (($data = fgetcsv($handle, 2000, ',')) !== false) {
            $rowNumber++;
            // Check for empty row
            if (empty(array_filter($data, fn($v) => trim($v) !== ''))) {
                continue;
            }

            // Detect header row if present
            if ($rowNumber === 1 && (strtolower(trim($data[0])) === 'date' || strtolower(trim($data[1] ?? '')) === 'type')) {
                $header = $data;
                continue;
            }

            // Expected columns: date, type, category, amount, description
            $rawDate = trim($data[0] ?? '');
            $rawType = strtolower(trim($data[1] ?? ''));
            $rawCat = trim($data[2] ?? '');
            $rawAmt = trim($data[3] ?? '');
            $rawDesc = trim($data[4] ?? 'Imported Transaction');

            $errors = [];

            // 1. Validate Date
            $parsedDate = null;
            try {
                if (!empty($rawDate)) {
                    $parsedDate = Carbon::parse($rawDate)->toDateString();
                } else {
                    $errors[] = 'Date is required.';
                }
            } catch (\Exception $e) {
                $errors[] = "Invalid date format '{$rawDate}'.";
            }

            // 2. Validate Type
            if (!in_array($rawType, ['income', 'expense'])) {
                $errors[] = "Type must be 'income' or 'expense' (got '{$rawType}').";
            }

            // 3. Validate Amount
            $amount = null;
            $cleanAmt = str_replace([',', '$', 'Rs.', 'Rs', ' '], '', $rawAmt);
            if (!is_numeric($cleanAmt) || floatval($cleanAmt) <= 0) {
                $errors[] = "Amount must be a positive number (got '{$rawAmt}').";
            } else {
                $amount = round(floatval($cleanAmt), 2);
            }

            // 4. Validate Category
            $matchedCategory = null;
            if (!empty($rawCat) && in_array($rawType, ['income', 'expense'])) {
                $key = strtolower($rawCat) . '|' . $rawType;
                if (isset($categoriesMap[$key])) {
                    $matchedCategory = $categoriesMap[$key];
                } else {
                    // Try to match by name only
                    $nameMatch = $categories->first(fn($c) => strtolower($c->name) === strtolower($rawCat));
                    if ($nameMatch) {
                        if ($nameMatch->type === $rawType) {
                            $matchedCategory = $nameMatch;
                        } else {
                            $errors[] = "Category '{$rawCat}' is of type {$nameMatch->type}, but row specifies {$rawType}.";
                        }
                    } else {
                        $errors[] = "Unknown category '{$rawCat}'.";
                    }
                }
            } else {
                if (empty($rawCat)) {
                    $errors[] = 'Category is required.';
                }
            }

            // 5. Validate Description
            if (empty($rawDesc)) {
                $rawDesc = 'Imported Transaction';
            }

            $rowObj = [
                'row_number' => $rowNumber,
                'date' => $parsedDate ?? $rawDate,
                'type' => $rawType,
                'category_name' => $matchedCategory ? $matchedCategory->name : $rawCat,
                'category_id' => $matchedCategory ? $matchedCategory->id : null,
                'amount' => $amount ?? $rawAmt,
                'description' => $rawDesc,
                'is_valid' => empty($errors),
                'errors' => $errors,
            ];

            $rows[] = $rowObj;
            if (empty($errors)) {
                $validRows[] = $rowObj;
            } else {
                $invalidRows[] = $rowObj;
            }
        }

        fclose($handle);

        return response()->json([
            'success' => true,
            'total_rows' => count($rows),
            'valid_count' => count($validRows),
            'invalid_count' => count($invalidRows),
            'rows' => $rows,
            'valid_rows' => $validRows,
        ]);
    }

    /**
     * Confirm and insert validated transactions.
     */
    public function confirm(Request $request)
    {
        $request->validate([
            'records' => 'required|array|min:1',
            'records.*.date' => 'required|date',
            'records.*.type' => 'required|in:income,expense',
            'records.*.category_id' => 'required|exists:categories,id',
            'records.*.amount' => 'required|numeric|min:0.01',
            'records.*.description' => 'required|string|max:255',
        ]);

        $user = Auth::user();
        $records = $request->input('records');
        $insertedCount = 0;

        foreach ($records as $record) {
            // Verify category accessibility
            $category = Category::forUser($user->id)->find($record['category_id']);
            if (!$category || $category->type !== $record['type']) {
                continue;
            }

            Transaction::create([
                'user_id' => $user->id,
                'category_id' => $category->id,
                'amount' => $record['amount'],
                'type' => $record['type'],
                'description' => trim($record['description']),
                'transaction_date' => $record['date'],
                'payment_method' => 'CSV Import',
                'note' => 'Imported via CSV batch upload',
                'is_recurring' => false,
            ]);
            $insertedCount++;
        }

        // Trigger budget alert checks for the user
        \App\Services\BudgetAlertService::checkUserBudgets($user);

        if ($request->wantsJson() || $request->ajax() || $request->isJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'count' => $insertedCount,
                'message' => "{$insertedCount} transactions imported successfully.",
            ]);
        }

        return redirect()->route('transactions')
            ->with('success', "{$insertedCount} transactions imported successfully.");
    }

    /**
     * Export user's transactions to CSV file.
     */
    public function export()
    {
        $user = Auth::user();
        $transactions = Transaction::with('category')
            ->where('user_id', $user->id)
            ->orderBy('transaction_date', 'desc')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="campus_coin_transactions_' . date('Y-m-d') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($transactions) {
            $handle = fopen('php://output', 'w');
            // Write Header
            fputcsv($handle, ['Date', 'Type', 'Category', 'Amount', 'Description', 'Payment Method', 'Recurring', 'Note']);

            foreach ($transactions as $txn) {
                fputcsv($handle, [
                    $txn->transaction_date ? $txn->transaction_date->format('Y-m-d') : '',
                    $txn->type,
                    $txn->category ? $txn->category->name : 'Uncategorized',
                    $txn->amount,
                    $txn->description,
                    $txn->payment_method ?? 'Cash',
                    $txn->is_recurring ? ($txn->recurring_frequency ?? 'Yes') : 'No',
                    $txn->note ?? '',
                ]);
            }

            fclose($handle);
        };

        return Response::stream($callback, 200, $headers);
    }
}
