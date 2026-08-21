<?php

class ThumbnailPresetsController
{
    private const IMAGE_SLOTS = ['background_image', 'image_1', 'image_2', 'image_3', 'image_4', 'image_5'];

    public static function routes(Router $router): void
    {
        $router->get('/api/thumbnail-presets', [self::class, 'index']);
        $router->post('/api/thumbnail-presets', [self::class, 'create']);
        $router->delete('/api/thumbnail-presets/{id}', [self::class, 'destroy']);
    }

    public static function index(array $params): void
    {
        $db = Database::connection();
        $channelId = Request::query('channel_id');

        if ($channelId) {
            $stmt = $db->prepare('SELECT * FROM m_thumbnails WHERE channel_id = ? ORDER BY updated_at DESC');
            $stmt->execute([$channelId]);
        } else {
            $stmt = $db->query('SELECT * FROM m_thumbnails ORDER BY updated_at DESC');
        }

        Response::json($stmt->fetchAll());
    }

    /**
     * Save a new thumbnail preset (multipart form data):
     * channel_id, background_image/image_1..5 (files), text_1..3.
     */
    public static function create(array $params): void
    {
        $channelId = $_POST['channel_id'] ?? null;

        if (!$channelId) {
            Response::error('channel_id is required', 422);
            return;
        }

        $db = Database::connection();
        $storage = new StorageService();

        $paths = [];
        try {
            foreach (self::IMAGE_SLOTS as $slot) {
                if (!empty($_FILES[$slot]) && $_FILES[$slot]['error'] === UPLOAD_ERR_OK) {
                    $paths[$slot] = $storage->saveUploadedFile((int) $channelId, 'thumbnail_presets', $_FILES[$slot]);
                }
            }
        } catch (RuntimeException $e) {
            Response::error($e->getMessage(), 502);
            return;
        }

        $thumbnailData = array_merge(
            array_fill_keys(self::IMAGE_SLOTS, null),
            $paths,
            [
                'text_1' => $_POST['text_1'] ?? null,
                'text_2' => $_POST['text_2'] ?? null,
                'text_3' => $_POST['text_3'] ?? null,
            ]
        );

        try {
            $renderedImage = (new ThumbnailRenderService())->render($thumbnailData, (int) $channelId);
        } catch (RuntimeException $e) {
            Response::error($e->getMessage(), 502);
            return;
        }

        $referenceNo = ReferenceCodeService::next('thum');

        $stmt = $db->prepare(
            'INSERT INTO m_thumbnails
                (reference_no, channel_id, background_image, image_1, image_2, image_3, image_4, image_5, text_1, text_2, text_3, rendered_image)
            VALUES
                (:reference_no, :channel_id, :background_image, :image_1, :image_2, :image_3, :image_4, :image_5, :text_1, :text_2, :text_3, :rendered_image)'
        );
        $stmt->execute([
            'reference_no' => $referenceNo,
            'channel_id' => $channelId,
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

        $stmt = $db->prepare('SELECT * FROM m_thumbnails WHERE reference_no = ?');
        $stmt->execute([$referenceNo]);
        Response::json($stmt->fetch(), 201);
    }

    public static function destroy(array $params): void
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM m_thumbnails WHERE id = ?');
        $stmt->execute([$params['id']]);
        $preset = $stmt->fetch();

        if (!$preset) {
            Response::error('Thumbnail preset not found', 404);
            return;
        }

        $storage = new StorageService();
        foreach (array_merge(self::IMAGE_SLOTS, ['rendered_image']) as $field) {
            $storage->deleteFile($preset[$field]);
        }

        $stmt = $db->prepare('DELETE FROM m_thumbnails WHERE id = ?');
        $stmt->execute([$params['id']]);
        Response::json(['deleted' => true]);
    }
}
