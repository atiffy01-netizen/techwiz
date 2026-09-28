<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminTipTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'message',
        'type',
        'status',
        'created_by',
    ];

    /**
     * User who created this template.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope for active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for inactive templates.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    /**
     * Scope for announcements.
     */
    public function scopeAnnouncements($query)
    {
        return $query->where('type', 'announcement');
    }

    /**
     * Scope for saving tips.
     */
    public function scopeSavingTips($query)
    {
        return $query->where('type', 'saving_tip');
    }

    /**
     * Check if active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get human-readable badge CSS class for type.
     */
    public function getTypeBadgeClassAttribute(): string
    {
        return match ($this->type) {
            'announcement' => 'bg-primary-subtle text-primary',
            'saving_tip'   => 'bg-success-subtle text-success',
            'general_tip'  => 'bg-info-subtle text-info',
            default        => 'bg-secondary-subtle text-secondary',
        };
    }

    /**
     * Get icon name for the type.
     */
    public function getTypeIconAttribute(): string
    {
        return match ($this->type) {
            'announcement' => 'bi-megaphone-fill',
            'saving_tip'   => 'bi-piggy-bank-fill',
            'general_tip'  => 'bi-info-circle-fill',
            default        => 'bi-card-text',
        };
    }
}
