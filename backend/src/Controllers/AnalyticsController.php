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
        $account = AccountsController::requireAccount(Request::query('account_id'));
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

        $account = AccountsController::requireAccount($video['account_id']);
        if (!$account) {
            return;
        }

        try {
            Response::json((new YouTubeService())->getVideoStatistics($account, $video['youtube_video_id']));
        } catch (Throwable $e) {
            Response::error($e->getMessage(), 502);
        }
    }
}
