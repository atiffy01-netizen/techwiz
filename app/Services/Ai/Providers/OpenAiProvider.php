<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\Contracts\AiProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiProvider implements AiProviderInterface
{
    protected ?string $apiKey;
    protected string $model;
    protected string $baseUrl;
    protected int $timeout;

    public function __construct()
    {
        $this->apiKey = config('ai.openai.api_key') ?: config('ai.api_key');
        $this->model = config('ai.openai.model', 'gpt-4o-mini');
        $this->baseUrl = rtrim(config('ai.openai.base_url', 'https://api.openai.com/v1'), '/');
        $this->timeout = (int) config('ai.timeout', 10);
    }

    public function getName(): string
    {
        return 'openai';
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Categorize expense/income via OpenAI Chat Completions JSON mode.
     */
    public function categorizeExpense(string $description, array $availableCategories, string $type = 'expense'): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $categoryNames = array_column($availableCategories, 'name');
        $categoryLookup = [];
        foreach ($availableCategories as $cat) {
            $categoryLookup[mb_strtolower($cat['name'])] = $cat;
        }

        $systemPrompt = "You are a precise financial categorization assistant for university students. " .
            "Categorize the transaction description into EXACTLY ONE category from this allowed list: [" . implode(', ', $categoryNames) . "]. " .
            "DO NOT invent new categories. If not confident or not applicable, set category to null. " .
            "Return JSON: {\"category\": \"<name_or_null>\", \"confidence\": <float_0_to_1>, \"reason\": \"<string>\"}";

        $userMessage = "Transaction description: \"{$description}\" (Type: {$type})";

        try {
            $response = Http::timeout($this->timeout)
                ->withToken($this->apiKey)
                ->post("{$this->baseUrl}/chat/completions", [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userMessage],
                    ],
                    'response_format' => ['type' => 'json_object'],
                    'temperature' => 0.1,
                ]);

            if ($response->successful()) {
                $rawBody = $response->json();
                $content = $rawBody['choices'][0]['message']['content'] ?? '';
                $parsed = json_decode($content, true);

                if (is_array($parsed) && !empty($parsed['category'])) {
                    $matchedNameLower = mb_strtolower(trim($parsed['category']));
                    if (isset($categoryLookup[$matchedNameLower])) {
                        $matchedCategory = $categoryLookup[$matchedNameLower];
                        return [
                            'category_id' => $matchedCategory['id'],
                            'category_name' => $matchedCategory['name'],
                            'confidence' => (float) ($parsed['confidence'] ?? 0.85),
                            'reason' => (string) ($parsed['reason'] ?? "AI categorized based on '{$description}'"),
                        ];
                    }
                }
            } else {
                Log::warning('OpenAI Categorization API returned error: ' . $response->status());
            }
        } catch (\Throwable $e) {
            Log::warning('OpenAI Categorization request failed: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Generate monthly spending insights via OpenAI Chat Completions.
     */
    public function generateMonthlyInsight(array $data): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $systemPrompt = "You are a friendly, encouraging university student personal finance advisor for Campus Coin. " .
            "Analyze the supplied aggregated financial metrics and provide structured student spending insights. " .
            "Return ONLY a JSON object: {\"summary\": \"<string>\", \"highlights\": [\"<string>\"], \"actions\": [\"<string>\"]}. " .
            "Advisory only, not certified financial advice.";

        $userMessage = "Aggregated monthly data:\n" . json_encode($data, JSON_PRETTY_PRINT);

        try {
            $response = Http::timeout($this->timeout)
                ->withToken($this->apiKey)
                ->post("{$this->baseUrl}/chat/completions", [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userMessage],
                    ],
                    'response_format' => ['type' => 'json_object'],
                    'temperature' => 0.2,
                ]);

            if ($response->successful()) {
                $rawBody = $response->json();
                $content = $rawBody['choices'][0]['message']['content'] ?? '';
                $parsed = json_decode($content, true);

                if (is_array($parsed) && !empty($parsed['summary']) && is_array($parsed['highlights']) && is_array($parsed['actions'])) {
                    return [
                        'summary' => (string) $parsed['summary'],
                        'highlights' => array_slice(array_map('strval', $parsed['highlights']), 0, 5),
                        'actions' => array_slice(array_map('strval', $parsed['actions']), 0, 5),
                    ];
                }
            } else {
                Log::warning('OpenAI Monthly Insight API returned error: ' . $response->status());
            }
        } catch (\Throwable $e) {
            Log::warning('OpenAI Monthly Insight request failed: ' . $e->getMessage());
        }

        return null;
    }
}
