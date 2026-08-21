<?php

/**
 * In-app "Connect with Google" flow, replaces manually round-tripping
 * through the OAuth Playground. Standard authorization-code + offline
 * access flow: start() redirects the browser to Google's consent screen,
 * callback() exchanges the returned code for tokens and saves them on the
 * account.
 */
class YouTubeOAuthController
{
    public static function routes(Router $router): void
    {
        $router->get('/api/accounts/{id}/youtube/oauth/start', [self::class, 'start']);
        $router->get('/api/oauth/youtube/callback', [self::class, 'callback']);
    }

    public static function start(array $params): void
    {
        $account = AccountsController::requireAccount($params['id']);
        if (!$account) {
            return;
        }

        if (empty($account['api_key']) || empty($account['api_secret'])) {
            self::redirectToFrontend($params['id'], 'error', 'Add the OAuth client ID/secret to this account first');
            return;
        }

        $config = Config::get()['youtube'];
        $query = http_build_query([
            'client_id' => $account['api_key'],
            'redirect_uri' => $config['oauth_redirect_uri'],
            'response_type' => 'code',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'scope' => 'https://www.googleapis.com/auth/youtube.upload https://www.googleapis.com/auth/youtube',
            'state' => $params['id'],
        ]);

        header('Location: ' . $config['oauth_auth_url'] . '?' . $query);
    }

    public static function callback(array $params): void
    {
        $accountId = Request::query('state');
        $code = Request::query('code');
        $error = Request::query('error');

        if (!$accountId) {
            http_response_code(400);
            echo 'Missing state';
            return;
        }

        if ($error) {
            self::redirectToFrontend($accountId, 'error', $error);
            return;
        }

        $account = AccountsController::requireAccount($accountId);
        if (!$account) {
            return;
        }

        $config = Config::get()['youtube'];
        $ch = curl_init($config['oauth_token_url']);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'client_id' => $account['api_key'],
                'client_secret' => $account['api_secret'],
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $config['oauth_redirect_uri'],
            ]),
            CURLOPT_TIMEOUT => 30,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        $decoded = json_decode($response ?: '', true);
        if (empty($decoded['access_token'])) {
            $message = $decoded['error_description'] ?? $decoded['error'] ?? 'Token exchange failed';
            self::redirectToFrontend($accountId, 'error', $message);
            return;
        }

        $db = Database::connection();
        $stmt = $db->prepare(
            'UPDATE accounts SET access_token = :access_token, token_expires_at = :token_expires_at' .
            (!empty($decoded['refresh_token']) ? ', refresh_token = :refresh_token' : '') .
            ' WHERE id = :id'
        );
        $values = [
            'access_token' => $decoded['access_token'],
            'token_expires_at' => date('Y-m-d H:i:s', time() + (int) ($decoded['expires_in'] ?? 3600)),
            'id' => $accountId,
        ];
        if (!empty($decoded['refresh_token'])) {
            $values['refresh_token'] = $decoded['refresh_token'];
        }
        $stmt->execute($values);

        self::redirectToFrontend($accountId, 'connected', '1');
    }

    private static function redirectToFrontend($accountId, string $key, string $value): void
    {
        // Query string goes before the #hash (hash-based router splits on
        // '/' inside the fragment, a trailing ?query there would corrupt
        // the account id route param).
        $frontendUrl = Config::get()['frontend_url'];
        header("Location: {$frontendUrl}/?{$key}=" . urlencode($value) . "#/account-edit/{$accountId}");
    }
}
