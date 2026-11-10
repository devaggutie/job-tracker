<?php

declare(strict_types=1);

namespace App;

use PDOException;

final class AuthHandler
{
    public function csrf(): array
    {
        return [
            'status' => 200,
            'data' => ['csrfToken' => Csrf::token()],
        ];
    }

    public function register(): array
    {
        $body = $this->jsonBody();
        if ($body === null) {
            return ['status' => 400, 'error' => 'invalid json body'];
        }

        $email = $this->email($body);
        if ($email === null) {
            return ['status' => 400, 'error' => 'a valid email is required'];
        }

        $password = $body['password'] ?? null;
        if (!is_string($password) || strlen($password) < 8 || strlen($password) > 72) {
            return ['status' => 400, 'error' => 'password must be between 8 and 72 characters'];
        }

        try {
            $user = Users::create($email, $password);
        } catch (PDOException $e) {
            if (($e->errorInfo[0] ?? null) === '23000') {
                return ['status' => 409, 'error' => 'email already registered'];
            }

            throw $e;
        }

        Session::regenerate();
        Session::setUserId($user['id']);

        return [
            'status' => 201,
            'data' => [
                'user' => $user,
                'csrfToken' => Csrf::token(),
            ],
        ];
    }

    public function login(): array
    {
        $body = $this->jsonBody();
        if ($body === null) {
            return ['status' => 400, 'error' => 'invalid json body'];
        }

        $email = $this->email($body);
        if ($email === null) {
            return ['status' => 400, 'error' => 'a valid email is required'];
        }

        $password = $body['password'] ?? null;
        if (!is_string($password) || $password === '') {
            return ['status' => 400, 'error' => 'password is required'];
        }

        $ip = $this->ip();

        if (RateLimiter::tooMany($ip, $email)) {
            return ['status' => 429, 'error' => 'too many login attempts, try again later'];
        }

        $user = Users::findByEmail($email);

        if ($user === null || !password_verify($password, $user['password_hash'])) {
            RateLimiter::record($ip, $email);

            return ['status' => 401, 'error' => 'invalid email or password'];
        }

        RateLimiter::clear($ip, $email);
        Session::regenerate();
        Session::setUserId((int) $user['id']);

        return [
            'status' => 200,
            'data' => [
                'user' => [
                    'id' => (int) $user['id'],
                    'email' => $user['email'],
                ],
                'csrfToken' => Csrf::token(),
            ],
        ];
    }

    public function logout(): array
    {
        Session::destroy();

        return [
            'status' => 200,
            'data' => ['message' => 'logged out'],
        ];
    }

    public function me(): array
    {
        $userId = Session::userId();

        if ($userId === null) {
            return ['status' => 401, 'error' => 'unauthenticated'];
        }

        $user = Users::findById($userId);

        if ($user === null) {
            Session::destroy();

            return ['status' => 401, 'error' => 'unauthenticated'];
        }

        return ['status' => 200, 'data' => ['user' => $user]];
    }

    private function jsonBody(): ?array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function email(array $body): ?string
    {
        $email = $body['email'] ?? null;
        if (!is_string($email)) {
            return null;
        }

        $email = strtolower(trim($email));

        return filter_var($email, FILTER_VALIDATE_EMAIL) === false ? null : $email;
    }

    private function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}
