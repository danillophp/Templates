<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login Admin · Festival 14 de Maio</title>
  <link rel="stylesheet" href="public/assets/css/admin.css">
</head>
<body class="admin-login-body">
  <main class="admin-login-card">
    <h1>Painel Administrativo</h1>
    <p>Garota SADE 2026 · acesso restrito</p>
    <?php if (!empty($error ?? '')): ?><div class="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post" action="?r=festival-admin/login">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars(App\Core\Csrf::token()) ?>">
      <label>Usuário<input type="text" name="username" required></label>
      <label>Senha<input type="password" name="password" required></label>
      <button type="submit">Entrar</button>
    </form>
  </main>
</body>
</html>
