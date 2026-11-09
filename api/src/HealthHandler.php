<?php

declare(strict_types=1);

namespace App;

use PDO;
use Throwable;

final class HealthHandler
{
    public function __invoke(): array
    {
        try {
            $pdo = Database::connection();
            $pdo->query('SELECT 1');
        } catch (Throwable $e) {
            error_log('health check failed: ' . $e->getMessage());

            return [
                'status' => 503,
                'error' => 'database unavailable',
            ];
        }

        return [
            'status' => 200,
            'data' => [
                'status' => 'ok',
                'service' => 'job-tracker-api',
                'db' => 'ok',
                'time' => gmdate('c'),
            ],
        ];
    }
}
