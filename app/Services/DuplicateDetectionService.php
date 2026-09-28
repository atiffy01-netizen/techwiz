<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;

class DuplicateDetectionService
{
    /**
     * Check for potential duplicate transactions using deterministic heuristics.
     *
     * @param User $user
     * @param float $amount
     * @param int|null $categoryId
     * @param string $type
     * @param string|null $transactionDate
     * @param string|null $description
     * @param int|null $excludeTransactionId
     * @return array
     */
    public static function checkForDuplicates(
        User $user,
        float $amount,
        ?int $categoryId,
        string $type = 'expense',
        ?string $transactionDate = null,
        ?string $description = null,
        ?int $excludeTransactionId = null
    ): array {
        if ($amount <= 0) {
            return [
                'is_duplicate' => false,
                'count' => 0,
                'warning_message' => null,
                'matches' => [],
            ];
        }

        $date = $transactionDate ? Carbon::parse($transactionDate) : Carbon::now();
        $startDate = $date->copy()->subDays(5)->toDateString();
        $endDate = $date->copy()->addDays(5)->toDateString();

        // 1. Query potential matching transactions for the user within a 5-day window
        $query = Transaction::where('user_id', $user->id)
            ->where('type', $type)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('amount', '=', $amount)
            ->with('category:id,name,icon');

        if ($excludeTransactionId) {
            $query->where('id', '!=', $excludeTransactionId);
        }

        $candidates = $query->get();

        $matches = [];
        $descClean = mb_strtolower(trim($description ?? ''));

        foreach ($candidates as $cand) {
            $isMatch = false;

            // Same category
            if ($categoryId && (int) $cand->category_id === (int) $categoryId) {
                $isMatch = true;
            }

            // Or highly similar description
            $candDesc = mb_strtolower(trim($cand->description));
            if (!empty($descClean) && !empty($candDesc)) {
                if ($descClean === $candDesc || stripos($candDesc, $descClean) !== false || stripos($descClean, $candDesc) !== false) {
                    $isMatch = true;
                } else {
                    similar_text($descClean, $candDesc, $percent);
                    if ($percent >= 70.0) {
                        $isMatch = true;
                    }
                }
            }

            if ($isMatch) {
                $matches[] = [
                    'id' => $cand->id,
                    'amount' => (float) $cand->amount,
                    'formatted_amount' => 'Rs. ' . number_format($cand->amount, 2),
                    'category_name' => $cand->category->name ?? 'Uncategorized',
                    'category_icon' => $cand->category->icon ?? 'bi-tag',
                    'transaction_date' => $cand->transaction_date ? $cand->transaction_date->format('M d, Y') : 'N/A',
                    'description' => $cand->description,
                ];
            }
        }

        $count = count($matches);
        $isDuplicate = $count > 0;

        $warningMessage = null;
        if ($isDuplicate) {
            $first = $matches[0];
            $warningMessage = "Possible duplicate detected: Rs. {$first['amount']} on {$first['transaction_date']} ({$first['description']}).";
        }

        return [
            'is_duplicate' => $isDuplicate,
            'count' => $count,
            'warning_message' => $warningMessage,
            'matches' => $matches,
        ];
    }
}
