<?php

class ContentFeederController
{
    public static function routes(Router $router): void
    {
        $router->get('/api/content-feeder', [self::class, 'index']);
        $router->post('/api/content-feeder', [self::class, 'create']);
        $router->put('/api/content-feeder/{id}', [self::class, 'update']);
        $router->delete('/api/content-feeder/{id}', [self::class, 'destroy']);
        $router->post('/api/content-feeder/generate/hook', [self::class, 'generateHook']);
        $router->post('/api/content-feeder/generate/content', [self::class, 'generateContent']);
        $router->post('/api/content-feeder/generate/cta', [self::class, 'generateCta']);
    }

    public static function index(array $params): void
    {
        $db = Database::connection();
        $accountId = Request::query('account_id');
        $videoRef = Request::query('video_ref');
        $zodiacVideoRef = Request::query('zodiac_video_ref');

        if ($videoRef) {
            $stmt = $db->prepare('SELECT * FROM m_content_feeder WHERE video_ref = ? ORDER BY created_at DESC');
            $stmt->execute([$videoRef]);
        } elseif ($zodiacVideoRef) {
            $stmt = $db->prepare('SELECT * FROM m_content_feeder WHERE zodiac_video_ref = ? ORDER BY created_at DESC');
            $stmt->execute([$zodiacVideoRef]);
        } elseif ($accountId) {
            $stmt = $db->prepare(
                'SELECT m_content_feeder.* FROM m_content_feeder
                 JOIN video_operations ON video_operations.id = m_content_feeder.video_ref
                 WHERE video_operations.account_id = ?
                 UNION ALL
                 SELECT m_content_feeder.* FROM m_content_feeder
                 JOIN zodiac_videos ON zodiac_videos.id = m_content_feeder.zodiac_video_ref
                 WHERE zodiac_videos.account_id = ?
                 ORDER BY created_at DESC'
            );
            $stmt->execute([$accountId, $accountId]);
        } else {
            $stmt = $db->query('SELECT * FROM m_content_feeder ORDER BY created_at DESC');
        }

        Response::json($stmt->fetchAll());
    }

    public static function create(array $params): void
    {
        $body = Request::jsonBody();

        $videoRef = $body['video_ref'] ?? null;
        $zodiacVideoRef = $body['zodiac_video_ref'] ?? null;

        if (empty($videoRef) && empty($zodiacVideoRef)) {
            Response::error('video_ref or zodiac_video_ref is required', 422);
            return;
        }
        if (!empty($videoRef) && !empty($zodiacVideoRef)) {
            Response::error('Provide only one of video_ref or zodiac_video_ref', 422);
            return;
        }

        if ($videoRef) {
            if (!VideosController::find($videoRef)) {
                Response::error('Video not found', 404);
                return;
            }
        } else {
            if (!ZodiacVideosController::find($zodiacVideoRef)) {
                Response::error('Zodiac video not found', 404);
                return;
            }
        }

        $referenceNo = ReferenceCodeService::next('cf');

        $db = Database::connection();
        $stmt = $db->prepare(
            'INSERT INTO m_content_feeder (reference_no, video_ref, zodiac_video_ref, hook, content, cta)
             VALUES (:reference_no, :video_ref, :zodiac_video_ref, :hook, :content, :cta)'
        );
        $stmt->execute([
            'reference_no' => $referenceNo,
            'video_ref' => $videoRef,
            'zodiac_video_ref' => $zodiacVideoRef,
            'hook' => $body['hook'] ?? null,
            'content' => $body['content'] ?? null,
            'cta' => $body['cta'] ?? null,
        ]);

        if ($zodiacVideoRef) {
            self::onContentFed((int) $zodiacVideoRef);
        }

        $stmt = $db->prepare('SELECT * FROM m_content_feeder WHERE reference_no = ?');
        $stmt->execute([$referenceNo]);
        Response::json($stmt->fetch(), 201);
    }

    public static function update(array $params): void
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM m_content_feeder WHERE id = ?');
        $stmt->execute([$params['id']]);
        $entry = $stmt->fetch();
        if (!$entry) {
            Response::error('Content feeder entry not found', 404);
            return;
        }

        $body = Request::jsonBody();
        $fields = ['hook', 'content', 'cta'];

        $updates = [];
        $values = ['id' => $params['id']];
        foreach ($fields as $field) {
            if (array_key_exists($field, $body)) {
                $updates[] = "$field = :$field";
                $values[$field] = $body[$field];
            }
        }

        if (!empty($updates)) {
            $sql = 'UPDATE m_content_feeder SET ' . implode(', ', $updates) . ' WHERE id = :id';
            $db->prepare($sql)->execute($values);
        }

        if (!empty($entry['zodiac_video_ref'])) {
            self::onContentFed((int) $entry['zodiac_video_ref']);
        }

        $stmt = $db->prepare('SELECT * FROM m_content_feeder WHERE id = ?');
        $stmt->execute([$params['id']]);
        Response::json($stmt->fetch());
    }

    /**
     * Runs whenever content is fed/saved for a zodiac sign video: marks it
     * content_added. Thumbnail generation happens later, as part of the
     * Video Operations create flow (audio -> thumbnail -> video).
     */
    private static function onContentFed(int $zodiacVideoId): void
    {
        $video = ZodiacVideosController::find($zodiacVideoId);
        if (!$video || $video['status'] !== 'draft') {
            return;
        }

        Database::connection()
            ->prepare("UPDATE zodiac_videos SET status = 'content_added' WHERE id = ?")
            ->execute([$zodiacVideoId]);
    }

    public static function destroy(array $params): void
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM m_content_feeder WHERE id = ?');
        $stmt->execute([$params['id']]);
        if (!$stmt->fetch()) {
            Response::error('Content feeder entry not found', 404);
            return;
        }

        $stmt = $db->prepare('DELETE FROM m_content_feeder WHERE id = ?');
        $stmt->execute([$params['id']]);
        Response::json(['deleted' => true]);
    }

    public static function generateHook(array $params): void
    {
        $video = self::requireVideo();
        if (!$video) {
            return;
        }

        try {
            $hook = (new OpenAIService())->generateHook($video['topic'] ?? '');
            Response::json(['hook' => $hook]);
        } catch (Throwable $e) {
            Response::error($e->getMessage(), 502);
        }
    }

    public static function generateContent(array $params): void
    {
        $video = self::requireVideo();
        if (!$video) {
            return;
        }

        try {
            $content = (new OpenAIService())->generateFeederContent($video['topic'] ?? '');
            Response::json(['content' => $content]);
        } catch (Throwable $e) {
            Response::error($e->getMessage(), 502);
        }
    }

    public static function generateCta(array $params): void
    {
        $video = self::requireVideo();
        if (!$video) {
            return;
        }

        try {
            $cta = (new OpenAIService())->generateCta($video['topic'] ?? '');
            Response::json(['cta' => $cta]);
        } catch (Throwable $e) {
            Response::error($e->getMessage(), 502);
        }
    }

    private static function requireVideo(): ?array
    {
        $body = Request::jsonBody();
        if (empty($body['video_id'])) {
            Response::error('video_id is required', 422);
            return null;
        }

        $video = VideosController::find($body['video_id']);
        if (!$video) {
            Response::error('Video not found', 404);
            return null;
        }

        return $video;
    }
}
