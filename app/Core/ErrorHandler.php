<?php
namespace App\Core;

class ErrorHandler
{
    public static function register(): void
    {
        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);
    }

    public static function handleException(\Throwable $e): void
    {
        self::log($e->getMessage() . ' @' . $e->getFile() . ':' . $e->getLine());
        http_response_code(500);
        if (config('APP_ENV') === 'production') {
            require __DIR__ . '/../../resources/views/errors/500.php';
        } else {
            echo '<pre>' . htmlspecialchars((string)$e) . '</pre>';
        }
    }

    public static function handleError($severity, $message, $file, $line): bool
    {
        self::log("$message @ $file:$line");
        return false;
    }

    public static function log(string $message): void
    {
        $logDir = __DIR__ . '/../../storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0775, true);
        }
        file_put_contents($logDir . '/app.log', '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL, FILE_APPEND);
    }
}
