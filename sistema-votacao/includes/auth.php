<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function is_admin_logged(): bool
{
    return !empty($_SESSION['admin_logged']) && !empty($_SESSION['admin_id']);
}

function require_admin(): void
{
    if (!is_admin_logged()) {
        header('Location: ' . app_path('login_admin.php'));
        exit;
    }
}
