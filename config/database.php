<?php

declare(strict_types=1);

final class FestivalDbConfig
{
    public const HOST = '127.0.0.1';
    public const PORT = '3306';
    public const DATABASE = 'festival14maio';
    public const USERNAME = 'root';
    public const PASSWORD = '';
    public const CHARSET = 'utf8mb4';

    public static function dsn(): string
    {
        return sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            self::HOST,
            self::PORT,
            self::DATABASE,
            self::CHARSET
        );
    }
}
