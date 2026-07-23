<?php

class VideosController
{
    /** Fields required before a video can move from draft -> prepared. */
    private const REQUIRED_FOR_PREPARED = ['title', 'description', 'tags', 'thumbnail_ref', 'video_type', 'video_path'];

    public static function routes(Router $router): void
    {
        $router->post('/api/videos/upload', [self::class, 'upload']);
        $router->get('/api/videos', [self::class, 'index']);
        $router->get('/api/videos/search', [self::class, 'search']);
        $router->get('/api/videos/{id}', [self::class, 'show']);
        $router->put('/api/videos/{id}', [self::class, 'update']);
        $router->put('/api/videos/{id}/schedule', [self::class, 'schedule']);
        $router->delete('/api/videos/{id}', [self::class, 'destroy']);
    }

    public static function index(array $params): void
    {
        $db = Database::connection();
        $accountId = Request::query('account_id');

        $sql = 'SELECT * FROM video_operations';
        $args = [];
        if ($accountId) {
            $sql .= ' WHERE account_id = ?';
            $args[] = $accountId;
        }
        $sql .= ' ORDER BY updated_at DESC';

        $stmt = $db->prepare($sql);
        $stmt->execute($args);
        Response::json($stmt->fetchAll());
    }

    public static function search(array $params): void
    {
        $db = Database::connection();
        $accountId = Request::query('account_id');
        $videoType = Request::query('video_type');
        $keyword = Request::query('q');

        $where = ["status IN ('draft', 'prepared', 'scheduled')"];
        $args = [];

        if ($accountId) {
            $where[] = 'account_id = ?';
            $args[] = $accountId;
        }
        if ($videoType) {
            $where[] = 'video_type = ?';
            $args[] = $videoType;
        }
        if ($keyword) {
            $where[] = '(title LIKE ? OR topic LIKE ?)';
            $args[] = "%$keyword%";
            $args[] = "%$keyword%";
        }

        $sql = 'SELECT * FROM video_operations WHERE ' . implode(' AND ', $where) . ' ORDER BY updated_at DESC';
        $stmt = $db->prepare($sql);
        $stmt->execute($args);
        Response::json($stmt->fetchAll());
    }

    public static function show(array $params): void
    {
        $video = self::find($params['id']);
        if (!$video) {
            Response::error('Video not found', 404);
            return;
        }
        Response::json($video);
    }

    public static function upload(array $params): void
    {
        $accountId = $_POST['account_id'] ?? null;
        if (!$accountId) {
            Response::error('account_id is required', 422);
            return;
        }

        $db = Database::connection();
        $videoPath = null;

        if (!empty($_FILES['video'])) {
            $videoPath = (new StorageService())->saveUploadedFile((int) $accountId, 'videos', $_FILES['video']);
        }

        $stmt = $db->prepare(
            'INSERT INTO video_operations (account_id, video_path, topic, status)
             VALUES (:account_id, :video_path, :topic, :status)'
        );
        $stmt->execute([
            'account_id' => $accountId,
            'video_path' => $videoPath,
            'topic' => $_POST['topic'] ?? null,
            'status' => 'draft',
        ]);

        Response::json(self::find((int) $db->lastInsertId()), 201);
    }

    public static function update(array $params): void
    {
        $db = Database::connection();
        $video = self::find($params['id']);
        if (!$video) {
            Response::error('Video not found', 404);
            return;
        }

        $body = Request::jsonBody();
        $fields = [
            'video_path', 'topic', 'content', 'voice_enabled', 'voice_over_path',
            'title', 'description', 'tags', 'thumbnail_ref', 'video_type',
        ];

        $updates = [];
        $values = ['id' => $params['id']];
        foreach ($fields as $field) {
            if (array_key_exists($field, $body)) {
                $updates[] = "$field = :$field";
                $values[$field] = $body[$field];
                $video[$field] = $body[$field];
            }
        }

        // Auto-advance draft -> prepared once every required field is filled.
        if ($video['status'] === 'draft' && self::isCompleteForPrepared($video)) {
            $updates[] = 'status = :status';
            $values['status'] = 'prepared';
        }

        if (!empty($updates)) {
            $sql = 'UPDATE video_operations SET ' . implode(', ', $updates) . ' WHERE id = :id';
            $stmt = $db->prepare($sql);
            $stmt->execute($values);
        }

        Response::json(self::find($params['id']));
    }

    public static function schedule(array $params): void
    {
        $db = Database::connection();
        $video = self::find($params['id']);
        if (!$video) {
            Response::error('Video not found', 404);
            return;
        }

        if ($video['status'] !== 'prepared' && $video['status'] !== 'scheduled') {
            Response::error('Video must be prepared before it can be scheduled', 422);
            return;
        }

        $body = Request::jsonBody();
        if (empty($body['scheduled_at'])) {
            Response::error('scheduled_at is required', 422);
            return;
        }

        $stmt = $db->prepare(
            "UPDATE video_operations SET scheduled_at = :scheduled_at, status = 'scheduled' WHERE id = :id"
        );
        $stmt->execute([
            'scheduled_at' => $body['scheduled_at'],
            'id' => $params['id'],
        ]);

        Response::json(self::find($params['id']));
    }

    public static function destroy(array $params): void
    {
        $db = Database::connection();
        $stmt = $db->prepare('DELETE FROM video_operations WHERE id = ?');
        $stmt->execute([$params['id']]);
        Response::json(['deleted' => true]);
    }

    private static function isCompleteForPrepared(array $video): bool
    {
        foreach (self::REQUIRED_FOR_PREPARED as $field) {
            if (empty($video[$field])) {
                return false;
            }
        }
        return true;
    }

    public static function find($id): ?array
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM video_operations WHERE id = ?');
        $stmt->execute([$id]);
        $video = $stmt->fetch();
        return $video ?: null;
    }
}
