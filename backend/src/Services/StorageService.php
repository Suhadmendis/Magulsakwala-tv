<?php

/**
 * Local, per-account folder storage (no cloud storage).
 * Layout: storage/accounts/{account_id}/{videos,thumbnails}/
 */
class StorageService
{
    private string $root;

    public function __construct()
    {
        $config = Config::get();
        $this->root = rtrim($config['storage']['path'], '/');
    }

    public function accountDir(int $accountId): string
    {
        return $this->root . '/accounts/' . $accountId;
    }

    /**
     * Creates the account's folder tree. Called when an account row is created.
     */
    public function provisionAccountFolder(int $accountId): void
    {
        $base = $this->accountDir($accountId);
        foreach (['videos', 'thumbnails'] as $sub) {
            $dir = $base . '/' . $sub;
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
        }
    }

    public function videosDir(int $accountId): string
    {
        return $this->accountDir($accountId) . '/videos';
    }

    public function thumbnailsDir(int $accountId): string
    {
        return $this->accountDir($accountId) . '/thumbnails';
    }

    public function saveUploadedFile(int $accountId, string $subfolder, array $file, ?string $filename = null): string
    {
        $dir = $this->accountDir($accountId) . '/' . $subfolder;
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $filename = $filename ?? (uniqid('', true) . '_' . basename($file['name']));
        $destination = $dir . '/' . $filename;
        move_uploaded_file($file['tmp_name'], $destination);

        return $destination;
    }

    public function saveContent(int $accountId, string $subfolder, string $filename, string $content): string
    {
        $dir = $this->accountDir($accountId) . '/' . $subfolder;
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $destination = $dir . '/' . $filename;
        file_put_contents($destination, $content);

        return $destination;
    }
}
