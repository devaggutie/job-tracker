<?php

declare(strict_types=1);

namespace App;

use DateTimeImmutable;

final class Dashboard
{
    private const STATUSES = ['applied', 'interviewing', 'offer', 'rejected', 'accepted'];
    private const WEEKS = 8;

    private function __construct()
    {
    }

    public static function countsByStatus(int $userId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT status, COUNT(*) AS aggregate_count
             FROM applications
             WHERE user_id = ?
             GROUP BY status'
        );
        $stmt->execute([$userId]);

        $counts = array_fill_keys(self::STATUSES, 0);

        foreach ($stmt->fetchAll() as $row) {
            $counts[$row['status']] = (int) $row['aggregate_count'];
        }

        return $counts;
    }

    public static function applicationsPerWeek(int $userId): array
    {
        $today = new DateTimeImmutable('today');
        $dayOfWeek = (int) $today->format('N');
        $currentMonday = $today->modify(sprintf('-%d days', $dayOfWeek - 1));
        $oldestMonday = $currentMonday->modify(sprintf('-%d weeks', self::WEEKS - 1));
        $nextMonday = $currentMonday->modify('+1 week');

        $stmt = Database::connection()->prepare(
            'SELECT YEARWEEK(date_applied, 3) AS week_key, COUNT(*) AS aggregate_count
             FROM applications
             WHERE user_id = ? AND date_applied >= ? AND date_applied < ?
             GROUP BY week_key'
        );
        $stmt->execute([
            $userId,
            $oldestMonday->format('Y-m-d'),
            $nextMonday->format('Y-m-d'),
        ]);

        $byKey = [];

        foreach ($stmt->fetchAll() as $row) {
            $byKey[(int) $row['week_key']] = (int) $row['aggregate_count'];
        }

        $weeks = [];

        for ($i = 0; $i < self::WEEKS; $i++) {
            $weekStart = $oldestMonday->modify(sprintf('+%d weeks', $i));
            $key = (int) ($weekStart->format('o') . str_pad($weekStart->format('W'), 2, '0', STR_PAD_LEFT));

            $weeks[] = [
                'weekStart' => $weekStart->format('Y-m-d'),
                'count' => $byKey[$key] ?? 0,
            ];
        }

        return $weeks;
    }

    public static function followUps(int $userId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, company, role, status, follow_up_date
             FROM applications
             WHERE user_id = ? AND follow_up_date IS NOT NULL AND follow_up_date <= CURDATE()
             ORDER BY follow_up_date ASC, id ASC'
        );
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }
}
