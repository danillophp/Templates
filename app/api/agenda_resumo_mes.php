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

date_default_timezone_set('America/Sao_Paulo');
$year = (int)($_GET['year'] ?? 0);
$month = (int)($_GET['month'] ?? 0);
if ($year <= 0 || $month <= 0) {
    $rawMonth = trim((string)($_GET['mes'] ?? date('Y-m')));
    if (preg_match('/^(\d{4})-(\d{2})$/', $rawMonth, $m) === 1) {
        $year = (int)$m[1];
        $month = (int)$m[2];
    }
}

if ($year < 2020 || $year > 2100 || $month < 1 || $month > 12) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Parâmetros de mês inválidos.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$yearMonth = sprintf('%04d-%02d', $year, $month);
$tenantId = (int)Auth::tenantId();
$data = (new RequestModel())->summaryByMonthDate($tenantId, $yearMonth);

echo json_encode(['ok' => true, 'year' => $year, 'month' => $month, 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
