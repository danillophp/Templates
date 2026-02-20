<?php

declare(strict_types=1);

namespace App\Core;

final class Auth
{
    public static function user(): ?array
    {
        if (function_exists('wp_get_current_user') && is_user_logged_in()) {
            $wpUser = wp_get_current_user();
            return [
                'id' => (int) $wpUser->ID,
                'name' => (string) $wpUser->display_name,
                'role' => (string) ($wpUser->roles[0] ?? ''),
                'username' => (string) $wpUser->user_login,
            ];
        }

        return $_SESSION['user'] ?? null;
    }

    public static function check(): bool
    {
        if (function_exists('is_user_logged_in')) {
            return is_user_logged_in();
        }

        return isset($_SESSION['user']);
    }

    public static function is(string $role): bool
    {
        if (function_exists('current_user_can')) {
            return current_user_can($role);
        }

        return (($_SESSION['user']['role'] ?? '') === $role);
    }

    public static function login(array $user): void
    {
        if (function_exists('wp_signon')) {
            return;
        }

        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => $user['full_name'],
            'role' => $user['role'],
            'username' => $user['username'],
        ];
    }

    public static function logout(): void
    {
        if (function_exists('wp_logout')) {
            wp_logout();
            return;
        }

        $_SESSION = [];
        session_destroy();
    }
}
