<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\TransactionNote;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class TransactionNoteService
{
    /**
     * Retrieve note for a transaction owned by user.
     *
     * @param User $user
     * @param int $transactionId
     * @return TransactionNote|null
     */
    public static function getNote(User $user, int $transactionId): ?TransactionNote
    {
        $transaction = Transaction::findOrFail($transactionId);

        if (!$transaction->isOwnedBy($user)) {
            throw new AccessDeniedHttpException('You cannot access notes for a transaction that does not belong to you.');
        }

        return TransactionNote::where('user_id', $user->id)
            ->where('transaction_id', $transaction->id)
            ->first();
    }

    /**
     * Save or update a note for a transaction.
     *
     * @param User $user
     * @param int $transactionId
     * @param string $noteText
     * @return TransactionNote
     */
    public static function saveNote(User $user, int $transactionId, string $noteText): TransactionNote
    {
        $transaction = Transaction::findOrFail($transactionId);

        if (!$transaction->isOwnedBy($user)) {
            throw new AccessDeniedHttpException('You cannot attach notes to a transaction that does not belong to you.');
        }

        $trimmed = trim($noteText);
        if (empty($trimmed)) {
            throw ValidationException::withMessages([
                'note' => ['Note text cannot be empty.'],
            ]);
        }

        if (mb_strlen($trimmed) > 2000) {
            throw ValidationException::withMessages([
                'note' => ['Note text must not exceed 2,000 characters.'],
            ]);
        }

        return TransactionNote::updateOrCreate(
            [
                'user_id' => $user->id,
                'transaction_id' => $transaction->id,
            ],
            [
                'note' => $trimmed,
            ]
        );
    }

    /**
     * Delete a note for a transaction.
     *
     * @param User $user
     * @param int $transactionId
     * @return bool
     */
    public static function deleteNote(User $user, int $transactionId): bool
    {
        $transaction = Transaction::findOrFail($transactionId);

        if (!$transaction->isOwnedBy($user)) {
            throw new AccessDeniedHttpException('You cannot delete notes from transactions that do not belong to you.');
        }

        return (bool) TransactionNote::where('user_id', $user->id)
            ->where('transaction_id', $transaction->id)
            ->delete();
    }
}
