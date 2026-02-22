<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $instance = null;

    public static function connection(): PDO
    {
        if (self::$instance instanceof PDO) {
            return self::$instance;
        }

        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', \DbConfig::HOST, \DbConfig::DATABASE, \DbConfig::CHARSET);

        try {
            self::$instance = new PDO($dsn, \DbConfig::USERNAME, \DbConfig::PASSWORD, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            return self::$instance;
        } catch (PDOException $e) {
            error_log('[CataTreco][DB] ' . $e->getMessage());

            if (APP_DEBUG) {
                throw $e;
            }

            throw new \RuntimeException('Falha temporária de conexão. Tente novamente em instantes.');
        }
    }
}
