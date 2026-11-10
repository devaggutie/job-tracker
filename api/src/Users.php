<?php

declare(strict_types=1);

namespace App;

final class Users
{
    private function __construct()
    {
    }

    public static function create(string $email, string $password): array
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = Database::connection()->prepare(
            'INSERT INTO users (email, password_hash) VALUES (?, ?)'
        );
        $stmt->execute([$email, $hash]);

        return [
            'id' => (int) Database::connection()->lastInsertId(),
            'email' => $email,
        ];
    }

    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public static function findById(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT id, email FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return ['id' => (int) $row['id'], 'email' => $row['email']];
    }
}
