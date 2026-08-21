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
            if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
                throw new RuntimeException("Failed to create storage folder: $dir");
            }
        }
    }

    /** Recursively removes an account's entire storage folder (called when the account is deleted). */
    public function deleteAccountFolder(int $accountId): void
    {
        self::removeDirectory($this->accountDir($accountId));
    }

    /** Deletes a single stored file if it exists. Safe to call with null/missing paths. */
    public function deleteFile(?string $path): void
    {
        if ($path && is_file($path)) {
            unlink($path);
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
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException("Failed to create storage folder: $dir");
        }

        $filename = $filename ?? (uniqid('', true) . '_' . basename($file['name']));
        $destination = $dir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new RuntimeException("Failed to save uploaded file to $destination");
        }

        return $destination;
    }

    public function saveContent(int $accountId, string $subfolder, string $filename, string $content): string
    {
        $dir = $this->accountDir($accountId) . '/' . $subfolder;
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException("Failed to create storage folder: $dir");
        }

        $destination = $dir . '/' . $filename;
        if (file_put_contents($destination, $content) === false) {
            throw new RuntimeException("Failed to write file to $destination");
        }

        return $destination;
    }

    private static function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                self::removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}
