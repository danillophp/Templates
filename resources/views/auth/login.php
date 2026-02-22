<!doctype html><html><body><main class='container'><div class='card'><h2>Login Admin</h2>
<?php if(!empty($_SESSION['error'])): ?><p><?= $_SESSION['error']; unset($_SESSION['error']); ?></p><?php endif; ?>
<form method='post' action='<?= $_ENV['APP_BASE_PATH'] ?>/login'>
<input type='hidden' name='_csrf' value='<?= \App\Core\Csrf::token() ?>'>
<input name='login' placeholder='Usuário ou e-mail' required><input type='password' name='senha' placeholder='Senha' required><button>Entrar</button>
</form><a href='<?= $_ENV['APP_BASE_PATH'] ?>/forgot-password'>Esqueci minha senha</a></div></main></body></html>
