<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false], 405);
}
if (!validate_csrf(post('csrf_token'))) {
    json_response(['ok' => false, 'msg' => 'CSRF inválido'], 419);
}

$novo = maintenance_active() ? '0' : '1';
$stmt = db()->prepare('UPDATE configuracoes SET valor = :valor WHERE chave = "modo_manutencao" LIMIT 1');
$stmt->execute([':valor' => $novo]);

json_response(['ok' => true, 'manutencao' => $novo === '1']);
