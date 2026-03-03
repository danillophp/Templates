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

        $host = $_ENV['DB_HOST'] ?? \DbConfig::HOST;
        $db = $_ENV['DB_NAME'] ?? \DbConfig::DATABASE;
        $user = $_ENV['DB_USER'] ?? \DbConfig::USERNAME;
        $pass = $_ENV['DB_PASS'] ?? \DbConfig::PASSWORD;
        $charset = $_ENV['DB_CHARSET'] ?? \DbConfig::CHARSET;

        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $host, $db, $charset);
        self::$instance = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return self::$instance;
    }
}
