<?php
namespace App\Core;

class ErrorHandler
{
    public static function register(array $app): void
    {
        error_reporting(E_ALL);

        set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        set_exception_handler(function (\Throwable $e) use ($app) {
            self::log(sprintf('%s in %s:%d | %s', $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()));
            http_response_code(500);
            $isDev = ($app['APP_ENV'] ?? 'production') === 'development' || (($app['APP_DEBUG'] ?? 'false') === 'true');
            if ($isDev) {
                echo '<pre>' . htmlspecialchars((string) $e) . '</pre>';
                return;
            }
            require __DIR__ . '/../../resources/views/errors/500.php';
        });
    }

    public static function log(string $content): void
    {
        $logFile = __DIR__ . '/../../storage/logs/app.log';
        @file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . '] ' . $content . PHP_EOL, FILE_APPEND);
    }
}
