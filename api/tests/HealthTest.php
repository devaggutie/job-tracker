<?php

declare(strict_types=1);

namespace App\Tests;

final class HealthTest extends ApiTestCase
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
}
