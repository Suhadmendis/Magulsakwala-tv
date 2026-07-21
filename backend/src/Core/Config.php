<?php

class Config
{
    private static ?array $data = null;

    public static function get(): array
    {
        if (self::$data === null) {
            self::$data = require __DIR__ . '/../../config/config.php';
        }
        return self::$data;
    }
}
