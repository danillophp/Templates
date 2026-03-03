<?php

declare(strict_types=1);

namespace App\Core;

final class Security
{
    public static function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function cleanString(?string $value, int $max = 255): string
    {
        $clean = trim((string) $value);
        $clean = strip_tags($clean);
        if (mb_strlen($clean) > $max) {
            $clean = mb_substr($clean, 0, $max);
        }

        return $clean;
    }

    public static function ip(): string
    {
        return substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
    }

    public static function userAgent(): string
    {
        return substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'), 0, 255);
    }
}
