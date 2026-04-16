<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf(post('csrf_token'))) {
        json_response(['ok' => false, 'msg' => 'CSRF inválido'], 419);
    }

    $usuario = post('usuario');
    $senha = post('senha');

    $stmt = db()->prepare('SELECT id, usuario, senha_hash, nome FROM admin_users WHERE usuario = :u LIMIT 1');
    $stmt->execute([':u' => $usuario]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($senha, $admin['senha_hash'])) {
        $_SESSION['admin_logged'] = true;
        $_SESSION['admin_id'] = (int) $admin['id'];
        $_SESSION['admin_nome'] = $admin['nome'];
        db()->prepare('UPDATE admin_users SET ultimo_login = NOW() WHERE id = :id')->execute([':id' => $admin['id']]);
        header('Location: ' . app_path('admin.php'));
        exit;
    }

    $erro = 'Usuário ou senha inválidos.';
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">
<form method="post" class="bg-white shadow-lg rounded-2xl w-full max-w-md p-6 space-y-4">
  <h1 class="text-xl font-semibold">Painel Administrativo</h1>
  <?php if (!empty($erro)): ?><div class="text-red-600 text-sm"><?= e($erro) ?></div><?php endif; ?>
  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
  <input name="usuario" class="w-full border rounded-xl p-3" placeholder="Usuário" required>
  <input type="password" name="senha" class="w-full border rounded-xl p-3" placeholder="Senha" required>
  <button class="w-full bg-slate-900 text-white p-3 rounded-xl">Entrar</button>
  <p class="text-xs text-slate-500">Troque a senha padrão após instalar.</p>
</form>
</body>
</html>
