<?php
namespace App\Core;

class ErrorHandler
{
    public static function register(array $app): void
    {
        set_exception_handler(function (\Throwable $e) use ($app) {
            self::log($e->getMessage() . "\n" . $e->getTraceAsString());
            http_response_code(500);
            if (($app['APP_ENV'] ?? 'production') === 'development') {
                echo '<pre>' . htmlspecialchars((string)$e) . '</pre>';
            } else {
                require __DIR__ . '/../../resources/views/errors/500.php';
            }
        });
    }

    public static function log(string $content): void
    {
        $logFile = __DIR__ . '/../../storage/logs/app.log';
        @file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . '] ' . $content . PHP_EOL, FILE_APPEND);
    }
}
