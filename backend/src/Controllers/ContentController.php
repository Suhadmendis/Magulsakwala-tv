<?php

class ContentController
{
    public static function routes(Router $router): void
    {
        $router->post('/api/content/generate/title', [self::class, 'title']);
        $router->post('/api/content/generate/description', [self::class, 'description']);
        $router->post('/api/content/generate/tags', [self::class, 'tags']);
        $router->post('/api/content/generate/content', [self::class, 'content']);
        $router->post('/api/content/generate/voice-over', [self::class, 'voiceOver']);
        $router->post('/api/content/generate/thumbnail', [self::class, 'thumbnail']);
    }

    public static function title(array $params): void
    {
        $video = self::requireVideo();
        if (!$video) {
            return;
        }

        try {
            $title = (new OpenAIService())->generateTitle($video['topic'] ?? '');
            Response::json(['title' => $title]);
        } catch (Throwable $e) {
            Response::error($e->getMessage(), 502);
        }
    }

    public static function description(array $params): void
    {
        $video = self::requireVideo();
        if (!$video) {
            return;
        }

        try {
            $description = (new OpenAIService())->generateDescription($video['topic'] ?? '', $video['title'] ?? '');
            Response::json(['description' => $description]);
        } catch (Throwable $e) {
            Response::error($e->getMessage(), 502);
        }
    }

    public static function tags(array $params): void
    {
        $video = self::requireVideo();
        if (!$video) {
            return;
        }

        try {
            $tags = (new OpenAIService())->generateTags($video['topic'] ?? '');
            Response::json(['tags' => $tags]);
        } catch (Throwable $e) {
            Response::error($e->getMessage(), 502);
        }
    }

    public static function content(array $params): void
    {
        $video = self::requireVideo();
        if (!$video) {
            return;
        }

        try {
            $content = (new OpenAIService())->generateContent($video['topic'] ?? '');
            Response::json(['content' => $content]);
        } catch (Throwable $e) {
            Response::error($e->getMessage(), 502);
        }
    }

    public static function voiceOver(array $params): void
    {
        $video = self::requireVideo();
        if (!$video) {
            return;
        }

        if (empty($video['content'])) {
            Response::error('Video has no content/script to narrate yet', 422);
            return;
        }

        try {
            $wav = (new KokoroService())->generateVoiceOver($video['content']);

            $objectPath = "accounts/{$video['account_id']}/audio/voice_over_{$video['id']}_" . time() . '.wav';
            $storage = new SupabaseStorageService();
            $storage->uploadBytes('media', $objectPath, $wav, 'audio/wav');

            $db = Database::connection();
            $stmt = $db->prepare('UPDATE video_operations SET voice_over_path = ? WHERE id = ?');
            $stmt->execute([$objectPath, $video['id']]);

            Response::json([
                'voice_over_path' => $objectPath,
                'voice_over_url' => $storage->signedUrl('media', $objectPath),
            ]);
        } catch (Throwable $e) {
            Response::error($e->getMessage(), 502);
        }
    }

    public static function thumbnail(array $params): void
    {
        // AI image generation for thumbnails is not wired to a specific
        // provider yet; use the Thumbnails editor (POST /api/thumbnails)
        // to compose one from uploaded images/text in the meantime.
        Response::error('AI thumbnail image generation is not implemented yet', 501);
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
