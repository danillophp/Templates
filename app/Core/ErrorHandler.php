<?php

declare(strict_types=1);

namespace App\Core;

final class ErrorHandler
{
    public static function register(): void
    {
        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handleError(int $severity, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        self::log(sprintf('PHP Error [%d] %s in %s:%d', $severity, $message, $file, $line));
        self::render500();
        return true;
    }


    public static function handleShutdown(): void
    {
        $error = error_get_last();
        if (!is_array($error)) {
            return;
        }

        $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
        if (!in_array((int)($error['type'] ?? 0), $fatalTypes, true)) {
            return;
        }

        self::log(sprintf(
            'Fatal Error [%d] %s in %s:%d',
            (int)$error['type'],
            (string)($error['message'] ?? ''),
            (string)($error['file'] ?? ''),
            (int)($error['line'] ?? 0)
        ));

        if (!headers_sent()) {
            self::render500();
        }
    }

    public static function handleException(\Throwable $e): void
    {
        self::log(sprintf('Uncaught Exception: %s in %s:%d | trace=%s', $e->getMessage(), $e->getFile(), $e->getLine(), str_replace(["\n", "\r"], ' ', $e->getTraceAsString())));

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

    public static function log(string $content): void
    {
        $request = sprintf(
            'method=%s uri=%s ip=%s user_id=%s ua=%s',
            (string)($_SERVER['REQUEST_METHOD'] ?? '-'),
            (string)($_SERVER['REQUEST_URI'] ?? '-'),
            (string)($_SERVER['REMOTE_ADDR'] ?? '-'),
            isset($_SESSION['user']['id']) ? (string)$_SESSION['user']['id'] : '-',
            substr((string)($_SERVER['HTTP_USER_AGENT'] ?? '-'), 0, 180)
        );
        $dir = __DIR__ . '/../../storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $line = '[' . date('Y-m-d H:i:s') . '] ' . $content . ' | ' . $request . PHP_EOL;
        @file_put_contents($dir . '/app.log', $line, FILE_APPEND);
        error_log('[CataTreco] ' . $content);
    }
}
