<?php

declare(strict_types=1);

namespace App;

final class RateLimiter
{
    private const MAX_ATTEMPTS = 5;
    private const WINDOW_MINUTES = 15;

    private function __construct()
    {
    }

    public static function tooMany(string $ip, string $email): bool
    {
        $sql = sprintf(
            'SELECT COUNT(*) FROM login_attempts
             WHERE ip = ? AND email = ? AND attempted_at > (NOW() - INTERVAL %d MINUTE)',
            self::WINDOW_MINUTES
        );

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([$ip, $email]);

        return (int) $stmt->fetchColumn() >= self::MAX_ATTEMPTS;
    }

    public static function record(string $ip, string $email): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO login_attempts (ip, email) VALUES (?, ?)'
        );
        $stmt->execute([$ip, $email]);
    }

    public static function clear(string $ip, string $email): void
    {
        $stmt = Database::connection()->prepare(
            'DELETE FROM login_attempts WHERE ip = ? AND email = ?'
        );
        $stmt->execute([$ip, $email]);
    }
}
