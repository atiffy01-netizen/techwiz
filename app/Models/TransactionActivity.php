<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'transaction_id',
        'activity_type', // 'viewed', 'edited'
    ];

    protected $appends = ['action'];

    /**
     * Accessor: expose activity_type as 'action' for template convenience.
     */
    public function getActionAttribute(): string
    {
        return $this->activity_type;
    }

    /**
     * Activity belongs to a user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Activity belongs to a transaction.
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Scope for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for viewed transactions.
     */
    public function scopeViewed($query)
    {
        return $query->where('activity_type', 'viewed');
    }

    /**
     * Scope for edited transactions.
     */
    public function scopeEdited($query)
    {
        return $query->where('activity_type', 'edited');
    }
}
