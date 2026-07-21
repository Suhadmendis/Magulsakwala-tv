<?php

/**
 * Central config, sourced from environment variables.
 * Copy .env.example to .env (or set real env vars) before running.
 */

if (!function_exists('env')) {
    function env(string $key, $default = null)
    {
        $value = getenv($key);
        return $value === false ? $default : $value;
    }
}

return [
    'db' => [
        'driver' => env('DB_DRIVER', 'mysql'), // 'mysql' or 'sqlite'
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '3306'),
        'name' => env('DB_NAME', 'magulsakwala_tv'),
        'user' => env('DB_USER', 'root'),
        'password' => env('DB_PASSWORD', ''),
        'sqlite_path' => env('DB_SQLITE_PATH', __DIR__ . '/../database/dev.sqlite'),
    ],
    'storage' => [
        // Root folder that holds one subfolder per account.
        'path' => env('STORAGE_PATH', __DIR__ . '/../../storage'),
    ],
    'openai' => [
        'api_key' => env('OPENAI_API_KEY', ''),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
    ],
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY', ''),
        'model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
        'tts_model' => env('GEMINI_TTS_MODEL', 'gemini-2.5-flash-preview-tts'),
    ],
    'youtube' => [
        'api_base' => 'https://www.googleapis.com/youtube/v3',
        'upload_base' => 'https://www.googleapis.com/upload/youtube/v3',
        'oauth_token_url' => 'https://oauth2.googleapis.com/token',
    ],
];
