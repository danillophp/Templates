<?php
require_once __DIR__ . '/../../includes/functions.php';

start_session();
if (!empty($_SESSION['user'])) {
    $target = $_SESSION['user']['role'] === 'FUNCIONARIO' ? '../employee/dashboard.php' : 'dashboard.php';
    header('Location: ' . $target);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $db = db_connect();
    $stmt = $db->prepare('SELECT id, username, password_hash, role, full_name FROM users WHERE username = ? AND is_active = 1 LIMIT 1');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
            'full_name' => $user['full_name'],
        ];

        log_action(null, (int) $user['id'], $user['role'], 'LOGIN', 'Login realizado com sucesso.');

        $target = $user['role'] === 'FUNCIONARIO' ? '../employee/dashboard.php' : 'dashboard.php';
        header('Location: ' . $target);
        exit;
    }

    flash('error', 'Usuário ou senha inválidos.');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= APP_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
<div class="container small">
    <h1>Login Administrativo</h1>
    <?php if ($msg = flash('error')): ?><div class="alert error"><?= htmlspecialchars($msg); ?></div><?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()); ?>">
        <label>Usuário
            <input type="text" name="username" required>
        </label>
        <label>Senha
            <input type="password" name="password" required>
        </label>
        <button type="submit">Entrar</button>
    </form>
</div>
</body>
</html>
