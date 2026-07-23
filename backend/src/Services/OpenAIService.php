<?php

/**
 * Thin wrapper around the OpenAI API. All text content generation
 * (title, description, tags, script/content) goes through this.
 */
class OpenAIService
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $config = Config::get();
        $this->apiKey = $config['openai']['api_key'];
        $this->model = $config['openai']['model'];
    }

    public function generateText(string $systemPrompt, string $userPrompt): string
    {
        if (empty($this->apiKey)) {
            throw new RuntimeException('OPENAI_API_KEY is not configured');
        }

        $payload = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'temperature' => 0.7,
        ];

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 60,
        ]);

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException('OpenAI request failed: ' . $curlError);
        }

        $decoded = json_decode($response, true);

        if ($status >= 400) {
            $message = $decoded['error']['message'] ?? $response;
            throw new RuntimeException("OpenAI API error ($status): $message");
        }

        return trim($decoded['choices'][0]['message']['content'] ?? '');
    }

    public function generateTitle(string $topic): string
    {
        return $this->generateText(
            'You write short, high-CTR YouTube video titles. Respond with only the title, no quotes.',
            "Write a YouTube video title for this topic: $topic"
        );
    }

    public function generateDescription(string $topic, string $title = ''): string
    {
        return $this->generateText(
            'You write concise, informative YouTube video descriptions. Respond with only the description.',
            "Write a YouTube video description. Topic: $topic. Title: $title"
        );
    }

    public function generateTags(string $topic): string
    {
        return $this->generateText(
            'You generate comma-separated SEO tags/keywords for a YouTube video. Respond with only a comma-separated list.',
            "Generate SEO tags for a video about: $topic"
        );
    }

    public function generateContent(string $topic): string
    {
        return $this->generateText(
            'You write video scripts/content for a YouTube channel. Respond with only the script text.',
            "Write a video script for this topic: $topic"
        );
    }
}
