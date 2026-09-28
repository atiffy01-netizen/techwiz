<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavingTip extends Model
{
    use HasFactory;

    // Tip Types Constants
    public const TYPE_ABOVE_HISTORICAL_AVERAGE = 'ABOVE_HISTORICAL_AVERAGE';
    public const TYPE_OVER_BUDGET = 'OVER_BUDGET';
    public const TYPE_NEAR_BUDGET_LIMIT = 'NEAR_BUDGET_LIMIT';
    public const TYPE_HIGH_SPENDING_CATEGORY = 'HIGH_SPENDING_CATEGORY';
    public const TYPE_REPEATED_SUBSCRIPTION = 'REPEATED_SUBSCRIPTION';
    public const TYPE_HIGH_DAILY_SPENDING = 'HIGH_DAILY_SPENDING';
    public const TYPE_HIGH_WEEKLY_SPENDING = 'HIGH_WEEKLY_SPENDING';
    public const TYPE_SAVING_OPPORTUNITY = 'SAVING_OPPORTUNITY';
    public const TYPE_IMPROVEMENT = 'IMPROVEMENT';

    protected $fillable = [
        'user_id',
        'category_id',
        'tip_type',
        'title',
        'message',
        'potential_savings',
        'priority',
        'reference_month',
        'is_pinned',
        'dismissed_at',
        'metadata',
    ];

    protected $casts = [
        'potential_savings' => 'decimal:2',
        'priority' => 'integer',
        'is_pinned' => 'boolean',
        'dismissed_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * The tip belongs to a User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The tip may optionally belong to a Category.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Scope to filter tips for a specific user.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for active (not dismissed) tips.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('dismissed_at');
    }

    /**
     * Scope for pinned tips.
     */
    public function scopePinned(Builder $query): Builder
    {
        return $query->where('is_pinned', true);
    }

    /**
     * Scope for dismissed tips.
     */
    public function scopeDismissed(Builder $query): Builder
    {
        return $query->whereNotNull('dismissed_at');
    }

    /**
     * Scope for a specific reference month.
     */
    public function scopeForMonth(Builder $query, string $month): Builder
    {
        return $query->where('reference_month', $month);
    }

    /**
     * Order tips deterministically by priority and potential savings.
     */
    public function scopeRanked(Builder $query): Builder
    {
        return $query->orderBy('is_pinned', 'desc')
            ->orderBy('priority', 'desc')
            ->orderBy('potential_savings', 'desc')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Pin the tip.
     */
    public function pin(): bool
    {
        $this->is_pinned = true;
        return $this->save();
    }

    /**
     * Unpin the tip.
     */
    public function unpin(): bool
    {
        $this->is_pinned = false;
        return $this->save();
    }

    /**
     * Dismiss the tip.
     */
    public function dismiss(): bool
    {
        $this->dismissed_at = now();
        return $this->save();
    }

    /**
     * Restore a dismissed tip.
     */
    public function restore(): bool
    {
        $this->dismissed_at = null;
        return $this->save();
    }

    /**
     * Check if dismissed.
     */
    public function isDismissed(): bool
    {
        return !is_null($this->dismissed_at);
    }

    /**
     * Get visual icon based on tip type.
     */
    public function getTypeIconAttribute(): string
    {
        return match ($this->tip_type) {
            self::TYPE_OVER_BUDGET => 'bi-exclamation-octagon-fill',
            self::TYPE_NEAR_BUDGET_LIMIT => 'bi-exclamation-triangle-fill',
            self::TYPE_ABOVE_HISTORICAL_AVERAGE => 'bi-graph-up-arrow',
            self::TYPE_HIGH_SPENDING_CATEGORY => 'bi-pie-chart-fill',
            self::TYPE_REPEATED_SUBSCRIPTION => 'bi-arrow-repeat',
            self::TYPE_HIGH_DAILY_SPENDING => 'bi-calendar-day',
            self::TYPE_HIGH_WEEKLY_SPENDING => 'bi-calendar-week',
            self::TYPE_IMPROVEMENT => 'bi-trophy-fill',
            self::TYPE_SAVING_OPPORTUNITY => 'bi-piggy-bank-fill',
            default => 'bi-lightbulb-fill',
        };
    }

    /**
     * Get badge CSS class based on tip type.
     */
    public function getTypeBadgeClassAttribute(): string
    {
        return match ($this->tip_type) {
            self::TYPE_OVER_BUDGET => 'cc-badge-expense',
            self::TYPE_NEAR_BUDGET_LIMIT => 'cc-badge-warning',
            self::TYPE_ABOVE_HISTORICAL_AVERAGE => 'cc-badge-warning',
            self::TYPE_HIGH_SPENDING_CATEGORY => 'cc-badge-info',
            self::TYPE_REPEATED_SUBSCRIPTION => 'cc-badge-teal',
            self::TYPE_HIGH_DAILY_SPENDING, self::TYPE_HIGH_WEEKLY_SPENDING => 'cc-badge-secondary',
            self::TYPE_IMPROVEMENT, self::TYPE_SAVING_OPPORTUNITY => 'cc-badge-income',
            default => 'cc-badge-primary',
        };
    }

    /**
     * Get human-readable label for tip type.
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->tip_type) {
            self::TYPE_OVER_BUDGET => 'Over Budget',
            self::TYPE_NEAR_BUDGET_LIMIT => 'Near Budget',
            self::TYPE_ABOVE_HISTORICAL_AVERAGE => 'Above Average',
            self::TYPE_HIGH_SPENDING_CATEGORY => 'Major Expense',
            self::TYPE_REPEATED_SUBSCRIPTION => 'Subscription',
            self::TYPE_HIGH_DAILY_SPENDING => 'Daily Spike',
            self::TYPE_HIGH_WEEKLY_SPENDING => 'Weekly Surge',
            self::TYPE_IMPROVEMENT => 'Improvement',
            self::TYPE_SAVING_OPPORTUNITY => 'Saving Opportunity',
            default => 'Saving Tip',
        };
    }
}
