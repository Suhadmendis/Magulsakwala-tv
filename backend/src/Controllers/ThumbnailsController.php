<?php

class ThumbnailsController
{
    private const IMAGE_SLOTS = ['background_image', 'image_1', 'image_2', 'image_3', 'image_4', 'image_5'];

    public static function routes(Router $router): void
    {
        $router->get('/api/thumbnails', [self::class, 'index']);
        $router->post('/api/thumbnails', [self::class, 'save']);
        $router->delete('/api/thumbnails/{id}', [self::class, 'destroy']);
    }

    public static function index(array $params): void
    {
        $db = Database::connection();
        $accountId = Request::query('account_id');

        if ($accountId) {
            $stmt = $db->prepare(
                'SELECT thumbnails.* FROM thumbnails
                 JOIN video_operations ON video_operations.id = thumbnails.video_id
                 WHERE video_operations.account_id = ?
                 ORDER BY thumbnails.updated_at DESC'
            );
            $stmt->execute([$accountId]);
        } else {
            $stmt = $db->query('SELECT * FROM thumbnails ORDER BY updated_at DESC');
        }

        Response::json($stmt->fetchAll());
    }

    /**
     * Upsert the thumbnail for a video (1:1). Accepts multipart form data:
     * video_id, account_id, background_image/image_1..5 (files), text_1..3.
     */
    public static function save(array $params): void
    {
        $videoId = $_POST['video_id'] ?? null;
        $accountId = $_POST['account_id'] ?? null;

        if (!$videoId || !$accountId) {
            Response::error('video_id and account_id are required', 422);
            return;
        }

        $db = Database::connection();
        $storage = new StorageService();

        $stmt = $db->prepare('SELECT * FROM thumbnails WHERE video_id = ?');
        $stmt->execute([$videoId]);
        $existing = $stmt->fetch();

        $paths = $existing ?: [];
        try {
            foreach (self::IMAGE_SLOTS as $slot) {
                if (!empty($_FILES[$slot]) && $_FILES[$slot]['error'] === UPLOAD_ERR_OK) {
                    $paths[$slot] = $storage->saveUploadedFile((int) $accountId, 'thumbnails', $_FILES[$slot]);
                }
            }
        } catch (RuntimeException $e) {
            Response::error($e->getMessage(), 502);
            return;
        }

        $texts = [
            'text_1' => $_POST['text_1'] ?? ($existing['text_1'] ?? null),
            'text_2' => $_POST['text_2'] ?? ($existing['text_2'] ?? null),
            'text_3' => $_POST['text_3'] ?? ($existing['text_3'] ?? null),
        ];

        $thumbnailData = array_merge(
            array_fill_keys(self::IMAGE_SLOTS, null),
            $existing ?: [],
            array_intersect_key($paths, array_flip(self::IMAGE_SLOTS)),
            $texts
        );

        try {
            $renderedImage = (new ThumbnailRenderService())->render($thumbnailData, (int) $accountId);
        } catch (RuntimeException $e) {
            Response::error($e->getMessage(), 502);
            return;
        }

        if ($existing) {
            $sql = 'UPDATE thumbnails SET
                        background_image = :background_image, image_1 = :image_1, image_2 = :image_2,
                        image_3 = :image_3, image_4 = :image_4, image_5 = :image_5,
                        text_1 = :text_1, text_2 = :text_2, text_3 = :text_3,
                        rendered_image = :rendered_image
                    WHERE video_id = :video_id';
            $thumbnailId = $existing['id'];
        } else {
            $sql = 'INSERT INTO thumbnails
                        (video_id, background_image, image_1, image_2, image_3, image_4, image_5, text_1, text_2, text_3, rendered_image)
                    VALUES
                        (:video_id, :background_image, :image_1, :image_2, :image_3, :image_4, :image_5, :text_1, :text_2, :text_3, :rendered_image)';
        }

        $stmt = $db->prepare($sql);
        $stmt->execute([
            'video_id' => $videoId,
            'background_image' => $thumbnailData['background_image'],
            'image_1' => $thumbnailData['image_1'],
            'image_2' => $thumbnailData['image_2'],
            'image_3' => $thumbnailData['image_3'],
            'image_4' => $thumbnailData['image_4'],
            'image_5' => $thumbnailData['image_5'],
            'text_1' => $thumbnailData['text_1'],
            'text_2' => $thumbnailData['text_2'],
            'text_3' => $thumbnailData['text_3'],
            'rendered_image' => $renderedImage,
        ]);

        $thumbnailId = $thumbnailId ?? (int) $db->lastInsertId();

        // Wire video_operations.thumbnail_ref -> this thumbnail.
        $stmt = $db->prepare('UPDATE video_operations SET thumbnail_ref = ? WHERE id = ?');
        $stmt->execute([$thumbnailId, $videoId]);

        $stmt = $db->prepare('SELECT * FROM thumbnails WHERE id = ?');
        $stmt->execute([$thumbnailId]);
        Response::json($stmt->fetch(), $existing ? 200 : 201);
    }

    public static function destroy(array $params): void
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM thumbnails WHERE id = ?');
        $stmt->execute([$params['id']]);
        $thumbnail = $stmt->fetch();

        if (!$thumbnail) {
            Response::error('Thumbnail not found', 404);
            return;
        }

        $storage = new StorageService();
        foreach (array_merge(self::IMAGE_SLOTS, ['rendered_image']) as $field) {
            $storage->deleteFile($thumbnail[$field]);
        }

        $stmt = $db->prepare('DELETE FROM thumbnails WHERE id = ?');
        $stmt->execute([$params['id']]);
        Response::json(['deleted' => true]);
    }
}
