<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Core\Auth;
use App\Models\RequestModel;

header('Content-Type: application/json; charset=utf-8');

if (!Auth::check() || !Auth::is('admin')) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Não autenticado.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$rawMonth = trim((string)($_GET['mes'] ?? date('Y-m')));
if (!preg_match('/^\d{4}-\d{2}$/', $rawMonth)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Mês inválido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$tenantId = (int)Auth::tenantId();
$data = (new RequestModel())->summaryByMonthDate($tenantId, $rawMonth);

echo json_encode(['ok' => true, 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
