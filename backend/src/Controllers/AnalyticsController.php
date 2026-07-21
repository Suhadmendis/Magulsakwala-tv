<?php

class AnalyticsController
{
    public static function routes(Router $router): void
    {
        $router->get('/api/analytics/channel', [self::class, 'channel']);
        $router->get('/api/analytics/videos/{id}', [self::class, 'video']);
    }

    /** Live channel-level stats (views, watch time proxy, subscribers). No caching. */
    public static function channel(array $params): void
    {
        $account = self::requireAccount(Request::query('account_id'));
        if (!$account) {
            return;
        }

        try {
            Response::json((new YouTubeService())->getChannelStatistics($account));
        } catch (Throwable $e) {
            Response::error($e->getMessage(), 502);
        }
    }

    /** Live per-video performance stats. No caching. */
    public static function video(array $params): void
    {
        $video = VideosController::find($params['id']);
        if (!$video) {
            Response::error('Video not found', 404);
            return;
        }
        if (empty($video['youtube_video_id'])) {
            Response::error('Video has not been published to YouTube yet', 422);
            return;
        }

        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM accounts WHERE id = ?');
        $stmt->execute([$video['account_id']]);
        $account = $stmt->fetch();

        if (!$account) {
            Response::error('Account not found', 404);
            return;
        }

        try {
            Response::json((new YouTubeService())->getVideoStatistics($account, $video['youtube_video_id']));
        } catch (Throwable $e) {
            Response::error($e->getMessage(), 502);
        }
    }

    private static function requireAccount($accountId): ?array
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
