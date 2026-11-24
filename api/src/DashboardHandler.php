<?php

declare(strict_types=1);

namespace App;

final class DashboardHandler
{
    public function show(): array
    {
        $userId = Session::userId();

        if ($userId === null) {
            return ['status' => 401, 'error' => 'unauthenticated'];
        }

        return [
            'status' => 200,
            'data' => [
                'countsByStatus' => Dashboard::countsByStatus($userId),
                'applicationsPerWeek' => Dashboard::applicationsPerWeek($userId),
                'followUps' => Dashboard::followUps($userId),
                'today' => date('Y-m-d'),
            ],
        ];
    }
}
