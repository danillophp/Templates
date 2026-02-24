<?php

declare(strict_types=1);

namespace App\Services;

final class BrandingService
{
    public static function logoUrl(array $config): ?string
    {
        $raw = trim((string)($config['logo'] ?? ''));
        if ($raw === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $raw) === 1 || str_starts_with($raw, 'data:')) {
            return $raw;
        }

        $normalized = '/' . ltrim(str_replace('\\', '/', $raw), '/');
        return APP_BASE_PATH . $normalized;
    }
}
