<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', env('APP_URL').'/auth/google/callback'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
    ],

    'gemini' => [
        /*
         * Google AI Studio (Generative Language API) untuk deteksi kartu mahjong.
         * Buat API key di https://aistudio.google.com/app/apikey lalu isi GEMINI_API_KEY.
         */
        'key' => env('GEMINI_API_KEY'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        'model' => env('GEMINI_MODEL', 'gemini-3.5-flash-lite'),
        /*
         * Kuota gratis Gemini dihitung per model. Bila model utama kena 429/503,
         * detector otomatis mencoba model cadangan berikutnya. Model lite dipakai
         * lebih dulu karena jauh lebih cepat sehingga tidak memicu 504 gateway.
         */
        'fallback_models' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('GEMINI_FALLBACK_MODELS', 'gemini-3.1-flash-lite,gemini-3.5-flash')),
        ))),
        /*
         * Resolusi gambar untuk deteksi kartu (low/medium/high).
         */
        'media_resolution' => env('GEMINI_MEDIA_RESOLUTION', 'high'),
        /*
         * Kurangi waktu "thinking" model Gemini 3.x (low/high).
         */
        'thinking_level' => env('GEMINI_THINKING_LEVEL', 'low'),
        /*
         * Gemini 3.x adalah model "thinking": sebagian besar token dipakai untuk
         * penalaran internal sebelum JSON dihasilkan. Batas kecil (mis. 400)
         * membuat JSON terpotong sehingga kartu tidak terdeteksi.
         */
        'max_output_tokens' => (int) env('GEMINI_MAX_OUTPUT_TOKENS', 8192),
        'timeout' => (int) env('GEMINI_TIMEOUT', 30),
        /*
         * Batas total seluruh percobaan model agar request tidak melewati
         * timeout proxy hosting (504 Gateway Time-out).
         */
        'total_timeout' => (int) env('GEMINI_TOTAL_TIMEOUT', 45),
    ],

    'mahjong_ai' => [
        /*
         * Deteksi kartu mahjong memakai OpenAI Vision (endpoint chat/completions).
         * Model harus mendukung input gambar, mis. gpt-4o-mini atau gpt-4.1-mini.
         */
        'model' => env('MAHJONG_OPENAI_MODEL', env('OPENAI_MODEL', 'gpt-4o-mini')),
        'detail' => env('MAHJONG_OPENAI_IMAGE_DETAIL', 'high'),
        'timeout' => (int) env('MAHJONG_OPENAI_TIMEOUT', 60),
    ],

];
