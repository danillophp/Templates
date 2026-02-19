<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

try {
    verify_csrf();

    $fullName = trim($_POST['full_name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $cep = trim($_POST['cep'] ?? '');
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    $pickup = trim($_POST['pickup_datetime'] ?? '');
    $latitude = (float) ($_POST['latitude'] ?? 0);
    $longitude = (float) ($_POST['longitude'] ?? 0);
    $consent = isset($_POST['consent']) ? 1 : 0;

    if ($fullName === '' || $address === '' || $cep === '' || $whatsapp === '' || $pickup === '' || $consent !== 1) {
        throw new RuntimeException('Preencha todos os campos obrigatórios.');
    }

    $pickupDateTime = date('Y-m-d H:i:s', strtotime($pickup));
    $photoFile = save_upload($_FILES['photo']);

    $db = db_connect();
    $stmt = $db->prepare('INSERT INTO requests (full_name, address, cep, whatsapp, photo_path, pickup_datetime, status, latitude, longitude, consent_given, request_ip, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, "PENDENTE", ?, ?, 1, ?, NOW(), NOW())');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $stmt->bind_param('ssssssdds', $fullName, $address, $cep, $whatsapp, $photoFile, $pickupDateTime, $latitude, $longitude, $ip);
    $stmt->execute();

    $requestId = (int) $db->insert_id;
    log_action($requestId, null, 'CIDADAO', 'CRIACAO_SOLICITACAO', 'Nova solicitação criada pelo cidadão.');

    flash('success', 'Solicitação enviada com sucesso. Entraremos em contato pelo WhatsApp.');
} catch (Throwable $e) {
    flash('error', $e->getMessage());
}

header('Location: index.php');
