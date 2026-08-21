<?php

/**
 * Thin HTTP client for the local SD-Turbo image generation server
 * (image-gen/server.py, documented Python exception in CLAUDE.md — same
 * shape as KokoroService/kokoro-tts). Exposes every operation the model
 * server supports: info, text-to-image, image-to-image.
 */
class ImageGenService
{
    private string $baseUrl;

    public function __construct()
    {
        $config = Config::get();
        // config's imagegen.api_url points at .../generate; derive the base.
        $this->baseUrl = rtrim(preg_replace('#/generate$#', '', $config['imagegen']['api_url']), '/');
    }

    public function info(): array
    {
        $this->requireConfigured();
        return $this->request('GET', '/info', null);
    }

    /**
     * Returns an array of raw PNG byte strings.
     */
    public function generateImages(array $params): array
    {
        $this->requireConfigured();
        $decoded = $this->request('POST', '/generate', $params);
        return array_map(fn($b64) => base64_decode($b64), $decoded['images'] ?? []);
    }

    /**
     * Returns an array of raw PNG byte strings. $params must include
     * 'image' as raw bytes (base64-encoded internally before sending).
     */
    public function img2img(array $params): array
    {
        $this->requireConfigured();
        if (isset($params['image'])) {
            $params['image'] = base64_encode($params['image']);
        }
        $decoded = $this->request('POST', '/img2img', $params);
        return array_map(fn($b64) => base64_decode($b64), $decoded['images'] ?? []);
    }

    /**
     * Convenience wrapper used by AssetsController::generate — one image in, one image out.
     */
    public function generateImage(string $prompt, int $width = 512, int $height = 512): string
    {
        $images = $this->generateImages(['prompt' => $prompt, 'width' => $width, 'height' => $height]);
        if (empty($images)) {
            throw new RuntimeException('Image generation returned no images');
        }
        return $images[0];
    }

    private function request(string $method, string $path, ?array $body): array
    {
        $ch = curl_init($this->baseUrl . $path);
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 180,
        ];
        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_HTTPHEADER] = ['Content-Type: application/json'];
            $options[CURLOPT_POSTFIELDS] = json_encode($body);
        }
        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException('Image generation request failed: ' . $curlError);
        }

        $decoded = json_decode($response, true);

        if ($status >= 400) {
            $message = $decoded['error'] ?? $response;
            throw new RuntimeException("Image generation error ($status): $message");
        }

        return $decoded ?? [];
    }

    private function requireConfigured(): void
    {
        if (empty($this->baseUrl)) {
            throw new RuntimeException('IMAGEGEN_API_URL is not configured');
        }
    }
}
