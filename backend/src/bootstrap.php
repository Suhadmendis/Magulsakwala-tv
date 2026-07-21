<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Load a .env file (KEY=VALUE per line) into getenv(), if present.
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        putenv(trim($key) . '=' . trim($value));
    }
}

spl_autoload_register(function (string $class) {
    $dirs = [
        __DIR__ . '/Core/',
        __DIR__ . '/Controllers/',
        __DIR__ . '/Services/',
    ];
    foreach ($dirs as $dir) {
        $file = $dir . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

require_once __DIR__ . '/../config/database.php';
