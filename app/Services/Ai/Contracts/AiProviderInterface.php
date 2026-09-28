<?php

namespace App\Services\Ai\Contracts;

interface AiProviderInterface
{
    /**
     * Get the provider's unique identifier.
     */
    public function getName(): string;

    /**
     * Suggest a category from available categories based on transaction description.
     *
     * @param string $description User-provided transaction description
     * @param array $availableCategories List of allowed categories with 'id' and 'name'
     * @param string $type 'expense' or 'income'
     * @return array|null Structured result: ['category_name' => string, 'confidence' => float, 'reason' => string] or null
     */
    public function categorizeExpense(string $description, array $availableCategories, string $type = 'expense'): ?array;

    /**
     * Generate monthly spending insights and suggestions from aggregated financial statistics.
     *
     * @param array $aggregatedData Aggregated metrics (totals, category breakdown, MoM trends, budgets)
     * @return array|null Structured result: ['summary' => string, 'highlights' => array, 'actions' => array] or null
     */
    public function generateMonthlyInsight(array $aggregatedData): ?array;
}
