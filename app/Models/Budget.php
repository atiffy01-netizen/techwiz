<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Budget extends Model
{
    use HasFactory;

    // Centralized Thresholds
    public const THRESHOLD_NEAR_LIMIT = 75.0; // 75%
    public const THRESHOLD_OVER_BUDGET = 100.0; // 100%

    // Centralized Status Constants
    public const STATUS_NO_SPENDING = 'no_spending';
    public const STATUS_ON_TRACK = 'on_track';
    public const STATUS_NEAR_LIMIT = 'near_limit';
    public const STATUS_OVER_BUDGET = 'over_budget';

    protected $fillable = [
        'user_id',
        'category_id',
        'month',
        'limit_amount',
    ];

    protected $casts = [
        'limit_amount' => 'decimal:2',
    ];

    /**
     * A budget belongs to a User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * A budget belongs to a Category.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Notifications related to this budget.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(AppNotification::class, 'budget_id');
    }

    /**
     * Calculate actual spending for this budget in its assigned month.
     */
    public function getActualSpending(): float
    {
        $parsedMonth = Carbon::createFromFormat('Y-m', $this->month);
        $startOfMonth = $parsedMonth->copy()->startOfMonth()->toDateString();
        $endOfMonth = $parsedMonth->copy()->endOfMonth()->toDateString();

        return (float) Transaction::where('user_id', $this->user_id)
            ->where('category_id', $this->category_id)
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');
    }

    /**
     * Calculate usage percentage.
     */
    public function getUsagePercentage(?float $actualSpent = null): float
    {
        $limit = (float) $this->limit_amount;
        if ($limit <= 0) {
            return 0.0;
        }

        $spent = $actualSpent !== null ? $actualSpent : $this->getActualSpending();
        return round(($spent / $limit) * 100, 1);
    }

    /**
     * Determine budget health status based on spending.
     */
    public function getStatus(?float $actualSpent = null): string
    {
        $spent = $actualSpent !== null ? $actualSpent : $this->getActualSpending();
        if ($spent <= 0) {
            return self::STATUS_NO_SPENDING;
        }

        $percent = $this->getUsagePercentage($spent);

        if ($percent >= self::THRESHOLD_OVER_BUDGET) {
            return self::STATUS_OVER_BUDGET;
        }

        if ($percent >= self::THRESHOLD_NEAR_LIMIT) {
            return self::STATUS_NEAR_LIMIT;
        }

        return self::STATUS_ON_TRACK;
    }
}
