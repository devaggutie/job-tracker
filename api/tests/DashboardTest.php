<?php

declare(strict_types=1);

namespace App\Tests;

final class DashboardTest extends ApiTestCase
{
    public function testDashboardRequiresAuthentication(): void
    {
        [$status, $json] = $this->request('GET', '/dashboard');

        self::assertSame(401, $status);
        self::assertSame('unauthenticated', $json['error']);
    }

    public function testEmptyDashboardIsZeroFilled(): void
    {
        [, $cookie] = $this->actingAs();

        [$status, $json] = $this->request('GET', '/dashboard', null, [], $cookie);

        self::assertSame(200, $status);
        self::assertSame(
            ['applied' => 0, 'interviewing' => 0, 'offer' => 0, 'rejected' => 0, 'accepted' => 0],
            $json['data']['countsByStatus']
        );
        self::assertCount(8, $json['data']['applicationsPerWeek']);
        self::assertSame(
            array_fill(0, 8, 0),
            array_column($json['data']['applicationsPerWeek'], 'count')
        );
        self::assertSame([], $json['data']['followUps']);
        self::assertSame(date('Y-m-d'), $json['data']['today']);
    }

    public function testDashboardAggregatesOwnData(): void
    {
        [$token, $cookie] = $this->actingAs();

        $this->createApplication($cookie, $token, [
            'status' => 'applied',
            'date_applied' => date('Y-m-d'),
            'follow_up_date' => date('Y-m-d', strtotime('-3 days')),
        ]);
        $this->createApplication($cookie, $token, [
            'status' => 'applied',
            'date_applied' => date('Y-m-d'),
            'follow_up_date' => date('Y-m-d'),
        ]);
        $this->createApplication($cookie, $token, [
            'status' => 'interviewing',
            'date_applied' => date('Y-m-d'),
        ]);
        $this->createApplication($cookie, $token, [
            'status' => 'offer',
            'follow_up_date' => date('Y-m-d', strtotime('+3 days')),
        ]);

        [$status, $json] = $this->request('GET', '/dashboard', null, [], $cookie);
        self::assertSame(200, $status);

        $counts = $json['data']['countsByStatus'];
        self::assertSame(2, $counts['applied']);
        self::assertSame(1, $counts['interviewing']);
        self::assertSame(1, $counts['offer']);
        self::assertSame(0, $counts['rejected']);
        self::assertSame(0, $counts['accepted']);

        $weeks = $json['data']['applicationsPerWeek'];
        self::assertCount(8, $weeks);
        self::assertSame(3, array_sum(array_column($weeks, 'count')));
        self::assertSame(3, $weeks[7]['count']);

        $today = new \DateTimeImmutable('today');
        $expectedMonday = $today->modify(sprintf('-%d days', (int) $today->format('N') - 1));
        self::assertSame($expectedMonday->format('Y-m-d'), $weeks[7]['weekStart']);

        $followUps = $json['data']['followUps'];
        self::assertCount(2, $followUps);
        self::assertSame(date('Y-m-d', strtotime('-3 days')), $followUps[0]['follow_up_date']);
        self::assertSame(date('Y-m-d'), $followUps[1]['follow_up_date']);
    }

    public function testDashboardIsScopedToCurrentUser(): void
    {
        [$tokenA, $cookieA] = $this->actingAs();
        $this->createApplication($cookieA, $tokenA, [
            'status' => 'applied',
            'date_applied' => date('Y-m-d'),
            'follow_up_date' => date('Y-m-d'),
        ]);

        [, $cookieB] = $this->actingAs();

        [$status, $json] = $this->request('GET', '/dashboard', null, [], $cookieB);

        self::assertSame(200, $status);
        self::assertSame(0, $json['data']['countsByStatus']['applied']);
        self::assertSame([], $json['data']['followUps']);
        self::assertSame(
            array_fill(0, 8, 0),
            array_column($json['data']['applicationsPerWeek'], 'count')
        );
    }
}
