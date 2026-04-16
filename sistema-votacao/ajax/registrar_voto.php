<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'msg' => 'Método inválido'], 405);
}
if (!validate_csrf(post('csrf_token'))) {
    json_response(['ok' => false, 'msg' => 'CSRF inválido'], 419);
}
if (!empty($_SESSION['voto_finalizado'])) {
    json_response(['ok' => false, 'msg' => 'Sessão já finalizada.']);
}
$flow = $_SESSION['vote_flow'] ?? [];
if (empty($flow['token']) || empty($flow['nome']) || empty($flow['whatsapp'])) {
    json_response(['ok' => false, 'msg' => 'Fluxo incompleto.']);
}

$candidatoId = (int) post('candidato_id');
if ($candidatoId <= 0) {
    json_response(['ok' => false, 'msg' => 'Candidato inválido.']);
}

$pdo = db();
$ip = get_client_ip();
$ua = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
$fingerprint = substr(post('fingerprint') ?: ($flow['fingerprint'] ?? ''), 0, 64);
$localizacao = substr(post('localizacao'), 0, 100);

try {
    $pdo->beginTransaction();

    $tokenStmt = $pdo->prepare('SELECT id, status FROM tokens WHERE senha = :senha AND status = 0 FOR UPDATE');
    $tokenStmt->execute([':senha' => $flow['token']]);
    $token = $tokenStmt->fetch();

    if (!$token) {
        throw new RuntimeException('Token indisponível.');
    }

    $cand = $pdo->prepare('SELECT id FROM candidatos WHERE id = :id AND ativo = 1 LIMIT 1');
    $cand->execute([':id' => $candidatoId]);
    if (!$cand->fetch()) {
        throw new RuntimeException('Candidato inválido.');
    }

    $pStmt = $pdo->prepare('INSERT INTO participantes (token_id, nome_completo, whatsapp, fingerprint) VALUES (:token,:nome,:whats,:fp)');
    $pStmt->execute([
        ':token' => $token['id'],
        ':nome' => $flow['nome'],
        ':whats' => preg_replace('/\D+/', '', $flow['whatsapp']),
        ':fp' => $fingerprint,
    ]);

    $participanteId = (int) $pdo->lastInsertId();

    $vStmt = $pdo->prepare('INSERT INTO votos (token_id, participante_id, candidato_id, ip, dispositivo, navegador, user_agent, localizacao, fingerprint)
                            VALUES (:token,:pid,:cid,:ip,:disp,:nav,:ua,:loc,:fp)');
    $vStmt->execute([
        ':token' => $token['id'],
        ':pid' => $participanteId,
        ':cid' => $candidatoId,
        ':ip' => $ip,
        ':disp' => device_name($ua),
        ':nav' => browser_name($ua),
        ':ua' => $ua,
        ':loc' => $localizacao,
        ':fp' => $fingerprint,
    ]);

    $uStmt = $pdo->prepare('UPDATE tokens SET status = 1, used_at = NOW() WHERE id = :id AND status = 0');
    $uStmt->execute([':id' => $token['id']]);
    if ($uStmt->rowCount() !== 1) {
        throw new RuntimeException('Falha de concorrência ao confirmar token.');
    }

    $pdo->commit();

    $_SESSION['voto_finalizado'] = true;
    unset($_SESSION['vote_flow']);

    @file_put_contents(__DIR__ . '/../storage/config_cache.json', json_encode(['last_vote' => time()]), LOCK_EX);

    json_response(['ok' => true, 'msg' => 'Voto computado com sucesso.']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    app_log('erros', 'falha_registrar_voto', ['erro' => $e->getMessage(), 'ip' => $ip]);
    json_response(['ok' => false, 'msg' => 'Não foi possível registrar o voto.']);
}
