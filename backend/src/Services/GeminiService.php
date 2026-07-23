<?php

/**
 * Thin wrapper around the Google Gemini API. Used specifically to turn a
 * video's `content` script into a voice-over audio narration.
 */
class GeminiService
{
    private string $apiKey;
    private string $ttsModel;

    public function __construct()
    {
        $config = Config::get();
        $this->apiKey = $config['gemini']['api_key'];
        $this->ttsModel = $config['gemini']['tts_model'];
    }

    /**
     * Returns raw PCM audio bytes (16-bit, 24kHz mono) generated from the given text.
     */
    public function generateVoiceOver(string $text): string
    {
        if (empty($this->apiKey)) {
            throw new RuntimeException('GEMINI_API_KEY is not configured');
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->ttsModel}:generateContent?key={$this->apiKey}";

        $payload = [
            'contents' => [
                ['parts' => [['text' => $text]]],
            ],
            'generationConfig' => [
                'responseModalities' => ['AUDIO'],
                'speechConfig' => [
                    'voiceConfig' => [
                        'prebuiltVoiceConfig' => ['voiceName' => 'Kore'],
                    ],
                ],
            ],
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 90,
        ]);

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException('Gemini request failed: ' . $curlError);
        }

        $decoded = json_decode($response, true);

        if ($status >= 400) {
            $message = $decoded['error']['message'] ?? $response;
            throw new RuntimeException("Gemini API error ($status): $message");
        }

        $base64Audio = $decoded['candidates'][0]['content']['parts'][0]['inlineData']['data'] ?? null;

        if (!$base64Audio) {
            throw new RuntimeException('Gemini response did not contain audio data');
        }

        return base64_decode($base64Audio);
    }

    /**
     * Wraps raw 16-bit/24kHz mono PCM in a WAV header so it plays in a standard <audio> tag.
     */
    public function pcmToWav(string $pcmData, int $sampleRate = 24000, int $channels = 1, int $bitsPerSample = 16): string
    {
        $byteRate = $sampleRate * $channels * $bitsPerSample / 8;
        $blockAlign = $channels * $bitsPerSample / 8;
        $dataSize = strlen($pcmData);

        $header = 'RIFF';
        $header .= pack('V', 36 + $dataSize);
        $header .= 'WAVE';
        $header .= 'fmt ';
        $header .= pack('V', 16);
        $header .= pack('v', 1); // PCM
        $header .= pack('v', $channels);
        $header .= pack('V', $sampleRate);
        $header .= pack('V', (int) $byteRate);
        $header .= pack('v', (int) $blockAlign);
        $header .= pack('v', $bitsPerSample);
        $header .= 'data';
        $header .= pack('V', $dataSize);

        return $header . $pcmData;
    }
}
