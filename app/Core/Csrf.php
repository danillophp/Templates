<?php

namespace App\Core;

class Csrf
{
    public static function token(): string
    {
        $name = Config::get('app.csrf_token_name');
        if (empty($_SESSION[$name])) {
            $_SESSION[$name] = bin2hex(random_bytes(32));
        }

        return $_SESSION[$name];
    }

    public static function validate(?string $token): bool
    {
        $name = Config::get('app.csrf_token_name');
        $sessionToken = $_SESSION[$name] ?? '';

        return is_string($token) && hash_equals($sessionToken, $token);
    }
}
