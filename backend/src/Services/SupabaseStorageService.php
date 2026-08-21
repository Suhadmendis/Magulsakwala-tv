<?php

/**
 * Thin wrapper around the Supabase Storage REST API. Used for audio
 * (voice-over) and final rendered video only — thumbnails/images stay on
 * local disk via StorageService.
 */
class SupabaseStorageService
{
    private string $url;
    private string $serviceRoleKey;

    public function __construct()
    {
        $config = Config::get();
        $this->url = rtrim($config['supabase']['url'], '/');
        $this->serviceRoleKey = $config['supabase']['service_role_key'];
    }

    public function uploadBytes(string $bucket, string $objectPath, string $bytes, string $contentType): string
    {
        $this->requireConfigured();

        $ch = curl_init("{$this->url}/storage/v1/object/{$bucket}/{$objectPath}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->serviceRoleKey,
                'apikey: ' . $this->serviceRoleKey,
                'Content-Type: ' . $contentType,
                'x-upsert: true',
            ],
            CURLOPT_POSTFIELDS => $bytes,
            CURLOPT_TIMEOUT => 120,
        ]);

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException('Supabase Storage upload failed: ' . $curlError);
        }
        if ($status >= 400) {
            throw new RuntimeException("Supabase Storage upload error ($status): $response");
        }

        return $objectPath;
    }

    public function deleteObject(string $bucket, ?string $objectPath): void
    {
        if (empty($objectPath) || empty($this->url) || empty($this->serviceRoleKey)) {
            return;
        }

        $ch = curl_init("{$this->url}/storage/v1/object/{$bucket}/{$objectPath}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->serviceRoleKey,
                'apikey: ' . $this->serviceRoleKey,
            ],
            CURLOPT_TIMEOUT => 30,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }

    public function signedUrl(string $bucket, ?string $objectPath, int $expiresIn = 3600): ?string
    {
        if (empty($objectPath) || empty($this->url) || empty($this->serviceRoleKey)) {
            return null;
        }

        $ch = curl_init("{$this->url}/storage/v1/object/sign/{$bucket}/{$objectPath}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->serviceRoleKey,
                'apikey: ' . $this->serviceRoleKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode(['expiresIn' => $expiresIn]),
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $status >= 400) {
            return null;
        }

        $decoded = json_decode($response, true);
        $signedUrlPath = $decoded['signedURL'] ?? null;

        return $signedUrlPath ? "{$this->url}/storage/v1{$signedUrlPath}" : null;
    }

    private function requireConfigured(): void
    {
        if (empty($this->url)) {
            throw new RuntimeException('SUPABASE_URL is not configured');
        }
        if (empty($this->serviceRoleKey)) {
            throw new RuntimeException('SUPABASE_SERVICE_ROLE_KEY is not configured');
        }
    }
}
