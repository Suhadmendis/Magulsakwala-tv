<?php

class ZodiacVideosController
{
    public const SIGNS = [
        'aries', 'taurus', 'gemini', 'cancer', 'leo', 'virgo',
        'libra', 'scorpio', 'sagittarius', 'capricorn', 'aquarius', 'pisces',
    ];

    public static function routes(Router $router): void
    {
        $router->get('/api/zodiac-videos', [self::class, 'index']);
        $router->get('/api/zodiac-videos/{id}', [self::class, 'show']);
        $router->put('/api/zodiac-videos/{id}', [self::class, 'update']);
        $router->post('/api/zodiac-videos/{id}/generate-voice-over', [self::class, 'generateVoiceOver']);
        $router->post('/api/zodiac-videos/{id}/generate-thumbnail', [self::class, 'generateThumbnail']);
        $router->post('/api/zodiac-videos/{id}/render', [self::class, 'render']);
        $router->delete('/api/zodiac-videos/{id}/media', [self::class, 'deleteMedia']);
        $router->delete('/api/zodiac-videos/{id}/thumbnail', [self::class, 'deleteThumbnail']);
    }

    public static function deleteMedia(array $params): void
    {
        $video = self::find($params['id']);
        if (!$video) {
            Response::error('Zodiac video not found', 404);
            return;
        }

        $storage = new SupabaseStorageService();
        $storage->deleteObject('media', $video['voice_over_path']);
        $storage->deleteObject('media', $video['rendered_video_path']);

        $db = Database::connection();
        $db->prepare('UPDATE zodiac_videos SET voice_over_path = NULL, rendered_video_path = NULL WHERE id = ?')
            ->execute([$params['id']]);

        Response::json(self::find($params['id']));
    }

    public static function deleteThumbnail(array $params): void
    {
        $video = self::find($params['id']);
        if (!$video) {
            Response::error('Zodiac video not found', 404);
            return;
        }

        $db = Database::connection();

        if (!empty($video['thumbnail_preset_ref'])) {
            $stmt = $db->prepare('SELECT rendered_image FROM m_thumbnails WHERE id = ?');
            $stmt->execute([$video['thumbnail_preset_ref']]);
            $thumbnail = $stmt->fetch();

            $db->prepare('UPDATE zodiac_videos SET thumbnail_preset_ref = NULL WHERE id = ?')
                ->execute([$params['id']]);

            if ($thumbnail) {
                (new StorageService())->deleteFile($thumbnail['rendered_image']);
                $db->prepare('DELETE FROM m_thumbnails WHERE id = ?')->execute([$video['thumbnail_preset_ref']]);
            }
        }

        Response::json(self::find($params['id']));
    }

    public static function index(array $params): void
    {
        $db = Database::connection();
        $accountId = Request::query('account_id');

        $sql = 'SELECT * FROM zodiac_videos';
        $args = [];
        if ($accountId) {
            $sql .= ' WHERE account_id = ?';
            $args[] = $accountId;
        }
        $sql .= " ORDER BY array_position(ARRAY['aries','taurus','gemini','cancer','leo','virgo','libra','scorpio','sagittarius','capricorn','aquarius','pisces']::varchar[], zodiac_sign)";

        $stmt = $db->prepare($sql);
        $stmt->execute($args);
        $videos = $stmt->fetchAll();

        $storage = new SupabaseStorageService();
        foreach ($videos as &$video) {
            $video['voice_over_url'] = $storage->signedUrl('media', $video['voice_over_path']);
            $video['rendered_video_url'] = $storage->signedUrl('media', $video['rendered_video_path']);
        }

        Response::json($videos);
    }

    public static function show(array $params): void
    {
        $video = self::find($params['id']);
        if (!$video) {
            Response::error('Zodiac video not found', 404);
            return;
        }
        Response::json($video);
    }

    public static function update(array $params): void
    {
        $db = Database::connection();
        if (!self::find($params['id'])) {
            Response::error('Zodiac video not found', 404);
            return;
        }

        $body = Request::jsonBody();
        $fields = ['title', 'description', 'tags', 'thumbnail_preset_ref', 'video_type'];

        $updates = [];
        $values = ['id' => $params['id']];
        foreach ($fields as $field) {
            if (array_key_exists($field, $body)) {
                $updates[] = "$field = :$field";
                $values[$field] = $body[$field];
            }
        }

        if (!empty($updates)) {
            $sql = 'UPDATE zodiac_videos SET ' . implode(', ', $updates) . ' WHERE id = :id';
            $db->prepare($sql)->execute($values);
        }

        Response::json(self::find($params['id']));
    }

    public static function generateVoiceOver(array $params): void
    {
        $video = self::find($params['id']);
        if (!$video) {
            Response::error('Zodiac video not found', 404);
            return;
        }

        $db = Database::connection();
        $stmt = $db->prepare('SELECT content FROM m_content_feeder WHERE zodiac_video_ref = ? ORDER BY created_at DESC LIMIT 1');
        $stmt->execute([$video['id']]);
        $feeder = $stmt->fetch();

        if (empty($feeder['content'])) {
            Response::error('This zodiac video has no fed content to narrate yet', 422);
            return;
        }

        try {
            $wav = (new KokoroService())->generateVoiceOver($feeder['content']);

            $objectPath = "accounts/{$video['account_id']}/audio/zodiac_voice_over_{$video['id']}_" . time() . '.wav';
            $storage = new SupabaseStorageService();
            $storage->uploadBytes('media', $objectPath, $wav, 'audio/wav');

            $db->prepare('UPDATE zodiac_videos SET voice_over_path = ? WHERE id = ?')->execute([$objectPath, $video['id']]);

            Response::json([
                'voice_over_path' => $objectPath,
                'voice_over_url' => $storage->signedUrl('media', $objectPath),
            ]);
        } catch (Throwable $e) {
            Response::error($e->getMessage(), 502);
        }
    }

    /**
     * Renders a plain black-background + title thumbnail (for now — a real
     * design comes later) and points the sign at it. Called as the middle
     * step of the create flow, between voice-over and video render.
     */
    public static function generateThumbnail(array $params): void
    {
        $video = self::find($params['id']);
        if (!$video) {
            Response::error('Zodiac video not found', 404);
            return;
        }

        $thumbnailData = [
            'background_image' => null,
            'image_1' => null, 'image_2' => null, 'image_3' => null, 'image_4' => null, 'image_5' => null,
            'text_1' => $video['title'] ?: ucfirst($video['zodiac_sign']),
            'text_2' => null,
            'text_3' => null,
        ];

        try {
            $renderedImage = (new ThumbnailRenderService())->render($thumbnailData, (int) $video['account_id']);
        } catch (RuntimeException $e) {
            Response::error($e->getMessage(), 502);
            return;
        }

        $db = Database::connection();
        $referenceNo = ReferenceCodeService::next('thum');
        $stmt = $db->prepare(
            'INSERT INTO m_thumbnails (reference_no, channel_id, text_1, rendered_image)
             VALUES (:reference_no, :channel_id, :text_1, :rendered_image)'
        );
        $stmt->execute([
            'reference_no' => $referenceNo,
            'channel_id' => $video['account_id'],
            'text_1' => $thumbnailData['text_1'],
            'rendered_image' => $renderedImage,
        ]);

        $stmt = $db->prepare('SELECT id FROM m_thumbnails WHERE reference_no = ?');
        $stmt->execute([$referenceNo]);
        $thumbnailId = $stmt->fetch()['id'];

        $db->prepare('UPDATE zodiac_videos SET thumbnail_preset_ref = ? WHERE id = ?')
            ->execute([$thumbnailId, $video['id']]);

        Response::json(self::find($video['id']));
    }

    public static function render(array $params): void
    {
        $video = self::find($params['id']);
        if (!$video) {
            Response::error('Zodiac video not found', 404);
            return;
        }

        if (empty($video['thumbnail_preset_ref'])) {
            Response::error('Zodiac video needs a thumbnail preset before it can be rendered', 422);
            return;
        }
        if (empty($video['voice_over_path'])) {
            Response::error('Zodiac video needs a voice-over before it can be rendered', 422);
            return;
        }

        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM m_thumbnails WHERE id = ?');
        $stmt->execute([$video['thumbnail_preset_ref']]);
        $preset = $stmt->fetch();
        if (!$preset || empty($preset['rendered_image'])) {
            Response::error('Thumbnail preset has no rendered image yet', 422);
            return;
        }

        $storage = new SupabaseStorageService();
        $audioUrl = $storage->signedUrl('media', $video['voice_over_path']);
        if (!$audioUrl) {
            Response::error('Could not access the stored voice-over audio', 502);
            return;
        }

        $tmpAudioPath = sys_get_temp_dir() . '/audio_' . uniqid('', true) . '.wav';
        try {
            $audioBytes = file_get_contents($audioUrl);
            if ($audioBytes === false) {
                throw new RuntimeException('Failed to download voice-over audio');
            }
            file_put_contents($tmpAudioPath, $audioBytes);

            $renderedPath = (new VideoRenderService())->render(
                $preset['rendered_image'],
                $tmpAudioPath,
                $video['video_type'] ?? 'short'
            );

            $objectPath = "accounts/{$video['account_id']}/videos/rendered_zodiac_{$video['id']}_" . time() . '.mp4';
            $storage->uploadBytes('media', $objectPath, file_get_contents($renderedPath), 'video/mp4');

            $db->prepare('UPDATE zodiac_videos SET rendered_video_path = ? WHERE id = ?')->execute([$objectPath, $video['id']]);

            Response::json([
                'rendered_video_path' => $objectPath,
                'rendered_video_url' => $storage->signedUrl('media', $objectPath),
            ]);
        } catch (Throwable $e) {
            Response::error($e->getMessage(), 502);
        } finally {
            if (is_file($tmpAudioPath)) {
                unlink($tmpAudioPath);
            }
            if (isset($renderedPath) && is_file($renderedPath)) {
                unlink($renderedPath);
            }
        }
    }

    public static function find($id): ?array
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM zodiac_videos WHERE id = ?');
        $stmt->execute([$id]);
        $video = $stmt->fetch();
        if (!$video) {
            return null;
        }

        $storage = new SupabaseStorageService();
        $video['voice_over_url'] = $storage->signedUrl('media', $video['voice_over_path']);
        $video['rendered_video_url'] = $storage->signedUrl('media', $video['rendered_video_path']);

        return $video;
    }
}
