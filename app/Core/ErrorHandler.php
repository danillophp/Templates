<?php

declare(strict_types=1);

namespace App\Core;

final class ErrorHandler
{
    public static function register(): void
    {
        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);
    }

    public static function handleError(int $severity, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        self::writeLog("PHP Error [{$severity}] {$message} in {$file}:{$line}");
        self::render500();
        return true;
    }

    public static function handleException(\Throwable $e): void
    {
        self::writeLog('Uncaught Exception: ' . (string)$e);

        if (APP_DEBUG) {
            http_response_code(500);
            echo '<pre>' . htmlspecialchars((string)$e) . '</pre>';
            return;
        }

        self::render500();
    }

    private static function render500(): void
    {
        http_response_code(500);
        $view = __DIR__ . '/../../resources/views/errors/500.php';
        if (is_file($view)) {
            require $view;
            return;
        }

        echo 'Serviço temporariamente indisponível.';
    }

    private static function writeLog(string $content): void
    {
        $dir = __DIR__ . '/../../storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $line = '[' . date('Y-m-d H:i:s') . '] ' . $content . PHP_EOL;
        @file_put_contents($dir . '/app.log', $line, FILE_APPEND);
        error_log('[CataTreco] ' . $content);
    }
}
