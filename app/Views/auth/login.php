<div class="row justify-content-center">
  <div class="col-md-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h4 class="mb-3">Login</h4>
        <?php if (!empty($error)): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post" action="?r=auth/login">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
          <div class="mb-3"><label class="form-label">Usuário</label><input class="form-control" name="username" required></div>
          <div class="mb-3"><label class="form-label">Senha</label><input class="form-control" type="password" name="password" required></div>
          <button class="btn btn-success w-100">Entrar</button>
        </form>
      </div>
    </div>
  </div>
</div>
