<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\Budget;
use App\Models\User;
use Carbon\Carbon;

class BudgetAlertService
{
    /**
     * Check user's budgets for a given month and generate alerts if thresholds are reached.
     * Prevents duplicate alerts for the same user, budget, month, and alert state.
     *
     * @param User $user
     * @param string|null $month Format 'YYYY-MM', defaults to current month
     * @return array List of new notifications created during this check
     */
    public static function checkUserBudgets(User $user, ?string $month = null): array
    {
        $month = $month ?: Carbon::now()->format('Y-m');
        $parsedMonth = Carbon::createFromFormat('Y-m', $month);
        $monthLabel = $parsedMonth->format('F Y');

        $budgets = Budget::with('category')
            ->where('user_id', $user->id)
            ->where('month', $month)
            ->get();

        $createdNotifications = [];

        foreach ($budgets as $budget) {
            $categoryName = $budget->category ? $budget->category->name : 'Category';
            $actualSpent = $budget->getActualSpending();
            $limit = (float) $budget->limit_amount;

            if ($limit <= 0) {
                continue;
            }

            $usagePercent = round(($actualSpent / $limit) * 100, 1);

            $alertState = null;
            $title = null;
            $message = null;
            $type = null;

            if ($usagePercent >= Budget::THRESHOLD_OVER_BUDGET) {
                $alertState = 'over_budget';
                $type = 'budget_exceeded';
                $overage = $actualSpent - $limit;
                $title = "Budget Exceeded: {$categoryName}";
                $message = "You are Rs. " . number_format($overage, 2) . " over your {$monthLabel} {$categoryName} budget (Rs. " . number_format($actualSpent, 2) . " / Rs. " . number_format($limit, 2) . ").";
            } elseif ($usagePercent >= Budget::THRESHOLD_NEAR_LIMIT) {
                $alertState = 'near_limit';
                $type = 'budget_near_limit';
                $title = "Budget Near Limit: {$categoryName}";
                $message = "{$categoryName} budget is almost reached. You have used {$usagePercent}% of your Rs. " . number_format($limit, 2) . " limit for {$monthLabel}.";
            }

            if ($alertState) {
                // Prevent duplicate notification for the same user, budget, month, and alert_state
                $exists = AppNotification::where('user_id', $user->id)
                    ->where('budget_id', $budget->id)
                    ->where('month', $month)
                    ->where('alert_state', $alertState)
                    ->exists();

                if (!$exists) {
                    $notif = AppNotification::create([
                        'user_id' => $user->id,
                        'budget_id' => $budget->id,
                        'type' => $type,
                        'title' => $title,
                        'message' => $message,
                        'month' => $month,
                        'alert_state' => $alertState,
                        'data' => [
                            'category_id' => $budget->category_id,
                            'category_name' => $categoryName,
                            'limit_amount' => $limit,
                            'actual_spent' => $actualSpent,
                            'usage_percentage' => $usagePercent,
                            'month' => $month,
                        ],
                    ]);

                    $createdNotifications[] = $notif;
                }
            }
        }

        return $createdNotifications;
    }
}
