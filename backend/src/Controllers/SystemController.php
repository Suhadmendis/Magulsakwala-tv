<?php

class SystemController
{
    public static function routes(Router $router): void
    {
        $router->get('/api/system/tables', [self::class, 'tables']);
        $router->get('/api/system/tables/{name}', [self::class, 'tableDetail']);
        $router->get('/api/system/imagegen/info', [self::class, 'imageGenInfo']);
        $router->post('/api/system/imagegen/generate', [self::class, 'imageGenGenerate']);
        $router->post('/api/system/imagegen/img2img', [self::class, 'imageGenImg2Img']);
    }

    /**
     * Ephemeral playground for testing every feature the local SD-Turbo
     * server exposes — unlike AssetsController::generate, nothing here is
     * saved to a table; results are returned inline as base64 for display.
     */
    public static function imageGenInfo(array $params): void
    {
        try {
            Response::json((new ImageGenService())->info());
        } catch (Throwable $e) {
            Response::error($e->getMessage(), 502);
        }
    }

    public static function imageGenGenerate(array $params): void
    {
        $body = Request::jsonBody();
        if (empty($body['prompt'])) {
            Response::error('prompt is required', 422);
            return;
        }

        try {
            $images = (new ImageGenService())->generateImages($body);
            Response::json(['images' => array_map('base64_encode', $images)]);
        } catch (Throwable $e) {
            Response::error($e->getMessage(), 502);
        }
    }

    public static function imageGenImg2Img(array $params): void
    {
        $prompt = $_POST['prompt'] ?? null;
        if (empty($prompt)) {
            Response::error('prompt is required', 422);
            return;
        }
        if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            Response::error('image file is required', 422);
            return;
        }

        $params = [
            'prompt' => $prompt,
            'image' => file_get_contents($_FILES['image']['tmp_name']),
        ];
        foreach (['negative_prompt', 'strength', 'steps', 'guidance_scale', 'seed', 'num_images'] as $field) {
            if (isset($_POST[$field]) && $_POST[$field] !== '') {
                $params[$field] = $_POST[$field];
            }
        }

        try {
            $images = (new ImageGenService())->img2img($params);
            Response::json(['images' => array_map('base64_encode', $images)]);
        } catch (Throwable $e) {
            Response::error($e->getMessage(), 502);
        }
    }

    public static function tables(array $params): void
    {
        $db = Database::connection();
        $stmt = $db->query(
            "SELECT table_name FROM information_schema.tables
             WHERE table_schema = 'public' AND table_type = 'BASE TABLE'
             ORDER BY table_name"
        );
        Response::json(array_column($stmt->fetchAll(), 'table_name'));
    }

    public static function tableDetail(array $params): void
    {
        $db = Database::connection();
        $name = $params['name'];

        // Whitelist against real public tables before the name is
        // interpolated into the LIMIT 1 query below (PDO can't parameterize
        // identifiers).
        $stmt = $db->prepare(
            "SELECT table_name FROM information_schema.tables
             WHERE table_schema = 'public' AND table_type = 'BASE TABLE' AND table_name = ?"
        );
        $stmt->execute([$name]);
        if (!$stmt->fetch()) {
            Response::error('Table not found', 404);
            return;
        }

        $columnsStmt = $db->prepare(
            "SELECT column_name, data_type, is_nullable, column_default
             FROM information_schema.columns
             WHERE table_schema = 'public' AND table_name = ?
             ORDER BY ordinal_position"
        );
        $columnsStmt->execute([$name]);
        $columns = $columnsStmt->fetchAll();

        $quotedName = '"' . str_replace('"', '', $name) . '"';
        $exampleStmt = $db->query("SELECT * FROM {$quotedName} LIMIT 1");
        $example = $exampleStmt->fetch() ?: null;

        Response::json(['name' => $name, 'columns' => $columns, 'example' => $example]);
    }
}
