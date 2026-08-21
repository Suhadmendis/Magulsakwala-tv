<?php

class FindingsController
{
    public static function routes(Router $router): void
    {
        $router->post('/api/findings/import', [self::class, 'import']);
    }

    /**
     * Body: { "account_id": <auto-filled by the frontend from the selected account>,
     *         "elements": [ { "topic": "..." }, ... ] }  <- this is what the user pastes
     */
    public static function import(array $params): void
    {
        $body = Request::jsonBody();

        $accountId = $body['account_id'] ?? null;
        $elements = $body['elements'] ?? null;

        if (!$accountId) {
            Response::error('account_id is required', 422);
            return;
        }
        if (!is_array($elements) || empty($elements)) {
            Response::error('elements must be a non-empty array', 422);
            return;
        }

        $db = Database::connection();
        $stmt = $db->prepare(
            "INSERT INTO video_operations (account_id, topic, status) VALUES (:account_id, :topic, 'draft')"
        );

        $insertedIds = [];
        foreach ($elements as $element) {
            $topic = is_array($element) ? ($element['topic'] ?? null) : null;
            if (!$topic) {
                continue;
            }
            $stmt->execute(['account_id' => $accountId, 'topic' => $topic]);
            $insertedIds[] = (int) $db->lastInsertId();
        }

        $created = [];
        if (!empty($insertedIds)) {
            $placeholders = implode(',', array_fill(0, count($insertedIds), '?'));
            $fetch = $db->prepare("SELECT * FROM video_operations WHERE id IN ($placeholders) ORDER BY id ASC");
            $fetch->execute($insertedIds);
            $created = $fetch->fetchAll();
        }

        Response::json(['created' => $created, 'count' => count($created)], 201);
    }
}
