<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Logger simples em arquivo para auditoria e troubleshooting.
 */
class Logger
{
    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('WARNING', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    private static function write(string $level, string $message, array $context): void
    {
        $dir = __DIR__ . '/../../storage/logs';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $line = sprintf(
            "[%s] %s: %s %s\n",
            date('Y-m-d H:i:s'),
            $level,
            $message,
            json_encode($context, JSON_UNESCAPED_UNICODE)
        );

        file_put_contents($dir . '/app.log', $line, FILE_APPEND);
    }
}
