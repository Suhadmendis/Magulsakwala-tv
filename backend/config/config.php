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
        'host' => env('DB_HOST', 'db.ddjrybbhhcwuiczwncfc.supabase.co'),
        'port' => env('DB_PORT', '5432'),
        'name' => env('DB_NAME', 'postgres'),
        'user' => env('DB_USER', 'postgres'),
        'password' => env('DB_PASSWORD', ''),
    ],
    'storage' => [
        // Root folder that holds one subfolder per account.
        'path' => env('STORAGE_PATH', __DIR__ . '/../../storage'),
    ],
    'openai' => [
        'api_key' => env('OPENAI_API_KEY', ''),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
    ],
    'kokoro' => [
        'api_url' => env('KOKORO_API_URL', ''),
        'api_key' => env('KOKORO_API_KEY', ''),
    ],
    'imagegen' => [
        'api_url' => env('IMAGEGEN_API_URL', ''),
    ],
    'supabase' => [
        'url' => env('SUPABASE_URL', ''),
        'service_role_key' => env('SUPABASE_SERVICE_ROLE_KEY', ''),
    ],
    'youtube' => [
        'api_base' => 'https://www.googleapis.com/youtube/v3',
        'upload_base' => 'https://www.googleapis.com/upload/youtube/v3',
        'oauth_token_url' => 'https://oauth2.googleapis.com/token',
        'oauth_auth_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
        // Must exactly match an "Authorized redirect URI" on the Google
        // Cloud OAuth client (Web application type).
        'oauth_redirect_uri' => env('YOUTUBE_OAUTH_REDIRECT_URI', 'http://localhost:8100/api/oauth/youtube/callback'),
    ],
    // Where to send the browser back after the OAuth callback finishes.
    'frontend_url' => env('FRONTEND_URL', 'http://localhost:8000'),
];
