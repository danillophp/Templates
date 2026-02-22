<?php

declare(strict_types=1);

require_once __DIR__ . '/database.php';

/**
 * Compatibilidade com código legado.
 */
final class DbConfig
{
    public const HOST = DatabaseConfig::HOST;
    public const DATABASE = DatabaseConfig::DATABASE;
    public const USERNAME = DatabaseConfig::USERNAME;
    public const PASSWORD = DatabaseConfig::PASSWORD;
    public const CHARSET = DatabaseConfig::CHARSET;
}
