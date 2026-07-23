<?php

/**
 * Thin wrapper around the YouTube Data API v3, scoped to a single
 * connected account (channel) at a time.
 */
class YouTubeService
{
    private string $apiBase;
    private string $uploadBase;
    private string $tokenUrl;

    public function __construct()
    {
        $config = Config::get();
        $this->apiBase = $config['youtube']['api_base'];
        $this->uploadBase = $config['youtube']['upload_base'];
        $this->tokenUrl = $config['youtube']['oauth_token_url'];
    }

    /**
     * Refreshes the account's access_token if it's expired, persisting the
     * new token/expiry back to the accounts table.
     */
    public function ensureFreshToken(array $account): array
    {
        $expiresAt = $account['token_expires_at'] ?? null;
        $isExpired = !$expiresAt || strtotime($expiresAt) <= time();

        if (!$isExpired) {
            return $account;
        }

        if (empty($account['refresh_token'])) {
            if (empty($account['access_token'])) {
                throw new RuntimeException(
                    "Account {$account['id']} has no YouTube credentials configured (access_token/refresh_token missing)"
                );
            }
            return $account;
        }

        $ch = curl_init($this->tokenUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'client_id' => $account['api_key'],
                'client_secret' => $account['api_secret'],
                'refresh_token' => $account['refresh_token'],
                'grant_type' => 'refresh_token',
            ]),
            CURLOPT_TIMEOUT => 30,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        $decoded = json_decode($response ?: '', true);
        if (empty($decoded['access_token'])) {
            throw new RuntimeException('Failed to refresh YouTube access token for account ' . $account['id']);
        }

        $account['access_token'] = $decoded['access_token'];
        $account['token_expires_at'] = date('Y-m-d H:i:s', time() + (int) ($decoded['expires_in'] ?? 3600));

        $db = Database::connection();
        $stmt = $db->prepare('UPDATE accounts SET access_token = ?, token_expires_at = ? WHERE id = ?');
        $stmt->execute([$account['access_token'], $account['token_expires_at'], $account['id']]);

        return $account;
    }

    /**
     * Uploads a video file with metadata via the multipart upload endpoint.
     * Returns the resulting YouTube video ID.
     */
    public function uploadVideo(array $account, array $video): string
    {
        if (empty($video['video_path']) || !is_file($video['video_path'])) {
            throw new RuntimeException('Video file not found on disk: ' . ($video['video_path'] ?? ''));
        }

        $account = $this->ensureFreshToken($account);

        $metadata = [
            'snippet' => [
                'title' => $video['title'],
                'description' => $video['description'] ?? '',
                'tags' => !empty($video['tags']) ? array_map('trim', explode(',', $video['tags'])) : [],
                'categoryId' => '22',
            ],
            'status' => [
                'privacyStatus' => 'public',
                'selfDeclaredMadeForKids' => false,
            ],
        ];

        $boundary = 'magulsakwala-' . uniqid();
        $body = "--$boundary\r\n";
        $body .= "Content-Type: application/json; charset=UTF-8\r\n\r\n";
        $body .= json_encode($metadata) . "\r\n";
        $body .= "--$boundary\r\n";
        $body .= 'Content-Type: ' . (mime_content_type($video['video_path']) ?: 'video/*') . "\r\n\r\n";
        $body .= file_get_contents($video['video_path']) . "\r\n";
        $body .= "--$boundary--";

        $url = $this->uploadBase . '/videos?uploadType=multipart&part=snippet,status';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $account['access_token'],
                "Content-Type: multipart/related; boundary=$boundary",
                'Content-Length: ' . strlen($body),
            ],
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_TIMEOUT => 0,
        ]);

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($response ?: '', true);

        if ($status >= 400 || empty($decoded['id'])) {
            $message = $decoded['error']['message'] ?? $response;
            throw new RuntimeException("YouTube upload failed ($status): $message");
        }

        return $decoded['id'];
    }

    /**
     * Top-level comment threads across the channel that have no reply from us yet.
     */
    public function listUnrepliedComments(array $account): array
    {
        $account = $this->ensureFreshToken($account);

        $url = $this->apiBase . '/commentThreads?' . http_build_query([
            'part' => 'snippet',
            'allThreadsRelatedToChannelId' => $account['channel_id'],
            'order' => 'time',
            'maxResults' => 50,
        ]);

        $decoded = $this->get($url, $account['access_token']);

        $comments = [];
        foreach ($decoded['items'] ?? [] as $item) {
            $topLevel = $item['snippet']['topLevelComment']['snippet'] ?? null;
            $replyCount = $item['snippet']['totalReplyCount'] ?? 0;

            if (!$topLevel || $replyCount > 0) {
                continue;
            }

            $comments[] = [
                'youtube_comment_id' => $item['snippet']['topLevelComment']['id'],
                'video_id' => $topLevel['videoId'] ?? null,
                'author_name' => $topLevel['authorDisplayName'] ?? '',
                'comment_text' => $topLevel['textDisplay'] ?? '',
                'commented_at' => $topLevel['publishedAt'] ?? null,
            ];
        }

        usort($comments, fn($a, $b) => strcmp($a['commented_at'] ?? '', $b['commented_at'] ?? ''));

        return $comments;
    }

    public function replyToComment(array $account, string $parentCommentId, string $text): array
    {
        $account = $this->ensureFreshToken($account);

        $url = $this->apiBase . '/comments?part=snippet';
        $payload = [
            'snippet' => [
                'parentId' => $parentCommentId,
                'textOriginal' => $text,
            ],
        ];

        return $this->post($url, $account['access_token'], $payload);
    }

    public function getChannelStatistics(array $account): array
    {
        $account = $this->ensureFreshToken($account);

        $url = $this->apiBase . '/channels?' . http_build_query([
            'part' => 'statistics',
            'id' => $account['channel_id'],
        ]);

        $decoded = $this->get($url, $account['access_token']);
        return $decoded['items'][0]['statistics'] ?? [];
    }

    public function getVideoStatistics(array $account, string $youtubeVideoId): array
    {
        $account = $this->ensureFreshToken($account);

        $url = $this->apiBase . '/videos?' . http_build_query([
            'part' => 'statistics',
            'id' => $youtubeVideoId,
        ]);

        $decoded = $this->get($url, $account['access_token']);
        return $decoded['items'][0]['statistics'] ?? [];
    }

    private function get(string $url, string $accessToken): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken],
            CURLOPT_TIMEOUT => 30,
        ]);
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($response ?: '', true) ?: [];

        if ($status >= 400) {
            $message = $decoded['error']['message'] ?? $response;
            throw new RuntimeException("YouTube API error ($status): $message");
        }

        return $decoded;
    }

    private function post(string $url, string $accessToken, array $payload): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 30,
        ]);
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($response ?: '', true) ?: [];

        if ($status >= 400) {
            $message = $decoded['error']['message'] ?? $response;
            throw new RuntimeException("YouTube API error ($status): $message");
        }

        return $decoded;
    }
}
