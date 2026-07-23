<?php

/**
 * Publishes every `scheduled` video whose scheduled_at has passed, across
 * all accounts. Intended to run every few minutes via the system crontab, e.g.:
 *   php-cgi -f cron/process-scheduled-uploads.php
 *   php cron/process-scheduled-uploads.php
 */

require_once __DIR__ . '/../src/bootstrap.php';

$db = Database::connection();
$stmt = $db->prepare(
    "SELECT * FROM video_operations WHERE status = 'scheduled' AND scheduled_at <= ?"
);
$stmt->execute([date('Y-m-d H:i:s')]);
$dueVideos = $stmt->fetchAll();

$publisher = new PublishService();

foreach ($dueVideos as $video) {
    try {
        $publisher->publish($video);
        echo "Published video {$video['id']}\n";
    } catch (Throwable $e) {
        echo "Failed to publish video {$video['id']}: " . $e->getMessage() . "\n";
    }
}

echo count($dueVideos) . " due video(s) processed.\n";
