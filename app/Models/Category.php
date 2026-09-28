<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'icon',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Category belongs to a user (if custom) or null (if system default).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Category has many transactions.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Category has many saving tips.
     */
    public function savingTips(): HasMany
    {
        return $this->hasMany(SavingTip::class);
    }

    /**
     * Scope categories available to a specific user (their personal + active system defaults).
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('is_default', true)
              ->orWhere('user_id', $userId);
        });
    }

    /**
     * Scope active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope system-wide default categories.
     */
    public function scopeSystem($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Check if this is a system default category.
     */
    public function isSystem(): bool
    {
        return (bool) $this->is_default || is_null($this->user_id);
    }

    /**
     * Check if the category is owned by the given user.
     */
    public function isOwnedBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        return !$this->is_default && $this->user_id === $user->id;
    }
}
