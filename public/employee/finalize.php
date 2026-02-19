<?php
require_once __DIR__ . '/../../includes/functions.php';

require_login(['FUNCIONARIO']);
verify_csrf();

$requestId = (int) ($_POST['request_id'] ?? 0);
$db = db_connect();
$stmt = $db->prepare('UPDATE requests SET status = "FINALIZADO", finalized_at = NOW(), updated_at = NOW() WHERE id = ? AND assigned_user_id = ?');
$uid = (int) $_SESSION['user']['id'];
$stmt->bind_param('ii', $requestId, $uid);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    log_action($requestId, $uid, 'FUNCIONARIO', 'FINALIZADO', 'Coleta finalizada com sucesso.');
    flash('success', 'Solicitação finalizada com sucesso.');
}

header('Location: dashboard.php');
