<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';

function start_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function csrf_token(): string
{
    start_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    start_session();
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        exit('Token CSRF inválido.');
    }
}

function sanitize_phone(string $phone): string
{
    return preg_replace('/\D+/', '', $phone) ?? '';
}

function require_login(array $roles = []): void
{
    start_session();
    if (empty($_SESSION['user'])) {
        header('Location: ' . base_url('admin/login.php'));
        exit;
    }

    if ($roles !== [] && !in_array($_SESSION['user']['role'], $roles, true)) {
        http_response_code(403);
        exit('Acesso não autorizado.');
    }
}

function flash(string $key, ?string $message = null): ?string
{
    start_session();
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }

    if (!isset($_SESSION['flash'][$key])) {
        return null;
    }

    $msg = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);
    return $msg;
}

function save_upload(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Envie uma foto válida dos trecos.');
    }

    if (($file['size'] ?? 0) > MAX_UPLOAD_SIZE) {
        throw new RuntimeException('A imagem excede 5MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Formato inválido. Use JPG, PNG ou WEBP.');
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0775, true);
    }

    $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    $target = UPLOAD_DIR . '/' . $name;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new RuntimeException('Falha ao salvar a imagem.');
    }

    return $name;
}

function log_action(?int $requestId, ?int $actorId, string $actorRole, string $action, string $details = ''): void
{
    $db = db_connect();
    $stmt = $db->prepare('INSERT INTO logs (request_id, actor_user_id, actor_role, action, details, actor_ip, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $stmt->bind_param('iissss', $requestId, $actorId, $actorRole, $action, $details, $ip);
    $stmt->execute();
}

function whatsapp_link(string $phone, string $message): string
{
    $cleanPhone = sanitize_phone($phone);
    return 'https://wa.me/55' . $cleanPhone . '?text=' . rawurlencode($message);
}
