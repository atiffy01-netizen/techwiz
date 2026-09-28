<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\TransactionActivity;
use App\Models\User;
use Carbon\Carbon;

class TransactionActivityService
{
    /**
     * Record a transaction activity (viewed or edited) with deduplication throttling.
     *
     * @param User $user
     * @param int $transactionId
     * @param string $type 'viewed' or 'edited'
     * @return TransactionActivity|null
     */
    public static function recordActivity(User $user, int $transactionId, string $type = 'viewed'): ?TransactionActivity
    {
        $transaction = Transaction::find($transactionId);

        // Only track activities for transactions owned by this user
        if (!$transaction || !$transaction->isOwnedBy($user)) {
            return null;
        }

        // Throttle duplicate records within 10 minutes
        $recent = TransactionActivity::where('user_id', $user->id)
            ->where('transaction_id', $transaction->id)
            ->where('activity_type', $type)
            ->where('updated_at', '>=', Carbon::now()->subMinutes(10))
            ->first();

        if ($recent) {
            $recent->touch();
            return $recent;
        }

        return TransactionActivity::create([
            'user_id' => $user->id,
            'transaction_id' => $transaction->id,
            'activity_type' => $type,
        ]);
    }

    /**
     * Get recent unique transaction activities for a user.
     *
     * @param User $user
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getRecentActivities(User $user, int $limit = 5)
    {
        return TransactionActivity::where('transaction_activities.user_id', $user->id)
            ->with(['transaction.category'])
            ->orderByDesc('updated_at')
            ->take($limit)
            ->get();
    }
}
