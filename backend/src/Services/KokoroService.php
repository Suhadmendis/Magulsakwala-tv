<?php

/**
 * Thin HTTP client for a Kokoro TTS endpoint. Kokoro has no PHP
 * implementation and normally runs via Python, which this project doesn't
 * use — so it's wired here as an external HTTP API instead (self-hosted or
 * third-party), same shape as OpenAIService/GeminiService.
 */
class KokoroService
{
    private string $apiUrl;
    private string $apiKey;

    public function __construct()
    {
        $config = Config::get();
        $this->apiUrl = $config['kokoro']['api_url'];
        $this->apiKey = $config['kokoro']['api_key'];
    }

    /**
     * Returns raw WAV audio bytes for the given text.
     */
    public function generateVoiceOver(string $text): string
    {
        if (empty($this->apiUrl)) {
            throw new RuntimeException('KOKORO_API_URL is not configured');
        }

        $headers = ['Content-Type: application/json'];
        if (!empty($this->apiKey)) {
            $headers[] = 'Authorization: Bearer ' . $this->apiKey;
        }

        $ch = curl_init($this->apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode(['text' => $text]),
            CURLOPT_TIMEOUT => 120,
        ]);

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException('Kokoro request failed: ' . $curlError);
        }
        if ($status >= 400) {
            throw new RuntimeException("Kokoro API error ($status): $response");
        }
        if ($response === '') {
            throw new RuntimeException('Kokoro returned empty audio');
        }

        return $response;
    }
}
