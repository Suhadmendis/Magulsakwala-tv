<?php

/**
 * Shared controller for every Assets-tab category (Zodiac Signs, Planets,
 * Elements, Moon Phases, Backgrounds, Fonts) — same reference-coded,
 * channel-scoped shape as m_thumbnails, one table per category (see
 * migrations 0006/0007). {category} in the route is validated against
 * CATEGORIES below before ever touching a table/column name.
 */
class AssetsController
{
    private const CATEGORIES = [
        'zodiac-signs' => ['table' => 'm_zodiac_sign_assets', 'prefix' => 'zod', 'file_field' => 'image'],
        'planets' => ['table' => 'm_planet_assets', 'prefix' => 'plnt', 'file_field' => 'image'],
        'elements' => ['table' => 'm_element_assets', 'prefix' => 'elem', 'file_field' => 'image'],
        'moon-phases' => ['table' => 'm_moon_phase_assets', 'prefix' => 'moon', 'file_field' => 'image'],
        'backgrounds' => ['table' => 'm_background_assets', 'prefix' => 'bg', 'file_field' => 'image'],
        'fonts' => ['table' => 'm_font_assets', 'prefix' => 'font', 'file_field' => 'file'],
    ];

    public static function routes(Router $router): void
    {
        $router->get('/api/assets/{category}', [self::class, 'index']);
        $router->post('/api/assets/{category}', [self::class, 'create']);
        $router->post('/api/assets/{category}/generate', [self::class, 'generate']);
        $router->delete('/api/assets/{category}/{id}', [self::class, 'destroy']);
    }

    public static function index(array $params): void
    {
        $config = self::category($params['category']);
        if (!$config) {
            return;
        }

        $db = Database::connection();
        $channelId = Request::query('channel_id');

        if ($channelId) {
            $stmt = $db->prepare("SELECT * FROM {$config['table']} WHERE channel_id = ? ORDER BY created_at DESC");
            $stmt->execute([$channelId]);
        } else {
            $stmt = $db->query("SELECT * FROM {$config['table']} ORDER BY created_at DESC");
        }

        Response::json($stmt->fetchAll());
    }

    public static function create(array $params): void
    {
        $config = self::category($params['category']);
        if (!$config) {
            return;
        }

        $channelId = $_POST['channel_id'] ?? null;
        if (!$channelId) {
            Response::error('channel_id is required', 422);
            return;
        }

        $fileField = $config['file_field'];
        $path = null;
        if (!empty($_FILES[$fileField]) && $_FILES[$fileField]['error'] === UPLOAD_ERR_OK) {
            try {
                $path = (new StorageService())->saveUploadedFile(
                    (int) $channelId,
                    'assets/' . $params['category'],
                    $_FILES[$fileField]
                );
            } catch (RuntimeException $e) {
                Response::error($e->getMessage(), 502);
                return;
            }
        }

        $referenceNo = ReferenceCodeService::next($config['prefix']);

        $db = Database::connection();
        $stmt = $db->prepare(
            "INSERT INTO {$config['table']} (reference_no, channel_id, name, {$fileField})
             VALUES (:reference_no, :channel_id, :name, :file_path)"
        );
        $stmt->execute([
            'reference_no' => $referenceNo,
            'channel_id' => $channelId,
            'name' => $_POST['name'] ?? null,
            'file_path' => $path,
        ]);

        $stmt = $db->prepare("SELECT * FROM {$config['table']} WHERE reference_no = ?");
        $stmt->execute([$referenceNo]);
        Response::json($stmt->fetch(), 201);
    }

    public static function generate(array $params): void
    {
        $config = self::category($params['category']);
        if (!$config) {
            return;
        }
        if ($config['file_field'] !== 'image') {
            Response::error('Image generation is not supported for this category', 422);
            return;
        }

        $body = Request::jsonBody();
        $channelId = $body['channel_id'] ?? null;
        $prompt = $body['prompt'] ?? null;

        if (!$channelId) {
            Response::error('channel_id is required', 422);
            return;
        }
        if (!$prompt) {
            Response::error('prompt is required', 422);
            return;
        }

        try {
            $png = (new ImageGenService())->generateImage($prompt);
            $filename = 'gen_' . uniqid('', true) . '.png';
            $path = (new StorageService())->saveContent((int) $channelId, 'assets/' . $params['category'], $filename, $png);
        } catch (RuntimeException $e) {
            Response::error($e->getMessage(), 502);
            return;
        }

        $referenceNo = ReferenceCodeService::next($config['prefix']);

        $db = Database::connection();
        $stmt = $db->prepare(
            "INSERT INTO {$config['table']} (reference_no, channel_id, name, image)
             VALUES (:reference_no, :channel_id, :name, :image)"
        );
        $stmt->execute([
            'reference_no' => $referenceNo,
            'channel_id' => $channelId,
            'name' => $body['name'] ?? $prompt,
            'image' => $path,
        ]);

        $stmt = $db->prepare("SELECT * FROM {$config['table']} WHERE reference_no = ?");
        $stmt->execute([$referenceNo]);
        Response::json($stmt->fetch(), 201);
    }

    public static function destroy(array $params): void
    {
        $config = self::category($params['category']);
        if (!$config) {
            return;
        }

        $db = Database::connection();
        $stmt = $db->prepare("SELECT * FROM {$config['table']} WHERE id = ?");
        $stmt->execute([$params['id']]);
        $row = $stmt->fetch();

        if (!$row) {
            Response::error('Asset not found', 404);
            return;
        }

        (new StorageService())->deleteFile($row[$config['file_field']]);

        $stmt = $db->prepare("DELETE FROM {$config['table']} WHERE id = ?");
        $stmt->execute([$params['id']]);
        Response::json(['deleted' => true]);
    }

    private static function category(string $key): ?array
    {
        if (!isset(self::CATEGORIES[$key])) {
            Response::error('Unknown asset category', 404);
            return null;
        }
        return self::CATEGORIES[$key];
    }
}
