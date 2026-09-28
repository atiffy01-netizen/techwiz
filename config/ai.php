<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Feature Switch
    |--------------------------------------------------------------------------
    | Master toggle for AI features (Expense Categorization & Monthly Insights).
    | When false, the application automatically uses deterministic fallbacks.
    */
    'enabled' => env('AI_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Default AI Provider
    |--------------------------------------------------------------------------
    | Supported providers: "gemini", "openai", "fallback"
    */
    'provider' => env('AI_PROVIDER', 'fallback'),

    /*
    |--------------------------------------------------------------------------
    | Google Gemini API Configuration
    |--------------------------------------------------------------------------
    */
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY', env('AI_API_KEY')),
        'model' => env('GEMINI_MODEL', 'gemini-1.5-flash'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
    ],

    /*
    |--------------------------------------------------------------------------
    | OpenAI API Configuration
    |--------------------------------------------------------------------------
    */
    'openai' => [
        'api_key' => env('OPENAI_API_KEY', env('AI_API_KEY')),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Request Settings & Cost Control
    |--------------------------------------------------------------------------
    */
    'timeout' => (int) env('AI_TIMEOUT', 10),
    'min_confidence' => 0.60,
    'cache_ttl_minutes' => (int) env('AI_CACHE_TTL', 60),
];
