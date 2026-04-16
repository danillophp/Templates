<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'msg' => 'Método inválido'], 405);
}
if (!validate_csrf(post('csrf_token'))) {
    json_response(['ok' => false, 'msg' => 'CSRF inválido'], 419);
}

$ip = get_client_ip();
$tentativas = (int) get_config('rate_limit_tentativas', '10');
$janela = (int) get_config('rate_limit_janela_segundos', '300');
$rl = rate_limit_check('token_' . md5($ip), $tentativas, $janela);

if (!$rl['allowed']) {
    app_log('security', 'rate_limit_bloqueado', ['ip' => $ip]);
    json_response(['ok' => false, 'msg' => 'Muitas tentativas. Aguarde ' . (int)$rl['retry_in'] . 's'], 429);
}

$token = normalize_token(post('token'));
if (strlen($token) !== 5) {
    json_response(['ok' => false, 'msg' => 'Token inválido.']);
}

$stmt = db()->prepare('SELECT id, status FROM tokens WHERE senha = :senha LIMIT 1');
$stmt->execute([':senha' => $token]);
$row = $stmt->fetch();

if (!$row) {
    app_log('security', 'token_inexistente', ['ip' => $ip, 'token' => $token]);
    json_response(['ok' => false, 'msg' => 'Token não encontrado.']);
}
if ((int) $row['status'] !== 0) {
    json_response(['ok' => false, 'msg' => 'Token já utilizado.']);
}

$_SESSION['vote_flow'] = ['token' => $token, 'token_id' => (int) $row['id']];
json_response(['ok' => true, 'msg' => 'Token validado com sucesso.']);
