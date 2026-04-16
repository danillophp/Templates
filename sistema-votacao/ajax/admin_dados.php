<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$cards = [
    'total_votos' => (int) db()->query('SELECT COUNT(*) FROM votos')->fetchColumn(),
    'tokens_usados' => (int) db()->query('SELECT COUNT(*) FROM tokens WHERE status = 1')->fetchColumn(),
    'tokens_disponiveis' => (int) db()->query('SELECT COUNT(*) FROM tokens WHERE status = 0')->fetchColumn(),
    'participantes' => (int) db()->query('SELECT COUNT(*) FROM participantes')->fetchColumn(),
    'acessos' => (int) db()->query('SELECT COUNT(*) FROM acessos')->fetchColumn(),
];

$ranking = db()->query('SELECT c.nome, COUNT(v.id) votos
                        FROM candidatos c LEFT JOIN votos v ON v.candidato_id = c.id
                        WHERE c.ativo=1 GROUP BY c.id, c.nome
                        ORDER BY votos DESC, c.ordem ASC')->fetchAll();

$topIps = db()->query('SELECT ip, COUNT(*) total FROM votos GROUP BY ip ORDER BY total DESC LIMIT 5')->fetchAll();
$fingerprints = db()->query('SELECT fingerprint, COUNT(*) total FROM votos WHERE fingerprint IS NOT NULL AND fingerprint <> "" GROUP BY fingerprint HAVING total > 1 ORDER BY total DESC LIMIT 5')->fetchAll();
$acessos = db()->query('SELECT ip, rota, metodo, created_at FROM acessos ORDER BY id DESC LIMIT 10')->fetchAll();

json_response([
    'ok' => true,
    'cards' => $cards,
    'ranking' => $ranking,
    'suspeitas' => [
        'top_ips' => $topIps,
        'fingerprints' => $fingerprints,
        'tentativas_bloqueadas' => 0
    ],
    'acessos' => $acessos,
    'manutencao' => maintenance_active(),
]);
