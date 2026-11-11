<?php

declare(strict_types=1);

namespace App\Tests;

final class ApplicationsTest extends ApiTestCase
{
    public function testListRequiresAuthentication(): void
    {
        [$status, $json] = $this->request('GET', '/applications');

        self::assertSame(401, $status);
        self::assertSame('unauthenticated', $json['error']);
    }

    public function testCreateAndShowWithStatusHistory(): void
    {
        [$token, $cookie] = $this->actingAs();

        [$status, $json] = $this->createApplication($cookie, $token, [
            'company' => 'Acme Corp',
            'role' => 'Backend Engineer',
            'status' => 'applied',
            'url' => 'https://acme.example/jobs/1',
            'salary_min' => 80000,
            'salary_max' => 110000,
            'date_applied' => '2026-11-09',
        ]);

        self::assertSame(201, $status);
        $application = $json['data']['application'];
        self::assertIsInt($application['id']);
        self::assertSame('Acme Corp', $application['company']);
        self::assertSame('applied', $application['status']);
        self::assertSame(80000, $application['salary_min']);

        [$showStatus, $showJson] = $this->request(
            'GET',
            '/applications/' . $application['id'],
            null,
            [],
            $cookie
        );

        self::assertSame(200, $showStatus);
        $history = $showJson['data']['statusHistory'];
        self::assertCount(1, $history);
        self::assertNull($history[0]['from_status']);
        self::assertSame('applied', $history[0]['to_status']);
    }

    public function testCreateMissingCompanyReturns400(): void
    {
        [$token, $cookie] = $this->actingAs();

        [$status, $json] = $this->request(
            'POST',
            '/applications',
            ['role' => 'Developer'],
            ['X-CSRF-Token' => $token],
            $cookie
        );

        self::assertSame(400, $status);
        self::assertSame('company is required', $json['error']);
    }

    public function testCreateInvalidStatusReturns400(): void
    {
        [$token, $cookie] = $this->actingAs();

        [$status, $json] = $this->createApplication($cookie, $token, ['status' => 'nope']);

        self::assertSame(400, $status);
        self::assertStringStartsWith('status must be one of', $json['error']);
    }

    public function testListFiltersByStatusAndSearch(): void
    {
        [$token, $cookie] = $this->actingAs();

        $this->createApplication($cookie, $token, [
            'company' => 'Apple Pie Corp',
            'role' => 'Backend Engineer',
        ]);
        $this->createApplication($cookie, $token, [
            'company' => 'Banana Ltd',
            'role' => 'Frontend Developer',
            'status' => 'interviewing',
        ]);

        [$status, $json] = $this->request('GET', '/applications', null, [], $cookie);
        self::assertSame(200, $status);
        self::assertSame(2, $json['data']['count']);

        [$status, $json] = $this->request('GET', '/applications?status=interviewing', null, [], $cookie);
        self::assertSame(200, $status);
        self::assertSame(1, $json['data']['count']);
        self::assertSame('Banana Ltd', $json['data']['applications'][0]['company']);

        [$status, $json] = $this->request('GET', '/applications?q=backend', null, [], $cookie);
        self::assertSame(200, $status);
        self::assertSame(1, $json['data']['count']);
        self::assertSame('Apple Pie Corp', $json['data']['applications'][0]['company']);

        [$status, $json] = $this->request('GET', '/applications?status=nope', null, [], $cookie);
        self::assertSame(400, $status);
        self::assertSame('invalid status filter', $json['error']);
    }

    public function testListIsScopedToCurrentUser(): void
    {
        [$tokenA, $cookieA] = $this->actingAs();
        $this->createApplication($cookieA, $tokenA);

        [, $cookieB] = $this->actingAs();

        [$status, $json] = $this->request('GET', '/applications', null, [], $cookieB);

        self::assertSame(200, $status);
        self::assertSame(0, $json['data']['count']);
    }

    public function testShowOtherUsersApplicationReturns404(): void
    {
        [$tokenA, $cookieA] = $this->actingAs();
        [, $createdJson] = $this->createApplication($cookieA, $tokenA);
        $id = $createdJson['data']['application']['id'];

        [, $cookieB] = $this->actingAs();

        [$status, $json] = $this->request('GET', '/applications/' . $id, null, [], $cookieB);

        self::assertSame(404, $status);
        self::assertSame('not found', $json['error']);
    }

    public function testUpdateStatusWritesHistory(): void
    {
        [$token, $cookie] = $this->actingAs();
        [, $createdJson] = $this->createApplication($cookie, $token);
        $id = $createdJson['data']['application']['id'];

        [$status, $json] = $this->request(
            'PUT',
            '/applications/' . $id,
            ['status' => 'interviewing'],
            ['X-CSRF-Token' => $token],
            $cookie
        );

        self::assertSame(200, $status);
        self::assertSame('interviewing', $json['data']['application']['status']);

        [, $showJson] = $this->request('GET', '/applications/' . $id, null, [], $cookie);
        $history = $showJson['data']['statusHistory'];
        self::assertCount(2, $history);
        self::assertSame('applied', $history[1]['from_status']);
        self::assertSame('interviewing', $history[1]['to_status']);

        [$status] = $this->request(
            'PUT',
            '/applications/' . $id,
            ['company' => 'Renamed Inc'],
            ['X-CSRF-Token' => $token],
            $cookie
        );
        self::assertSame(200, $status);

        [, $showJson] = $this->request('GET', '/applications/' . $id, null, [], $cookie);
        self::assertCount(2, $showJson['data']['statusHistory']);
        self::assertSame('Renamed Inc', $showJson['data']['application']['company']);
    }

    public function testUpdateSalaryMinAboveMaxReturns400(): void
    {
        [$token, $cookie] = $this->actingAs();
        [, $createdJson] = $this->createApplication($cookie, $token, [
            'salary_min' => 100,
            'salary_max' => 200,
        ]);
        $id = $createdJson['data']['application']['id'];

        [$status, $json] = $this->request(
            'PUT',
            '/applications/' . $id,
            ['salary_min' => 500],
            ['X-CSRF-Token' => $token],
            $cookie
        );

        self::assertSame(400, $status);
        self::assertSame('salary_min cannot exceed salary_max', $json['error']);
    }

    public function testUpdateWithNoFieldsReturns400(): void
    {
        [$token, $cookie] = $this->actingAs();
        [, $createdJson] = $this->createApplication($cookie, $token);
        $id = $createdJson['data']['application']['id'];

        [$status, $json] = $this->request(
            'PUT',
            '/applications/' . $id,
            [],
            ['X-CSRF-Token' => $token],
            $cookie
        );

        self::assertSame(400, $status);
        self::assertSame('no updatable fields provided', $json['error']);
    }

    public function testDeleteRemovesApplication(): void
    {
        [$token, $cookie] = $this->actingAs();
        [, $createdJson] = $this->createApplication($cookie, $token);
        $id = $createdJson['data']['application']['id'];

        [$status, $json] = $this->request(
            'DELETE',
            '/applications/' . $id,
            null,
            ['X-CSRF-Token' => $token],
            $cookie
        );
        self::assertSame(200, $status);
        self::assertSame('application deleted', $json['data']['message']);

        [$status] = $this->request('GET', '/applications/' . $id, null, [], $cookie);
        self::assertSame(404, $status);

        [$status] = $this->request(
            'DELETE',
            '/applications/' . $id,
            null,
            ['X-CSRF-Token' => $token],
            $cookie
        );
        self::assertSame(404, $status);
    }

    public function testUnsupportedMethodReturns405(): void
    {
        [$status, $json] = $this->request('PATCH', '/applications/1');

        self::assertSame(405, $status);
        self::assertSame('method not allowed', $json['error']);
    }
}
