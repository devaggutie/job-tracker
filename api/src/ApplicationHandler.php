<?php

declare(strict_types=1);

namespace App;

use DateTime;

final class ApplicationHandler
{
    private const STATUSES = ['applied', 'interviewing', 'offer', 'rejected', 'accepted'];

    public function index(): array
    {
        $userId = Session::userId();

        if ($userId === null) {
            return ['status' => 401, 'error' => 'unauthenticated'];
        }

        $status = $_GET['status'] ?? null;

        if ($status !== null) {
            if (!is_string($status) || !in_array($status, self::STATUSES, true)) {
                return ['status' => 400, 'error' => 'invalid status filter'];
            }
        }

        $search = $_GET['q'] ?? null;

        if ($search !== null) {
            if (!is_string($search) || trim($search) === '') {
                return ['status' => 400, 'error' => 'invalid search query'];
            }

            $search = trim($search);
        }

        $applications = Applications::list($userId, $status, $search);

        return [
            'status' => 200,
            'data' => [
                'applications' => $applications,
                'count' => count($applications),
            ],
        ];
    }

    public function create(): array
    {
        $userId = Session::userId();

        if ($userId === null) {
            return ['status' => 401, 'error' => 'unauthenticated'];
        }

        $body = Request::jsonBody();

        if ($body === null) {
            return ['status' => 400, 'error' => 'invalid json body'];
        }

        $changes = $this->parseFields($body, false);

        if (is_string($changes)) {
            return ['status' => 400, 'error' => $changes];
        }

        $fields = array_merge(
            [
                'status' => 'applied',
                'url' => null,
                'salary_min' => null,
                'salary_max' => null,
                'date_applied' => null,
                'notes' => null,
                'follow_up_date' => null,
            ],
            $changes
        );

        $salaryError = $this->salaryError($fields['salary_min'], $fields['salary_max']);

        if ($salaryError !== null) {
            return ['status' => 400, 'error' => $salaryError];
        }

        $id = Applications::create($userId, $fields);
        $application = Applications::find($userId, $id);

        return [
            'status' => 201,
            'data' => ['application' => $application],
        ];
    }

    public function show(array $params): array
    {
        $userId = Session::userId();

        if ($userId === null) {
            return ['status' => 401, 'error' => 'unauthenticated'];
        }

        $id = $this->id($params);

        if ($id === null) {
            return ['status' => 404, 'error' => 'not found'];
        }

        $application = Applications::find($userId, $id);

        if ($application === null) {
            return ['status' => 404, 'error' => 'not found'];
        }

        return [
            'status' => 200,
            'data' => [
                'application' => $application,
                'statusHistory' => Applications::history($id),
            ],
        ];
    }

    public function update(array $params): array
    {
        $userId = Session::userId();

        if ($userId === null) {
            return ['status' => 401, 'error' => 'unauthenticated'];
        }

        $id = $this->id($params);

        if ($id === null) {
            return ['status' => 404, 'error' => 'not found'];
        }

        $old = Applications::find($userId, $id);

        if ($old === null) {
            return ['status' => 404, 'error' => 'not found'];
        }

        $body = Request::jsonBody();

        if ($body === null) {
            return ['status' => 400, 'error' => 'invalid json body'];
        }

        $changes = $this->parseFields($body, true);

        if (is_string($changes)) {
            return ['status' => 400, 'error' => $changes];
        }

        if ($changes === []) {
            return ['status' => 400, 'error' => 'no updatable fields provided'];
        }

        $min = array_key_exists('salary_min', $changes) ? $changes['salary_min'] : $old['salary_min'];
        $max = array_key_exists('salary_max', $changes) ? $changes['salary_max'] : $old['salary_max'];
        $salaryError = $this->salaryError(
            $min === null ? null : (int) $min,
            $max === null ? null : (int) $max
        );

        if ($salaryError !== null) {
            return ['status' => 400, 'error' => $salaryError];
        }

        $statusChanged = isset($changes['status']) && $changes['status'] !== $old['status'];

        Applications::update($userId, $id, $changes);

        if ($statusChanged) {
            Applications::recordStatusChange($id, (string) $old['status'], $changes['status']);
        }

        return [
            'status' => 200,
            'data' => ['application' => Applications::find($userId, $id)],
        ];
    }

    public function destroy(array $params): array
    {
        $userId = Session::userId();

        if ($userId === null) {
            return ['status' => 401, 'error' => 'unauthenticated'];
        }

        $id = $this->id($params);

        if ($id === null || !Applications::delete($userId, $id)) {
            return ['status' => 404, 'error' => 'not found'];
        }

        return [
            'status' => 200,
            'data' => ['message' => 'application deleted'],
        ];
    }

    private function parseFields(array $body, bool $partial): array|string
    {
        $changes = [];

        if (array_key_exists('company', $body)) {
            $value = $body['company'];

            if (!is_string($value) || trim($value) === '' || strlen(trim($value)) > 255) {
                return 'company must be a non-empty string of at most 255 characters';
            }

            $changes['company'] = trim($value);
        } elseif (!$partial) {
            return 'company is required';
        }

        if (array_key_exists('role', $body)) {
            $value = $body['role'];

            if (!is_string($value) || trim($value) === '' || strlen(trim($value)) > 255) {
                return 'role must be a non-empty string of at most 255 characters';
            }

            $changes['role'] = trim($value);
        } elseif (!$partial) {
            return 'role is required';
        }

        if (array_key_exists('status', $body)) {
            $value = $body['status'];

            if (!is_string($value) || !in_array($value, self::STATUSES, true)) {
                return 'status must be one of: ' . implode(', ', self::STATUSES);
            }

            $changes['status'] = $value;
        }

        if (array_key_exists('url', $body)) {
            $value = $body['url'];

            if ($value === null || $value === '') {
                $changes['url'] = null;
            } elseif (!is_string($value)
                || strlen($value) > 2048
                || filter_var($value, FILTER_VALIDATE_URL) === false
            ) {
                return 'url must be a valid url of at most 2048 characters';
            } else {
                $changes['url'] = $value;
            }
        }

        foreach (['salary_min', 'salary_max'] as $key) {
            if (!array_key_exists($key, $body)) {
                continue;
            }

            $value = $body[$key];

            if ($value === null) {
                $changes[$key] = null;
                continue;
            }

            if (is_int($value) && $value >= 0) {
                $changes[$key] = $value;
                continue;
            }

            if (is_string($value) && preg_match('/^\d+$/', $value) === 1) {
                $changes[$key] = (int) $value;
                continue;
            }

            return $key . ' must be a non-negative integer or null';
        }

        foreach (['date_applied', 'follow_up_date'] as $key) {
            if (!array_key_exists($key, $body)) {
                continue;
            }

            $value = $body[$key];

            if ($value === null || $value === '') {
                $changes[$key] = null;
                continue;
            }

            if (!is_string($value) || !self::isValidDate($value)) {
                return $key . ' must be a date in YYYY-MM-DD format';
            }

            $changes[$key] = $value;
        }

        if (array_key_exists('notes', $body)) {
            $value = $body['notes'];

            if ($value !== null && !is_string($value)) {
                return 'notes must be a string or null';
            }

            $changes['notes'] = $value;
        }

        return $changes;
    }

    private function salaryError(?int $min, ?int $max): ?string
    {
        if ($min !== null && $max !== null && $min > $max) {
            return 'salary_min cannot exceed salary_max';
        }

        return null;
    }

    private function id(array $params): ?int
    {
        $id = $params['id'] ?? '';

        return is_string($id) && ctype_digit($id) && $id !== '0' ? (int) $id : null;
    }

    private static function isValidDate(string $value): bool
    {
        $date = DateTime::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }
}
