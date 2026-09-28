<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\Contracts\AiProviderInterface;

class FallbackAiProvider implements AiProviderInterface
{
    /**
     * Common keyword heuristics mapped to standard category names.
     */
    protected array $keywordMap = [
        'expense' => [
            'Food' => [
                'cafe', 'cafeteria', 'canteen', 'coffee', 'chai', 'tea', 'lunch', 'dinner', 'breakfast',
                'burger', 'pizza', 'biryani', 'snack', 'restaurant', 'foodpanda', 'subway', 'kfc',
                'mcdonalds', 'bakery', 'dining', 'groceries', 'grocery', 'mart', 'supermarket', 'dhaba',
                'shawarma', 'roll', 'juice', 'shake', 'meal', 'baking', 'fruits', 'veggies'
            ],
            'Transport' => [
                'uber', 'careem', 'bykea', 'indrive', 'bus', 'metro', 'fuel', 'petrol', 'diesel',
                'fare', 'taxi', 'rickshaw', 'train', 'ticket', 'commute', 'ride', 'parking', 'toll',
                'speedo', 'van', 'auto'
            ],
            'Subscriptions' => [
                'netflix', 'spotify', 'youtube', 'apple', 'icloud', 'chatgpt', 'github', 'coursera',
                'prime', 'amazon prime', 'gym', 'software', 'subscription', 'vpn', 'patreon', 'udemy',
                'linkedin premium', 'playstation', 'xbox'
            ],
            'Academics' => [
                'book', 'stationary', 'stationery', 'print', 'photocopy', 'photocopies', 'xerox',
                'semester', 'tuition', 'fee', 'course', 'exam', 'notes', 'library', 'textbook',
                'lab', 'assignment', 'calculator', 'project material', 'pen', 'notebook', 'register'
            ],
            'Hostel/Rent' => [
                'hostel', 'rent', 'dorm', 'room', 'mess', 'electricity', 'utility', 'water bill',
                'gas bill', 'wifi', 'internet bill', 'maintenance', 'landlord', 'accommodation'
            ],
            'Entertainment' => [
                'cinema', 'movie', 'game', 'gaming', 'steam', 'concert', 'party', 'outing', 'bowling',
                'arcade', 'netflix party', 'boardgame', 'amusement', 'festival'
            ],
            'Miscellaneous' => [
                'misc', 'other', 'general', 'shopping', 'clothes', 'shoes', 'haircut', 'salon',
                'medicine', 'pharmacy', 'medical', 'repair'
            ],
        ],
        'income' => [
            'Allowance' => [
                'allowance', 'pocket money', 'father', 'dad', 'mother', 'mom', 'parents', 'family',
                'monthly allowance', 'home transfer', 'stipend'
            ],
            'Part-time Job' => [
                'freelance', 'salary', 'wages', 'job', 'tutoring', 'client', 'gig', 'upwork', 'fiverr',
                'internship', 'ta stipend', 'teaching assistant', 'project payout', 'part time'
            ],
            'Scholarship' => [
                'scholarship', 'merit', 'financial aid', 'grant', 'fellowship', 'hec', 'award'
            ],
            'Gift' => [
                'gift', 'eidi', 'birthday', 'prize', 'bonus', 'won', 'competition'
            ],
            'Other Income' => [
                'other', 'refund', 'reimbursement', 'cashback', 'sold', 'sale', 'interest'
            ],
        ],
    ];

    public function getName(): string
    {
        return 'fallback';
    }

    /**
     * Categorize expense or income description via deterministic heuristic matching.
     */
    public function categorizeExpense(string $description, array $availableCategories, string $type = 'expense'): ?array
    {
        $desc = mb_strtolower(trim($description));
        if (mb_strlen($desc) < 2) {
            return null;
        }

        $typeKey = ($type === 'income') ? 'income' : 'expense';
        $categoryLookup = [];
        foreach ($availableCategories as $cat) {
            $categoryLookup[mb_strtolower($cat['name'])] = $cat;
        }

        $rules = $this->keywordMap[$typeKey] ?? [];
        $bestMatch = null;
        $bestScore = 0;
        $matchedKeyword = '';

        foreach ($rules as $categoryName => $keywords) {
            $catLower = mb_strtolower($categoryName);

            // Direct name match in available categories
            $targetCategory = null;
            if (isset($categoryLookup[$catLower])) {
                $targetCategory = $categoryLookup[$catLower];
            } else {
                // Fuzzy match against available category names
                foreach ($categoryLookup as $availName => $catObj) {
                    if (str_contains($availName, $catLower) || str_contains($catLower, $availName)) {
                        $targetCategory = $catObj;
                        break;
                    }
                }
            }

            if (!$targetCategory) {
                continue;
            }

            foreach ($keywords as $kw) {
                if (preg_match('/\b' . preg_quote($kw, '/') . '\b/i', $desc)) {
                    $score = 0.88;
                    // Exact keyword match gives high confidence
                    if ($desc === $kw || str_starts_with($desc, $kw . ' ') || str_ends_with($desc, ' ' . $kw)) {
                        $score = 0.94;
                    }

                    if ($score > $bestScore) {
                        $bestScore = $score;
                        $bestMatch = $targetCategory;
                        $matchedKeyword = $kw;
                    }
                }
            }
        }

        if ($bestMatch && $bestScore >= 0.60) {
            return [
                'category_id' => $bestMatch['id'],
                'category_name' => $bestMatch['name'],
                'confidence' => $bestScore,
                'reason' => "The description relates to '" . ucfirst($matchedKeyword) . "' ({$bestMatch['name']}).",
            ];
        }

        return null;
    }

    /**
     * Generate structured monthly spending insight from aggregated statistics.
     */
    public function generateMonthlyInsight(array $data): ?array
    {
        $monthLabel = $data['month_label'] ?? 'this month';
        $totalExpense = (float) ($data['total_expense'] ?? 0.0);
        $totalIncome = (float) ($data['total_income'] ?? 0.0);
        $netBalance = (float) ($data['net_balance'] ?? ($totalIncome - $totalExpense));
        $topCategory = $data['top_category_name'] ?? null;
        $topCategoryPct = (float) ($data['top_category_percent'] ?? 0.0);
        $topCategoryAmount = (float) ($data['top_category_amount'] ?? 0.0);
        $momExpenseChange = isset($data['expense_change_percent']) ? (float) $data['expense_change_percent'] : null;
        $hasHistory = !empty($data['has_history']);

        // Summary generation
        if ($totalExpense <= 0 && $totalIncome <= 0) {
            $summary = "No transactions have been logged for {$monthLabel} yet. Start recording your daily expenses and income to generate actionable spending insights.";
        } elseif ($totalExpense <= 0) {
            $summary = "You recorded Rs. " . number_format($totalIncome, 2) . " in income for {$monthLabel} with zero expenses logged so far. Maintaining this discipline allows you to maximize your savings.";
        } elseif ($netBalance >= 0) {
            $rate = $totalIncome > 0 ? round(($netBalance / $totalIncome) * 100, 1) : 0.0;
            $summary = "In {$monthLabel}, you maintained a positive financial balance of Rs. " . number_format($netBalance, 2) . ($rate > 0 ? " (a {$rate}% savings rate)" : "") . ". " .
                ($topCategory ? "Your primary expense driver was {$topCategory}, accounting for {$topCategoryPct}% of all outflows." : "Your expenses remained well distributed.");
        } else {
            $overspent = abs($netBalance);
            $summary = "Your expenses (Rs. " . number_format($totalExpense, 2) . ") exceeded your income by Rs. " . number_format($overspent, 2) . " in {$monthLabel}. " .
                ($topCategory ? "{$topCategory} was your largest cost center at Rs. " . number_format($topCategoryAmount, 2) . " ({$topCategoryPct}%)." : "Reviewing discretionary spending can restore a positive balance.");
        }

        // Highlights list
        $highlights = [];
        if ($topCategory && $topCategoryPct > 0) {
            $highlights[] = "Dominant Expense: {$topCategory} represented {$topCategoryPct}% (Rs. " . number_format($topCategoryAmount, 2) . ") of total monthly spending.";
        }

        if ($momExpenseChange !== null) {
            if ($momExpenseChange > 10) {
                $highlights[] = "Spending Surge: Monthly expenses grew by {$momExpenseChange}% compared to the previous month.";
            } elseif ($momExpenseChange < -10) {
                $highlights[] = "Disciplined Reduction: Monthly expenses dropped by " . abs($momExpenseChange) . "% compared to the previous period.";
            } else {
                $highlights[] = "Stable Outflow: Spending remained consistent with past month trends ({$momExpenseChange}% variance).";
            }
        } elseif (!$hasHistory) {
            $highlights[] = "Initial Baseline: This month sets your baseline for future month-over-month trend analytics.";
        }

        if ($netBalance >= 1000) {
            $highlights[] = "Cash Flow Surplus: Net savings of Rs. " . number_format($netBalance, 2) . " achieved.";
        } elseif ($netBalance < 0) {
            $highlights[] = "Deficit Alert: Outflows exceeded inflows by Rs. " . number_format(abs($netBalance), 2) . ".";
        }

        // Actionable suggestions
        $actions = [];
        if ($topCategoryPct >= 35.0 && $topCategory) {
            $actions[] = "Set a dedicated weekly cap on {$topCategory} to prevent it from consuming over a third of your monthly allowance.";
        }

        if (!empty($data['near_limit_budgets'])) {
            $catNames = implode(', ', $data['near_limit_budgets']);
            $actions[] = "Monitor categories approaching their limits ({$catNames}) to avoid end-of-month budget breaches.";
        }

        if ($netBalance > 2000) {
            $actions[] = "Allocate at least Rs. " . number_format(min($netBalance, 5000), 2) . " of your surplus directly to your emergency fund or savings goal.";
        } else {
            $actions[] = "Review recurring subscription services and small frequent purchases for easy savings opportunities.";
        }

        return [
            'summary' => $summary,
            'highlights' => $highlights,
            'actions' => $actions,
        ];
    }
}
