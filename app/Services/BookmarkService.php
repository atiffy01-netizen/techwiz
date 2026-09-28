<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\TransactionBookmark;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class BookmarkService
{
    /**
     * Toggle bookmark state for a user's transaction.
     *
     * @param User $user
     * @param int $transactionId
     * @return array
     */
    public static function toggle(User $user, int $transactionId): array
    {
        $transaction = Transaction::findOrFail($transactionId);

        // Strict server-side ownership enforcement
        if (!$transaction->isOwnedBy($user)) {
            throw new AccessDeniedHttpException('You cannot bookmark a transaction that does not belong to you.');
        }

        $existing = TransactionBookmark::where('user_id', $user->id)
            ->where('transaction_id', $transaction->id)
            ->first();

        if ($existing) {
            $existing->delete();
            return [
                'bookmarked' => false,
                'message' => 'Transaction removed from bookmarks.',
            ];
        }

        TransactionBookmark::create([
            'user_id' => $user->id,
            'transaction_id' => $transaction->id,
        ]);

        return [
            'bookmarked' => true,
            'message' => 'Transaction bookmarked successfully.',
        ];
    }

    /**
     * Bookmark a transaction for a user (Idempotent).
     *
     * @param User $user
     * @param int $transactionId
     * @return TransactionBookmark
     */
    public static function bookmark(User $user, int $transactionId): TransactionBookmark
    {
        $transaction = Transaction::findOrFail($transactionId);

        if (!$transaction->isOwnedBy($user)) {
            throw new AccessDeniedHttpException('You cannot bookmark a transaction that does not belong to you.');
        }

        return TransactionBookmark::firstOrCreate([
            'user_id' => $user->id,
            'transaction_id' => $transaction->id,
        ]);
    }

    /**
     * Remove a bookmark for a user.
     *
     * @param User $user
     * @param int $transactionId
     * @return bool
     */
    public static function remove(User $user, int $transactionId): bool
    {
        $transaction = Transaction::findOrFail($transactionId);

        if (!$transaction->isOwnedBy($user)) {
            throw new AccessDeniedHttpException('You cannot remove bookmarks from transactions that do not belong to you.');
        }

        return (bool) TransactionBookmark::where('user_id', $user->id)
            ->where('transaction_id', $transaction->id)
            ->delete();
    }

    /**
     * Check if transaction is bookmarked by user.
     *
     * @param User $user
     * @param int $transactionId
     * @return bool
     */
    public static function isBookmarked(User $user, int $transactionId): bool
    {
        return TransactionBookmark::where('user_id', $user->id)
            ->where('transaction_id', $transactionId)
            ->exists();
    }

    /**
     * Retrieve paginated bookmarked transactions for a user.
     *
     * @param User $user
     * @param int $perPage
     * @param string|null $search
     * @param string|null $type
     * @return LengthAwarePaginator
     */
    public static function getBookmarkedTransactions(User $user, int $perPage = 15, ?string $search = null, ?string $type = null): LengthAwarePaginator
    {
        $query = Transaction::where('transactions.user_id', $user->id)
            ->join('transaction_bookmarks', function ($join) use ($user) {
                $join->on('transactions.id', '=', 'transaction_bookmarks.transaction_id')
                     ->where('transaction_bookmarks.user_id', '=', $user->id);
            })
            ->with(['category', 'noteRecord'])
            ->select('transactions.*', 'transaction_bookmarks.created_at as bookmarked_at');

        if (!empty($search)) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhereHas('category', function (Builder $cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($type === 'income' || $type === 'expense') {
            $query->where('transactions.type', $type);
        }

        return $query->orderByDesc('transaction_bookmarks.created_at')
            ->paginate($perPage);
    }
}
