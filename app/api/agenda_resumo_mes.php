<?php
require __DIR__ . '/../../index.php';
use App\Core\Database;
header('Content-Type: application/json');
$mes = $_GET['mes'] ?? date('Y-m');
$st = Database::connection()->prepare('SELECT status, COUNT(*) total FROM solicitacoes WHERE DATE_FORMAT(data_agendada, "%Y-%m") = :mes GROUP BY status');
$st->execute(['mes' => $mes]);
echo json_encode(['mes' => $mes, 'resumo' => $st->fetchAll()]);
