<?php

declare(strict_types=1);

namespace App;

final class Applications
{
    private const COLUMNS = 'id, user_id, company, role, status, url, salary_min, salary_max,
        date_applied, notes, follow_up_date, created_at, updated_at';

    private function __construct()
    {
    }

    public static function create(int $userId, array $fields): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO applications
                (user_id, company, role, status, url, salary_min, salary_max, date_applied, notes, follow_up_date)
             VALUES
                (:user_id, :company, :role, :status, :url, :salary_min, :salary_max, :date_applied, :notes, :follow_up_date)'
        );

        $stmt->execute([
            'user_id' => $userId,
            'company' => $fields['company'],
            'role' => $fields['role'],
            'status' => $fields['status'],
            'url' => $fields['url'],
            'salary_min' => $fields['salary_min'],
            'salary_max' => $fields['salary_max'],
            'date_applied' => $fields['date_applied'],
            'notes' => $fields['notes'],
            'follow_up_date' => $fields['follow_up_date'],
        ]);

        $id = (int) Database::connection()->lastInsertId();
        self::recordStatusChange($id, null, $fields['status']);

        return $id;
    }

    public static function find(int $userId, int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            sprintf('SELECT %s FROM applications WHERE id = ? AND user_id = ?', self::COLUMNS)
        );
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public static function list(int $userId, ?string $status, ?string $search): array
    {
        $where = ['user_id = :user_id'];
        $params = ['user_id' => $userId];

        if ($status !== null) {
            $where[] = 'status = :filter_status';
            $params['filter_status'] = $status;
        }

        if ($search !== null) {
            $where[] = '(company LIKE :q1 OR role LIKE :q2)';
            $pattern = '%' . addcslashes($search, '%_\\') . '%';
            $params['q1'] = $pattern;
            $params['q2'] = $pattern;
        }

        $sql = sprintf(
            'SELECT %s FROM applications WHERE %s ORDER BY created_at DESC, id DESC',
            self::COLUMNS,
            implode(' AND ', $where)
        );

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function update(int $userId, int $id, array $changes): void
    {
        $sets = [];
        $params = ['__id' => $id, '__user_id' => $userId];

        foreach ($changes as $column => $value) {
            $sets[] = sprintf('%s = :%s', $column, $column);
            $params[$column] = $value;
        }

        $sql = sprintf(
            'UPDATE applications SET %s WHERE id = :__id AND user_id = :__user_id',
            implode(', ', $sets)
        );

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
    }

    public static function delete(int $userId, int $id): bool
    {
        $stmt = Database::connection()->prepare(
            'DELETE FROM applications WHERE id = ? AND user_id = ?'
        );
        $stmt->execute([$id, $userId]);

        return $stmt->rowCount() > 0;
    }

    public static function history(int $applicationId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT from_status, to_status, changed_at
             FROM status_history
             WHERE application_id = ?
             ORDER BY changed_at ASC, id ASC'
        );
        $stmt->execute([$applicationId]);

        return $stmt->fetchAll();
    }

    public static function recordStatusChange(int $applicationId, ?string $from, string $to): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO status_history (application_id, from_status, to_status) VALUES (?, ?, ?)'
        );
        $stmt->execute([$applicationId, $from, $to]);
    }
}
