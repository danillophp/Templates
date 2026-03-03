<h1>Login E-EDUCA</h1>
<?php if (!empty($error)): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form method="post" action="index.php?r=auth/login" class="row g-3">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
    <div class="col-12"><input class="form-control" name="username" placeholder="Usuário"></div>
    <div class="col-12"><input class="form-control" type="password" name="password" placeholder="Senha"></div>
    <div class="col-12"><button class="btn btn-primary">Entrar</button></div>
</form>
