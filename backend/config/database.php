<?php

class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        $config = Config::get();
        $db = $config['db'];

        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s',
            $db['host'],
            $db['port'],
            $db['name']
        );
        self::$connection = new PDO($dsn, $db['user'], $db['password']);

        self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return self::$connection;
    }
}
