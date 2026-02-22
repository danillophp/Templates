<!doctype html><html><body><main class='container'><div class='card'><h2>Recuperar senha</h2>
<?php if(!empty($_SESSION['ok'])): ?><p><?= $_SESSION['ok']; unset($_SESSION['ok']); ?></p><?php endif; ?>
<form method='post'><input type='hidden' name='_csrf' value='<?= \App\Core\Csrf::token() ?>'><input type='email' name='email' required><button>Gerar senha temporária</button></form>
</div></main></body></html>
