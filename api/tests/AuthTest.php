<?php

declare(strict_types=1);

namespace App\Tests;

use PHPUnit\Framework\TestCase;

final class AuthTest extends TestCase
{
    public function testCsrfEndpointReturnsToken(): void
    {
        [$status, $json] = $this->request('GET', '/auth/csrf');

        self::assertSame(200, $status);
        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $json['data']['csrfToken']);
    }

    public function testRegisterLoginMeLogoutFlow(): void
    {
        $email = 'user-' . bin2hex(random_bytes(4)) . '@example.com';

        [$csrfStatus, $csrfJson, $csrfCookie] = $this->request('GET', '/auth/csrf');
        self::assertSame(200, $csrfStatus);
        $token = $csrfJson['data']['csrfToken'];

        [$regStatus, $regJson, $regCookie] = $this->request(
            'POST',
            '/auth/register',
            ['email' => $email, 'password' => 'password123'],
            ['X-CSRF-Token' => $token],
            $csrfCookie
        );
        self::assertSame(201, $regStatus);
        self::assertSame($email, $regJson['data']['user']['email']);
        self::assertIsInt($regJson['data']['user']['id']);

        [$meStatus, $meJson] = $this->request('GET', '/auth/me', null, [], $regCookie);
        self::assertSame(200, $meStatus);
        self::assertSame($email, $meJson['data']['user']['email']);

        [$loginStatus, $loginJson, $loginCookie] = $this->request(
            'POST',
            '/auth/login',
            ['email' => $email, 'password' => 'password123'],
            ['X-CSRF-Token' => $token],
            $regCookie
        );
        self::assertSame(200, $loginStatus);
        self::assertSame($email, $loginJson['data']['user']['email']);
        self::assertIsString($loginCookie);

        [$logoutStatus] = $this->request(
            'POST',
            '/auth/logout',
            null,
            ['X-CSRF-Token' => $token],
            $loginCookie
        );
        self::assertSame(200, $logoutStatus);

        [$meAfterStatus] = $this->request('GET', '/auth/me', null, [], $loginCookie);
        self::assertSame(401, $meAfterStatus);
    }

    public function testDuplicateEmailReturns409(): void
    {
        $email = 'dupe-' . bin2hex(random_bytes(4)) . '@example.com';

        [, , $cookie] = $this->request('GET', '/auth/csrf');
        [, $csrfJson] = $this->request('GET', '/auth/csrf', null, [], $cookie);
        $token = $csrfJson['data']['csrfToken'];

        [$first, , $firstCookie] = $this->request(
            'POST',
            '/auth/register',
            ['email' => $email, 'password' => 'password123'],
            ['X-CSRF-Token' => $token],
            $cookie
        );
        self::assertSame(201, $first);

        [$second, $secondJson] = $this->request(
            'POST',
            '/auth/register',
            ['email' => $email, 'password' => 'password123'],
            ['X-CSRF-Token' => $token],
            $firstCookie
        );
        self::assertSame(409, $second);
        self::assertSame('email already registered', $secondJson['error']);
    }

    public function testWeakPasswordReturns400(): void
    {
        [, , $cookie] = $this->request('GET', '/auth/csrf');
        [, $csrfJson] = $this->request('GET', '/auth/csrf', null, [], $cookie);
        $token = $csrfJson['data']['csrfToken'];

        [$status, $json] = $this->request(
            'POST',
            '/auth/register',
            ['email' => 'weak@example.com', 'password' => 'short'],
            ['X-CSRF-Token' => $token],
            $cookie
        );

        self::assertSame(400, $status);
        self::assertSame('password must be between 8 and 72 characters', $json['error']);
    }

    public function testLoginWithWrongPasswordReturns401(): void
    {
        [, , $cookie] = $this->request('GET', '/auth/csrf');
        [, $csrfJson] = $this->request('GET', '/auth/csrf', null, [], $cookie);
        $token = $csrfJson['data']['csrfToken'];

        [$status, $json] = $this->request(
            'POST',
            '/auth/login',
            ['email' => 'nobody-' . bin2hex(random_bytes(4)) . '@example.com', 'password' => 'wrongpassword'],
            ['X-CSRF-Token' => $token],
            $cookie
        );

        self::assertSame(401, $status);
        self::assertSame('invalid email or password', $json['error']);
    }

    public function testMeWithoutSessionReturns401(): void
    {
        [$status, $json] = $this->request('GET', '/auth/me');

        self::assertSame(401, $status);
        self::assertSame('unauthenticated', $json['error']);
    }

    public function testPostWithoutCsrfTokenReturns403(): void
    {
        [$status, $json] = $this->request(
            'POST',
            '/auth/login',
            ['email' => 'x@example.com', 'password' => 'password123']
        );

        self::assertSame(403, $status);
        self::assertSame('invalid csrf token', $json['error']);
    }

    public function testPostWithWrongCsrfTokenReturns403(): void
    {
        [, , $cookie] = $this->request('GET', '/auth/csrf');

        [$status, $json] = $this->request(
            'POST',
            '/auth/login',
            ['email' => 'x@example.com', 'password' => 'password123'],
            ['X-CSRF-Token' => str_repeat('0', 64)],
            $cookie
        );

        self::assertSame(403, $status);
        self::assertSame('invalid csrf token', $json['error']);
    }

    public function testRateLimitAfterFiveFailedLogins(): void
    {
        $email = 'ratelimit-' . bin2hex(random_bytes(4)) . '@example.com';

        [, $csrfJson, $cookie] = $this->request('GET', '/auth/csrf');
        $token = $csrfJson['data']['csrfToken'];

        for ($i = 0; $i < 5; $i++) {
            [$status] = $this->request(
                'POST',
                '/auth/login',
                ['email' => $email, 'password' => 'wrongpassword'],
                ['X-CSRF-Token' => $token],
                $cookie
            );
            self::assertSame(401, $status);
        }

        [$status, $json] = $this->request(
            'POST',
            '/auth/login',
            ['email' => $email, 'password' => 'wrongpassword'],
            ['X-CSRF-Token' => $token],
            $cookie
        );

        self::assertSame(429, $status);
        self::assertSame('too many login attempts, try again later', $json['error']);
    }

    /**
     * @return array{0: int, 1: array<string, mixed>|null, 2: string|null}
     */
    private function request(
        string $method,
        string $path,
        ?array $body = null,
        array $headers = [],
        ?string $cookie = null
    ): array {
        $headerLines = ['Content-Type: application/json'];

        if ($cookie !== null && $cookie !== '') {
            $headerLines[] = 'Cookie: ' . $cookie;
        }

        foreach ($headers as $name => $value) {
            $headerLines[] = $name . ': ' . $value;
        }

        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headerLines),
                'content' => $body !== null ? json_encode($body) : '',
                'ignore_errors' => true,
            ],
        ]);

        $raw = file_get_contents('http://127.0.0.1' . $path, false, $context);
        self::assertNotFalse($raw);

        $status = 0;
        $setCookie = null;

        foreach ($http_response_header ?? [] as $header) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $header, $m) === 1) {
                $status = (int) $m[1];
            }

            if (preg_match('/^Set-Cookie:\s*([^;]+)/i', $header, $m) === 1) {
                $setCookie = $m[1];
            }
        }

        return [$status, json_decode($raw, true), $setCookie];
    }
}
