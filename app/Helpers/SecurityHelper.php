<?php

declare(strict_types=1);

namespace App\Helpers;

final class SecurityHelper
{
    public static function ip(): string
    {
        $candidates = [
            $_SERVER['HTTP_CF_CONNECTING_IP'] ?? null,
            $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (!$candidate) {
                continue;
            }

            $ip = trim(explode(',', $candidate)[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }

        return '0.0.0.0';
    }

    public static function hashValue(string $value): string
    {
        return hash('sha256', FestivalConfig::HASH_SALT . '|' . $value);
    }

    public static function fingerprint(): string
    {
        $parts = [
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'unknown',
            $_SERVER['HTTP_SEC_CH_UA_PLATFORM'] ?? 'unknown',
        ];

        return self::hashValue(implode('|', $parts));
    }

    public static function sanitizeText(?string $value, int $maxLen = 255): string
    {
        $value = trim((string) $value);
        $value = strip_tags($value);
        $value = mb_substr($value, 0, $maxLen);
        return $value;
    }

    public static function ensureCookieToken(): string
    {
        if (!isset($_COOKIE['festival_vote_token']) || !is_string($_COOKIE['festival_vote_token'])) {
            $token = bin2hex(random_bytes(16));
            setcookie('festival_vote_token', $token, [
                'expires' => time() + 31536000,
                'path' => '/',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            return $token;
        }

        return $_COOKIE['festival_vote_token'];
    }
}
