<?php

class ReferenceCodeService
{
    public static function next(string $prefix, int $pad = 8): string
    {
        $stmt = Database::connection()->prepare('SELECT next_reference(:prefix, :pad) AS code');
        $stmt->execute(['prefix' => $prefix, 'pad' => $pad]);
        return $stmt->fetch()['code'];
    }
}
