<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'amount',
        'type',
        'description',
        'transaction_date',
        'payment_method',
        'note',
        'is_recurring',
        'recurring_frequency',
        'recurring_start_date',
        'recurring_end_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
        'is_recurring' => 'boolean',
        'recurring_start_date' => 'date',
        'recurring_end_date' => 'date',
    ];

    /**
     * Transaction belongs to a User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Transaction belongs to a Category.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Check if transaction is income.
     */
    public function isIncome(): bool
    {
        return $this->type === 'income';
    }

    /**
     * Check if transaction is expense.
     */
    public function isExpense(): bool
    {
        return $this->type === 'expense';
    }

    /**
     * Bookmarks on this transaction.
     */
    public function bookmarks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TransactionBookmark::class);
    }

    /**
     * Notes on this transaction.
     */
    public function notes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TransactionNote::class);
    }

    /**
     * Primary note record.
     */
    public function noteRecord(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(TransactionNote::class);
    }

    /**
     * Shares on this transaction.
     */
    public function shares(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TransactionShare::class);
    }

    /**
     * Activities recorded on this transaction.
     */
    public function activities(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TransactionActivity::class);
    }

    /**
     * Check if bookmarked by a user.
     */
    public function isBookmarkedBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        return $this->bookmarks()->where('user_id', $user->id)->exists();
    }

    /**
     * Check if owned by a user.
     */
    public function isOwnedBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        return (int) $this->user_id === (int) $user->id;
    }
}
