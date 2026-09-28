<?php

namespace App\Services\Ai;

use App\Models\Category;
use App\Models\User;
use App\Services\Ai\Providers\FallbackAiProvider;
use Illuminate\Support\Facades\Cache;

class AiCategorizationService
{
    /**
     * Suggest a category for a given transaction description and type for a user.
     *
     * @param User $user
     * @param string $description
     * @param string $type 'expense' or 'income'
     * @return array
     */
    public static function suggestCategory(User $user, string $description, string $type = 'expense'): array
    {
        $trimmedDesc = trim($description);
        if (mb_strlen($trimmedDesc) < 2) {
            return [
                'success' => true,
                'suggestion' => null,
                'message' => 'Description is too short for AI suggestion.',
            ];
        }

        // Fetch user's available categories for the given type
        $categories = Category::forUser($user->id)
            ->where('type', $type)
            ->get(['id', 'name', 'type', 'icon'])
            ->toArray();

        if (empty($categories)) {
            return [
                'success' => true,
                'suggestion' => null,
                'message' => 'No categories found for this transaction type.',
            ];
        }

        $minConfidence = (float) config('ai.min_confidence', 0.60);
        $provider = AiManager::provider();

        // 1. Attempt with active provider
        $result = $provider->categorizeExpense($trimmedDesc, $categories, $type);

        // 2. If active provider returns null or fails, use FallbackAiProvider heuristics
        if (!$result && !($provider instanceof FallbackAiProvider)) {
            $fallback = new FallbackAiProvider();
            $result = $fallback->categorizeExpense($trimmedDesc, $categories, $type);
        }

        if ($result && !empty($result['category_id'])) {
            // Strict server-side verification: category must exist in user's available set
            $matchedCategory = collect($categories)->firstWhere('id', (int) $result['category_id']);
            $confidence = (float) ($result['confidence'] ?? 0.85);

            if ($matchedCategory && $confidence >= $minConfidence) {
                return [
                    'success' => true,
                    'suggestion' => [
                        'category_id' => $matchedCategory['id'],
                        'category_name' => $matchedCategory['name'],
                        'category_icon' => $matchedCategory['icon'] ?? 'bi-tag',
                        'confidence' => round($confidence, 2),
                        'reason' => (string) ($result['reason'] ?? "Matched based on '{$trimmedDesc}'"),
                    ],
                    'provider' => $provider->getName(),
                ];
            }
        }

        return [
            'success' => true,
            'suggestion' => null,
            'message' => 'No confident category suggestion available. Please choose a category manually.',
        ];
    }
}
