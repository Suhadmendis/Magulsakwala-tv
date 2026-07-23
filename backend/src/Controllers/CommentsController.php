<?php

class CommentsController
{
    public static function routes(Router $router): void
    {
        $router->get('/api/comments', [self::class, 'index']);
        $router->post('/api/comments/{youtube_comment_id}/suggest-reply', [self::class, 'suggestReply']);
        $router->post('/api/comments/{youtube_comment_id}/reply', [self::class, 'reply']);
    }

    /** Live-fetches un-replied comments from YouTube, oldest first. No local table. */
    public static function index(array $params): void
    {
        $account = self::requireAccount(Request::query('account_id'));
        if (!$account) {
            return;
        }

        try {
            $comments = (new YouTubeService())->listUnrepliedComments($account);
            Response::json($comments);
        } catch (Throwable $e) {
            Response::error($e->getMessage(), 502);
        }
    }

    /** Generates an AI-suggested reply for a comment. Does not send it. */
    public static function suggestReply(array $params): void
    {
        $body = Request::jsonBody();
        if (empty($body['comment_text'])) {
            Response::error('comment_text is required', 422);
            return;
        }

        try {
            $reply = (new OpenAIService())->generateText(
                'You write short, friendly replies to YouTube comments on behalf of the channel owner. Respond with only the reply text.',
                'Comment: ' . $body['comment_text']
            );
            Response::json(['suggested_reply' => $reply]);
        } catch (Throwable $e) {
            Response::error($e->getMessage(), 502);
        }
    }

    /** Manually confirm-and-send a reply directly to YouTube. */
    public static function reply(array $params): void
    {
        $body = Request::jsonBody();
        $account = self::requireAccount($body['account_id'] ?? null);
        if (!$account) {
            return;
        }

        if (empty($body['reply_text'])) {
            Response::error('reply_text is required', 422);
            return;
        }

        try {
            $result = (new YouTubeService())->replyToComment(
                $account,
                $params['youtube_comment_id'],
                $body['reply_text']
            );
            Response::json($result, 201);
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
