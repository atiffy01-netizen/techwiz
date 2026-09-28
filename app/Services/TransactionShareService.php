<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\TransactionShare;
use App\Models\User;
use Carbon\Carbon;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TransactionShareService
{
    /**
     * Create or retrieve an active share link for a transaction.
     *
     * @param User $user
     * @param int $transactionId
     * @param int|null $expiresInDays
     * @return TransactionShare
     */
    public static function createShare(User $user, int $transactionId, ?int $expiresInDays = 30): TransactionShare
    {
        $transaction = Transaction::findOrFail($transactionId);

        if (!$transaction->isOwnedBy($user)) {
            throw new AccessDeniedHttpException('You cannot share a transaction that does not belong to you.');
        }

        // Deactivate any existing active shares for this transaction to maintain a single active link
        TransactionShare::where('user_id', $user->id)
            ->where('transaction_id', $transaction->id)
            ->update(['is_active' => false]);

        $token = TransactionShare::generateUniqueToken();
        $expiresAt = $expiresInDays ? Carbon::now()->addDays($expiresInDays) : null;

        return TransactionShare::create([
            'user_id' => $user->id,
            'transaction_id' => $transaction->id,
            'token' => $token,
            'is_active' => true,
            'expires_at' => $expiresAt,
        ]);
    }

    /**
     * Revoke a shared link.
     *
     * @param User $user
     * @param int $shareId
     * @return bool
     */
    public static function revokeShare(User $user, int $shareId): bool
    {
        $share = TransactionShare::findOrFail($shareId);

        if ($share->user_id !== $user->id) {
            throw new AccessDeniedHttpException('You cannot revoke a share link that does not belong to you.');
        }

        $share->is_active = false;
        return $share->save();
    }

    /**
     * Get safe non-sensitive public details for a shared transaction token.
     *
     * @param string $token
     * @return array
     */
    public static function getValidSharedTransaction(string $token): array
    {
        $share = TransactionShare::with(['transaction.category'])
            ->where('token', $token)
            ->first();

        if (!$share || !$share->isValid() || !$share->transaction) {
            throw new NotFoundHttpException('This shared transaction link is invalid, expired, or has been revoked.');
        }

        $tx = $share->transaction;

        // Strictly safe public representation — NO passwords, emails, private notes, or internal IDs
        return [
            'type' => $tx->type,
            'amount' => (float) $tx->amount,
            'formatted_amount' => 'Rs. ' . number_format($tx->amount, 2),
            'description' => $tx->description,
            'transaction_date' => $tx->transaction_date ? $tx->transaction_date->format('F d, Y') : 'N/A',
            'category_name' => $tx->category->name ?? 'General',
            'category_icon' => $tx->category->icon ?? 'bi-tag',
            'category_type' => $tx->category->type ?? $tx->type,
            'payment_method' => $tx->payment_method ?? 'Cash',
            'shared_at' => $share->created_at ? $share->created_at->format('M d, Y') : null,
            'expires_at' => $share->expires_at ? $share->expires_at->format('M d, Y') : null,
        ];
    }
}
