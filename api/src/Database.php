<?php

declare(strict_types=1);

namespace App;

use PDO;
use RuntimeException;

final class Database
{
    private static ?PDO $pdo = null;

    private function __construct()
    {
    }

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            $host = self::env('DB_HOST');
            $port = self::env('DB_PORT');
            $name = self::env('DB_NAME');
            $user = self::env('DB_USER');
            $password = self::env('DB_PASSWORD');

            self::$pdo = new PDO(
                sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name),
                $user,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        }

        return self::$pdo;
    }

    private static function env(string $key): string
    {
        $value = getenv($key);

        if ($value === false || $value === '') {
            throw new RuntimeException(sprintf('Missing environment variable: %s', $key));
        }

        return $value;
    }
}
