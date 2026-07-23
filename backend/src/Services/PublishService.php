<?php

/**
 * Shared "find the next scheduled video and publish it" logic, used by
 * both the Compose screen's Post button (POST /api/videos/post-next)
 * and the scheduled-uploads cron job.
 */
class PublishService
{
    /**
     * The `scheduled` video for this account whose scheduled_at is closest
     * to the current date/time (used by both Compose preview and posting).
     */
    public function findNextScheduled(int $accountId): ?array
    {
        $db = Database::connection();
        $stmt = $db->prepare("SELECT * FROM video_operations WHERE account_id = ? AND status = 'scheduled'");
        $stmt->execute([$accountId]);
        $videos = $stmt->fetchAll();

        if (empty($videos)) {
            return null;
        }

        $now = time();
        usort($videos, function ($a, $b) use ($now) {
            $diffA = abs(strtotime($a['scheduled_at']) - $now);
            $diffB = abs(strtotime($b['scheduled_at']) - $now);
            return $diffA <=> $diffB;
        });

        return $videos[0];
    }

    public function publishByAccountId(int $accountId): array
    {
        $video = $this->findNextScheduled($accountId);
        if (!$video) {
            throw new RuntimeException('No scheduled video found for this account');
        }

        return $this->publish($video);
    }

    public function publish(array $video): array
    {
        $db = Database::connection();

        $stmt = $db->prepare('SELECT * FROM accounts WHERE id = ?');
        $stmt->execute([$video['account_id']]);
        $account = $stmt->fetch();

        if (!$account) {
            throw new RuntimeException('Account not found for video ' . $video['id']);
        }

        try {
            $youtubeVideoId = (new YouTubeService())->uploadVideo($account, $video);

            $stmt = $db->prepare(
                "UPDATE video_operations
                 SET status = 'published', youtube_video_id = :youtube_video_id, published_at = :published_at, error_message = NULL
                 WHERE id = :id"
            );
            $stmt->execute([
                'youtube_video_id' => $youtubeVideoId,
                'published_at' => date('Y-m-d H:i:s'),
                'id' => $video['id'],
            ]);
        } catch (Throwable $e) {
            $stmt = $db->prepare(
                "UPDATE video_operations SET status = 'failed', error_message = :error_message WHERE id = :id"
            );
            $stmt->execute([
                'error_message' => $e->getMessage(),
                'id' => $video['id'],
            ]);

            throw $e;
        }

        return VideosController::find($video['id']);
    }
}
