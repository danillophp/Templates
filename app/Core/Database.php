<?php
namespace App\Core;

use PDO;

class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            $cfg = require __DIR__ . '/../../config/database.php';
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $cfg['host'], $cfg['port'], $cfg['dbname'], $cfg['charset']);
            self::$pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], $cfg['options']);
        }
        return self::$pdo;
    }
}
