<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'msg' => 'Método inválido'], 405);
}
if (!validate_csrf(post('csrf_token'))) {
    json_response(['ok' => false, 'msg' => 'CSRF inválido'], 419);
}
if (empty($_SESSION['vote_flow']['token'])) {
    json_response(['ok' => false, 'msg' => 'Valide o token antes.'], 422);
}

$nome = post('nome');
$whatsapp = post('whatsapp');
$fingerprint = substr(post('fingerprint'), 0, 64);

if (mb_strlen($nome) < 5) {
    json_response(['ok' => false, 'msg' => 'Nome muito curto.']);
}
if (!valid_whatsapp($whatsapp)) {
    json_response(['ok' => false, 'msg' => 'WhatsApp inválido.']);
}

$_SESSION['vote_flow']['nome'] = $nome;
$_SESSION['vote_flow']['whatsapp'] = $whatsapp;
$_SESSION['vote_flow']['fingerprint'] = $fingerprint;

json_response(['ok' => true, 'msg' => 'Cadastro salvo.']);
