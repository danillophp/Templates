<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class Database
{
    private static ?PDO $instance = null;

    public static function connection(): PDO
    {
        if (self::$instance instanceof PDO) {
            return self::$instance;
        }

        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', \DbConfig::HOST, \DbConfig::DATABASE, \DbConfig::CHARSET);
        self::$instance = new PDO($dsn, \DbConfig::USERNAME, \DbConfig::PASSWORD, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        return self::$instance;
    }
}
