<?php

class AccountsController
{
    public static function routes(Router $router): void
    {
        $router->get('/api/accounts', [self::class, 'index']);
        $router->post('/api/accounts', [self::class, 'create']);
        $router->get('/api/accounts/{id}', [self::class, 'show']);
        $router->put('/api/accounts/{id}', [self::class, 'update']);
        $router->delete('/api/accounts/{id}', [self::class, 'destroy']);
    }

    public static function index(array $params): void
    {
        $db = Database::connection();
        $stmt = $db->query('SELECT * FROM accounts ORDER BY name ASC');
        Response::json($stmt->fetchAll());
    }

    public static function create(array $params): void
    {
        $body = Request::jsonBody();

        if (empty($body['name'])) {
            Response::error('name is required', 422);
            return;
        }

        $db = Database::connection();
        $stmt = $db->prepare(
            'INSERT INTO accounts
                (platform, channel_id, name, url, api_key, api_secret, access_token, refresh_token, token_expires_at)
             VALUES
                (:platform, :channel_id, :name, :url, :api_key, :api_secret, :access_token, :refresh_token, :token_expires_at)'
        );
        $stmt->execute([
            'platform' => $body['platform'] ?? 'youtube',
            'channel_id' => $body['channel_id'] ?? null,
            'name' => $body['name'],
            'url' => $body['url'] ?? null,
            'api_key' => $body['api_key'] ?? null,
            'api_secret' => $body['api_secret'] ?? null,
            'access_token' => $body['access_token'] ?? null,
            'refresh_token' => $body['refresh_token'] ?? null,
            'token_expires_at' => $body['token_expires_at'] ?? null,
        ]);

        $accountId = (int) $db->lastInsertId();

        // Provision the account's dedicated storage folder.
        (new StorageService())->provisionAccountFolder($accountId);

        // Auto-seed the 12 zodiac sign video slots for this channel.
        $placeholders = implode(', ', array_fill(0, count(ZodiacVideosController::SIGNS), '(?, ?)'));
        $seedSql = "INSERT INTO zodiac_videos (account_id, zodiac_sign) VALUES $placeholders";
        $seedArgs = [];
        foreach (ZodiacVideosController::SIGNS as $sign) {
            $seedArgs[] = $accountId;
            $seedArgs[] = $sign;
        }
        $db->prepare($seedSql)->execute($seedArgs);

        $stmt = $db->prepare('SELECT * FROM accounts WHERE id = ?');
        $stmt->execute([$accountId]);
        Response::json($stmt->fetch(), 201);
    }

    public static function show(array $params): void
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM accounts WHERE id = ?');
        $stmt->execute([$params['id']]);
        $account = $stmt->fetch();

        if (!$account) {
            Response::error('Account not found', 404);
            return;
        }

        Response::json($account);
    }

    public static function update(array $params): void
    {
        $db = Database::connection();
        $body = Request::jsonBody();

        $fields = ['platform', 'channel_id', 'name', 'url', 'api_key', 'api_secret', 'access_token', 'refresh_token', 'token_expires_at'];
        $updates = [];
        $values = ['id' => $params['id']];

        foreach ($fields as $field) {
            if (array_key_exists($field, $body)) {
                $updates[] = "$field = :$field";
                $values[$field] = $body[$field];
            }
        }

        if (empty($updates)) {
            Response::error('No fields to update', 422);
            return;
        }

        $sql = 'UPDATE accounts SET ' . implode(', ', $updates) . ' WHERE id = :id';
        $stmt = $db->prepare($sql);
        $stmt->execute($values);

        $stmt = $db->prepare('SELECT * FROM accounts WHERE id = ?');
        $stmt->execute([$params['id']]);
        $account = $stmt->fetch();

        if (!$account) {
            Response::error('Account not found', 404);
            return;
        }

        Response::json($account);
    }

    public static function destroy(array $params): void
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT id FROM accounts WHERE id = ?');
        $stmt->execute([$params['id']]);
        if (!$stmt->fetch()) {
            Response::error('Account not found', 404);
            return;
        }

        $stmt = $db->prepare('DELETE FROM accounts WHERE id = ?');
        $stmt->execute([$params['id']]);

        // Row deletion cascades video_operations/thumbnails in the DB; the
        // on-disk folder is this app's responsibility to clean up.
        (new StorageService())->deleteAccountFolder((int) $params['id']);

        Response::json(['deleted' => true]);
    }

    /** Shared account lookup used by controllers that need account credentials for an external call. */
    public static function requireAccount($accountId): ?array
    {
        if (!$accountId) {
            Response::error('account_id is required', 422);
            return null;
        }

        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM accounts WHERE id = ?');
        $stmt->execute([$accountId]);
        $account = $stmt->fetch();

        if (!$account) {
            Response::error('Account not found', 404);
            return null;
        }

        return $account;
    }
}
