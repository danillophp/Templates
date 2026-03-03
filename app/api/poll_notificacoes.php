<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Core\Auth;
use App\Models\AdminNotificationModel;

header('Content-Type: application/json; charset=utf-8');

if (!Auth::check() || !Auth::is('admin')) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Não autenticado.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$tenantId = (int)Auth::tenantId();
$lastId = max(0, (int)($_GET['last_id'] ?? 0));
$rows = (new AdminNotificationModel())->listSince($tenantId, $lastId);

$data = array_map(static function (array $row): array {
    $payload = json_decode((string)($row['payload_json'] ?? ''), true);

    return [
        'id' => (int)$row['id'],
        'tipo' => (string)$row['tipo'],
        'solicitacao_id' => (int)$row['solicitacao_id'],
        'payload' => is_array($payload) ? $payload : [],
        'criado_em' => (string)$row['criado_em'],
    ];
}, $rows);

echo json_encode(['ok' => true, 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
