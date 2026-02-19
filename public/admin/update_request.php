<?php
require_once __DIR__ . '/../../includes/functions.php';

require_login(['ADMIN']);
verify_csrf();

$requestId = (int) ($_POST['request_id'] ?? 0);
$action = $_POST['action'] ?? '';
$newDate = $_POST['new_datetime'] ?? null;
$employeeId = (int) ($_POST['employee_id'] ?? 0);

$db = db_connect();
$stmt = $db->prepare('SELECT id, whatsapp, full_name FROM requests WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $requestId);
$stmt->execute();
$request = $stmt->get_result()->fetch_assoc();

if (!$request) {
    flash('error', 'Solicitação não encontrada.');
    header('Location: dashboard.php');
    exit;
}

$status = null;
$details = '';

switch ($action) {
    case 'APROVAR':
        $status = 'APROVADO';
        $details = 'Solicitação aprovada.';
        break;
    case 'RECUSAR':
        $status = 'RECUSADO';
        $details = 'Solicitação recusada.';
        break;
    case 'REAGENDAR':
        if (!$newDate) {
            flash('error', 'Informe nova data/hora para reagendamento.');
            header('Location: dashboard.php');
            exit;
        }
        $status = 'REAGENDADO';
        $formatted = date('Y-m-d H:i:s', strtotime($newDate));
        $update = $db->prepare('UPDATE requests SET pickup_datetime = ?, status = ?, updated_at = NOW() WHERE id = ?');
        $update->bind_param('ssi', $formatted, $status, $requestId);
        $update->execute();
        $details = 'Coleta reagendada para ' . $formatted;
        break;
    case 'ENCAMINHAR':
        if ($employeeId <= 0) {
            flash('error', 'Selecione um funcionário para encaminhar.');
            header('Location: dashboard.php');
            exit;
        }
        $status = 'ENCAMINHADO';
        $update = $db->prepare('UPDATE requests SET assigned_user_id = ?, status = ?, updated_at = NOW() WHERE id = ?');
        $update->bind_param('isi', $employeeId, $status, $requestId);
        $update->execute();
        $details = 'Solicitação encaminhada para funcionário ID ' . $employeeId;
        break;
    default:
        flash('error', 'Ação inválida.');
        header('Location: dashboard.php');
        exit;
}

if (in_array($action, ['APROVAR', 'RECUSAR'], true)) {
    $update = $db->prepare('UPDATE requests SET status = ?, updated_at = NOW() WHERE id = ?');
    $update->bind_param('si', $status, $requestId);
    $update->execute();
}

log_action($requestId, (int) $_SESSION['user']['id'], 'ADMIN', $action, $details);

$message = "Olá, {$request['full_name']}! Atualização da sua solicitação Cata Treco (#{$requestId}): {$details}";
$waLink = whatsapp_link($request['whatsapp'], $message);
flash('success', 'Atualização realizada. Envie a mensagem para o cidadão: ' . $waLink);
header('Location: dashboard.php');
