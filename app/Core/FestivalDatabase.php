<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class FestivalDatabase
{
    private static ?PDO $instance = null;

    public static function connection(): PDO
    {
        if (self::$instance instanceof PDO) {
            return self::$instance;
        }

        self::$instance = new PDO(FestivalDbConfig::dsn(), FestivalDbConfig::USERNAME, FestivalDbConfig::PASSWORD, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return self::$instance;
    }
}
