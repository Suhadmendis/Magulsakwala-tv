<?php

class ComposeController
{
    public static function routes(Router $router): void
    {
        $router->get('/api/compose/next', [self::class, 'next']);
        $router->post('/api/videos/post-next', [self::class, 'postNext']);
    }

    public static function next(array $params): void
    {
        $accountId = Request::query('account_id');
        if (!$accountId) {
            Response::error('account_id is required', 422);
            return;
        }

        $video = (new PublishService())->findNextScheduled((int) $accountId);
        if (!$video) {
            Response::json(['video' => null]);
            return;
        }

        $thumbnail = null;
        if (!empty($video['thumbnail_ref'])) {
            $db = Database::connection();
            $stmt = $db->prepare('SELECT * FROM thumbnails WHERE id = ?');
            $stmt->execute([$video['thumbnail_ref']]);
            $thumbnail = $stmt->fetch() ?: null;
        }

        Response::json(['video' => $video, 'thumbnail' => $thumbnail]);
    }

    public static function postNext(array $params): void
    {
        $body = Request::jsonBody();
        $accountId = $body['account_id'] ?? Request::query('account_id');

        if (!$accountId) {
            Response::error('account_id is required', 422);
            return;
        }

        try {
            $video = (new PublishService())->publishByAccountId((int) $accountId);
            Response::json($video);
        } catch (Throwable $e) {
            Response::error($e->getMessage(), 502);
        }
    }
}
