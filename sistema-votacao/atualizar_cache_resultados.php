<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$ttl = (int) get_config('cache_ttl_segundos', (string) CACHE_TTL);
$current = load_json_file(RESULTADOS_JSON, []);

if (!empty($current['updated_at_unix']) && (time() - (int)$current['updated_at_unix']) < $ttl) {
    if (php_sapi_name() === 'cli') {
        echo "Cache ainda válido.\n";
    } else {
        json_response(['ok' => true, 'cached' => true, 'data' => $current]);
    }
    exit;
}

$total = (int) db()->query('SELECT COUNT(*) FROM votos')->fetchColumn();
$sql = 'SELECT c.id, c.nome, c.numero, c.foto, COUNT(v.id) AS votos
        FROM candidatos c
        LEFT JOIN votos v ON v.candidato_id = c.id
        WHERE c.ativo = 1
        GROUP BY c.id, c.nome, c.numero, c.foto
        ORDER BY votos DESC, c.ordem ASC';
$ranking = db()->query($sql)->fetchAll();

$data = [
    'total_geral' => $total,
    'atualizado_em' => date('d/m/Y H:i:s'),
    'updated_at_unix' => time(),
    'ranking' => $ranking,
];

write_json_file(RESULTADOS_JSON, $data);

if (php_sapi_name() === 'cli') {
    echo "Cache atualizado com sucesso.\n";
} else {
    json_response(['ok' => true, 'cached' => false, 'data' => $data]);
}
