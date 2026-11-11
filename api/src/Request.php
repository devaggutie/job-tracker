<?php

declare(strict_types=1);

namespace App;

final class Request
{
    public static function jsonBody(): ?array
    {
        $raw = file_get_contents('php://input');

        if ($raw === false || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }
}
