<?php

declare(strict_types=1);

namespace App\Middlewares;

final class RateLimitMiddleware
{
    public static function check(string $key, int $maxAttempts = 5, int $lockSeconds = 900): array
    {
        $now = time();
        $bucket = $_SESSION['_rate_limit'][$key] ?? ['count' => 0, 'lock_until' => 0];

        if ($bucket['lock_until'] > $now) {
            return ['allowed' => false, 'retry_after' => $bucket['lock_until'] - $now];
        }

        if ($bucket['count'] >= $maxAttempts) {
            $bucket['lock_until'] = $now + $lockSeconds;
            $_SESSION['_rate_limit'][$key] = $bucket;
            return ['allowed' => false, 'retry_after' => $lockSeconds];
        }

        return ['allowed' => true, 'retry_after' => 0];
    }

    public static function fail(string $key): void
    {
        if (!isset($_SESSION['_rate_limit'][$key])) {
            $_SESSION['_rate_limit'][$key] = ['count' => 0, 'lock_until' => 0];
        }
        $_SESSION['_rate_limit'][$key]['count']++;
    }

    public static function clear(string $key): void
    {
        unset($_SESSION['_rate_limit'][$key]);
    }
}
