<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\Contracts\AiProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiAiProvider implements AiProviderInterface
{
    protected ?string $apiKey;
    protected string $model;
    protected string $baseUrl;
    protected int $timeout;

    public function __construct()
    {
        $this->apiKey = config('ai.gemini.api_key') ?: config('ai.api_key');
        $this->model = config('ai.gemini.model', 'gemini-1.5-flash');
        $this->baseUrl = config('ai.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta');
        $this->timeout = (int) config('ai.timeout', 10);
    }

    public function getName(): string
    {
        return 'gemini';
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Categorize expense/income via Google Gemini REST API.
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

        $prompt = "You are an advisory financial categorization assistant for university students. " .
            "Categorize the transaction description: \"{$description}\" (Type: {$type}). " .
            "You MUST select from ONLY these available categories: [" . implode(', ', $categoryNames) . "]. " .
            "DO NOT invent new categories. If the description does not clearly match any category, set category to null. " .
            "Respond ONLY with a valid JSON object formatted as: " .
            "{\"category\": \"<matching_name_or_null>\", \"confidence\": <float_0_to_1>, \"reason\": \"<short_explanation>\"}";

        $url = "{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}";

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.1,
                        'responseMimeType' => 'application/json',
                    ],
                ]);

            if ($response->successful()) {
                $rawBody = $response->json();
                $text = $rawBody['candidates'][0]['content']['parts'][0]['text'] ?? '';
                $parsed = json_decode($text, true);

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
                Log::warning('Gemini AI Categorization API returned error: ' . $response->status());
            }
        } catch (\Throwable $e) {
            Log::warning('Gemini AI Categorization request failed: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Generate monthly spending insights via Google Gemini.
     */
    public function generateMonthlyInsight(array $data): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $dataJson = json_encode($data, JSON_PRETTY_PRINT);
        $prompt = "You are a friendly, encouraging university student personal finance advisor for Campus Coin. " .
            "Analyze the following aggregated monthly financial metrics for a student:\n{$dataJson}\n\n" .
            "Requirements:\n" .
            "1. Base your analysis STRICTLY on the supplied data.\n" .
            "2. Keep the tone friendly, constructive, and realistic for a university student.\n" .
            "3. State that this is advisory insight, not certified financial advice.\n" .
            "4. Return ONLY a valid JSON object matching this exact structure:\n" .
            "{\n" .
            "  \"summary\": \"<Concise 2-3 sentence executive summary of spending, savings, and top drivers>\",\n" .
            "  \"highlights\": [\"<Highlight 1>\", \"<Highlight 2>\", \"<Highlight 3>\"],\n" .
            "  \"actions\": [\"<Practical action 1>\", \"<Practical action 2>\"]\n" .
            "}";

        $url = "{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}";

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.2,
                        'responseMimeType' => 'application/json',
                    ],
                ]);

            if ($response->successful()) {
                $rawBody = $response->json();
                $text = $rawBody['candidates'][0]['content']['parts'][0]['text'] ?? '';
                $parsed = json_decode($text, true);

                if (is_array($parsed) && !empty($parsed['summary']) && is_array($parsed['highlights']) && is_array($parsed['actions'])) {
                    return [
                        'summary' => (string) $parsed['summary'],
                        'highlights' => array_slice(array_map('strval', $parsed['highlights']), 0, 5),
                        'actions' => array_slice(array_map('strval', $parsed['actions']), 0, 5),
                    ];
                }
            } else {
                Log::warning('Gemini AI Monthly Insight API returned error: ' . $response->status());
            }
        } catch (\Throwable $e) {
            Log::warning('Gemini AI Monthly Insight request failed: ' . $e->getMessage());
        }

        return null;
    }
}
