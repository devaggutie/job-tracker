<?php

declare(strict_types=1);

namespace App;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $secure = (getenv('SESSION_SECURE') ?: '0') === '1';

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function userId(): ?int
    {
        $id = $_SESSION['user_id'] ?? null;

        return is_int($id) ? $id : null;
    }

    public static function setUserId(int $userId): void
    {
        $_SESSION['user_id'] = $userId;
    }

    public static function destroy(): void
    {
        $_SESSION = [];

        if (session_status() === PHP_SESSION_ACTIVE) {
            $params = session_get_cookie_params();

            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'],
            ]);

            session_destroy();
        }
    }
}
