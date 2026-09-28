<?php

namespace App\Services\Ai;

use App\Services\Ai\Contracts\AiProviderInterface;
use App\Services\Ai\Providers\FallbackAiProvider;
use App\Services\Ai\Providers\GeminiAiProvider;
use App\Services\Ai\Providers\OpenAiProvider;

class AiManager
{
    protected static ?AiProviderInterface $customProvider = null;

    /**
     * Get the resolved AI provider instance.
     */
    public static function provider(): AiProviderInterface
    {
        if (static::$customProvider !== null) {
            return static::$customProvider;
        }

        $enabled = (bool) config('ai.enabled', true);
        if (!$enabled) {
            return new FallbackAiProvider();
        }

        $providerName = config('ai.provider', 'fallback');

        return match ($providerName) {
            'gemini' => new GeminiAiProvider(),
            'openai' => new OpenAiProvider(),
            default => new FallbackAiProvider(),
        };
    }

    /**
     * Set a custom/mock provider (useful for automated testing).
     */
    public static function setProvider(?AiProviderInterface $provider): void
    {
        static::$customProvider = $provider;
    }

    /**
     * Reset custom provider to default configuration.
     */
    public static function resetProvider(): void
    {
        static::$customProvider = null;
    }
}
