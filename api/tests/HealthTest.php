<?php

declare(strict_types=1);

namespace App\Tests;

use PHPUnit\Framework\TestCase;

final class HealthTest extends TestCase
{
    public function testHealthReturnsOkWithDatabase(): void
    {
        [$status, $json] = $this->request('GET', '/health');

        self::assertSame(200, $status);
        self::assertSame('ok', $json['data']['status']);
        self::assertSame('ok', $json['data']['db']);
    }

    public function testUnknownPathReturnsJson404(): void
    {
        [$status, $json] = $this->request('GET', '/nope');

        self::assertSame(404, $status);
        self::assertSame('not found', $json['error']);
    }

    public function testWrongMethodReturnsJson405(): void
    {
        [$status, $json] = $this->request('POST', '/health');

        self::assertSame(405, $status);
        self::assertSame('method not allowed', $json['error']);
    }

    /**
     * @return array{0: int, 1: array<string, mixed>|null}
     */
    private function request(string $method, string $path): array
    {
        $context = stream_context_create(['http' => ['method' => $method, 'ignore_errors' => true]]);
        $body = file_get_contents('http://127.0.0.1' . $path, false, $context);
        self::assertNotFalse($body);

        $status = 0;
        foreach ($http_response_header ?? [] as $header) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $header, $m) === 1) {
                $status = (int) $m[1];
            }
        }

        return [$status, json_decode($body, true)];
    }
}
