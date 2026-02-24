<?php

namespace App\Core;

class Config
{
    private static array $config = [];

    public static function load(): void
    {
        if (!empty(self::$config)) {
            return;
        }

        self::$config['app'] = require __DIR__ . '/../../config/app.php';
        self::$config['database'] = require __DIR__ . '/../../config/database.php';
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::load();
        $segments = explode('.', $key);
        $value = self::$config;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}
