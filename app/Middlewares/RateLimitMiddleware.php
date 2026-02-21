<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Database;

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

    public static function checkIpForCitizen(string $ip, int $max = 5, int $windowMinutes = 10): array
    {
        $stmt = Database::connection()->prepare('SELECT id, total_requisicoes, ultima_requisicao_em FROM controle_rate_limit WHERE ip = :ip LIMIT 1');
        $stmt->execute(['ip' => $ip]);
        $row = $stmt->fetch();

        if (!$row) {
            Database::connection()->prepare('INSERT INTO controle_rate_limit (ip, total_requisicoes, ultima_requisicao_em) VALUES (:ip, 1, NOW())')
                ->execute(['ip' => $ip]);
            return ['allowed' => true];
        }

        $last = new \DateTimeImmutable((string)$row['ultima_requisicao_em']);
        $limitAt = $last->modify('+' . $windowMinutes . ' minutes');
        $now = new \DateTimeImmutable('now');

        if ($now > $limitAt) {
            Database::connection()->prepare('UPDATE controle_rate_limit SET total_requisicoes = 1, ultima_requisicao_em = NOW() WHERE id = :id')
                ->execute(['id' => (int)$row['id']]);
            return ['allowed' => true];
        }

        if ((int)$row['total_requisicoes'] >= $max) {
            return ['allowed' => false, 'message' => 'Muitas solicitações deste IP. Tente novamente em alguns minutos.'];
        }

        Database::connection()->prepare('UPDATE controle_rate_limit SET total_requisicoes = total_requisicoes + 1, ultima_requisicao_em = NOW() WHERE id = :id')
            ->execute(['id' => (int)$row['id']]);

        return ['allowed' => true];
    }
}
