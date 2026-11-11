<?php

declare(strict_types=1);

namespace App\Tests;

use PHPUnit\Framework\TestCase;

abstract class ApiTestCase extends TestCase
{
    /**
     * @return array{0: int, 1: array<string, mixed>|null, 2: string|null}
     */
    protected function request(
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

    /**
     * @return array{0: string, 1: string|null} csrfToken, session cookie
     */
    protected function csrf(): array
    {
        [$status, $json, $cookie] = $this->request('GET', '/auth/csrf');
        self::assertSame(200, $status);

        return [$json['data']['csrfToken'], $cookie];
    }

    /**
     * @return array{0: string, 1: string|null} csrfToken, session cookie for a fresh user
     */
    protected function actingAs(): array
    {
        [$token, $cookie] = $this->csrf();

        [$status, , $sessionCookie] = $this->request(
            'POST',
            '/auth/register',
            [
                'email' => 'user-' . bin2hex(random_bytes(4)) . '@example.com',
                'password' => 'password123',
            ],
            ['X-CSRF-Token' => $token],
            $cookie
        );
        self::assertSame(201, $status);

        return [$token, $sessionCookie];
    }

    /**
     * @return array{0: int, 1: array<string, mixed>|null, 2: string|null}
     */
    protected function createApplication(string $cookie, string $token, array $overrides = []): array
    {
        return $this->request(
            'POST',
            '/applications',
            array_merge(['company' => 'Acme Corp', 'role' => 'Developer'], $overrides),
            ['X-CSRF-Token' => $token],
            $cookie
        );
    }
}
