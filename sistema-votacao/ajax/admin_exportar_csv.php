<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$tipo = get('tipo');
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $tipo . '_' . date('Ymd_His') . '.csv"');

$out = fopen('php://output', 'w');

switch ($tipo) {
    case 'votos':
        fputcsv($out, ['id','token_id','participante_id','candidato_id','timestamp','ip','dispositivo','navegador','localizacao','fingerprint']);
        $stmt = db()->query('SELECT id, token_id, participante_id, candidato_id, timestamp, ip, dispositivo, navegador, localizacao, fingerprint FROM votos ORDER BY id DESC');
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) { fputcsv($out, $row); }
        break;

    case 'participantes':
        fputcsv($out, ['id','token_id','nome_completo','whatsapp','fingerprint','created_at']);
        $stmt = db()->query('SELECT id, token_id, nome_completo, whatsapp, fingerprint, created_at FROM participantes ORDER BY id DESC');
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) { fputcsv($out, $row); }
        break;

    case 'ranking':
        fputcsv($out, ['candidato','votos']);
        $stmt = db()->query('SELECT c.nome, COUNT(v.id) votos FROM candidatos c LEFT JOIN votos v ON v.candidato_id=c.id GROUP BY c.id, c.nome ORDER BY votos DESC');
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) { fputcsv($out, $row); }
        break;

    case 'tokens_usados':
        fputcsv($out, ['id','senha','used_at']);
        $stmt = db()->query('SELECT id, senha, used_at FROM tokens WHERE status=1 ORDER BY id DESC');
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) { fputcsv($out, $row); }
        break;

    case 'tokens_disponiveis':
        fputcsv($out, ['id','senha','created_at']);
        $stmt = db()->query('SELECT id, senha, created_at FROM tokens WHERE status=0 ORDER BY id DESC');
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) { fputcsv($out, $row); }
        break;

    default:
        fputcsv($out, ['tipo inválido']);
}

fclose($out);
exit;
