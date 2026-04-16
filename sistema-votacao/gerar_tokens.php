<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

set_time_limit(0);

$total = (int) ($_GET['total'] ?? 65000);
$mode = $_GET['modo'] ?? 'insert'; // insert|csv
$alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$length = 5;
$batchSize = 2000;
$start = microtime(true);

function make_token(string $alphabet, int $length): string {
    $max = strlen($alphabet) - 1;
    $token = '';
    for ($i = 0; $i < $length; $i++) {
        $token .= $alphabet[random_int(0, $max)];
    }
    return $token;
}

$generated = [];
$duplicates = 0;
while (count($generated) < $total) {
    $t = make_token($alphabet, $length);
    if (isset($generated[$t])) {
        $duplicates++;
        continue;
    }
    $generated[$t] = true;
}

$tokens = array_keys($generated);
$inserted = 0;

if ($mode === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=tokens_' . date('Ymd_His') . '.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['senha']);
    foreach ($tokens as $t) {
        fputcsv($out, [$t]);
    }
    fclose($out);
    exit;
}

$pdo = db();
$pdo->beginTransaction();
$sql = 'INSERT IGNORE INTO tokens (senha, status) VALUES '; 
for ($i = 0; $i < count($tokens); $i += $batchSize) {
    $chunk = array_slice($tokens, $i, $batchSize);
    $values = [];
    $params = [];
    foreach ($chunk as $idx => $token) {
        $p = ':t' . $idx;
        $values[] = "($p,0)";
        $params[$p] = $token;
    }
    $stmt = $pdo->prepare($sql . implode(',', $values));
    $stmt->execute($params);
    $inserted += $stmt->rowCount();
}
$pdo->commit();

$time = microtime(true) - $start;
$mem = memory_get_peak_usage(true) / 1024 / 1024;

echo "Total gerado: {$total}<br>";
echo "Total inserido: {$inserted}<br>";
echo "Duplicidades evitadas em memória: {$duplicates}<br>";
echo 'Tempo de execução: ' . number_format($time, 2) . "s<br>";
echo 'Pico de memória: ' . number_format($mem, 2) . ' MB';
