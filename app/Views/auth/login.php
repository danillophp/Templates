<div class="row justify-content-center">
  <div class="col-md-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h1 class="h5 mb-3">Acesso Restrito</h1>
        <?php if (!empty($error)): ?><div class="alert alert-danger"><?= \App\Core\Security::e($error) ?></div><?php endif; ?>
        <form method="post" action="index.php?r=auth/login" autocomplete="off">
          <input type="hidden" name="_csrf" value="<?= \App\Core\Security::e($csrf) ?>">

          <?php if (!empty($step2)): ?>
            <div class="mb-3">
              <label class="form-label">Código 2FA (TOTP)</label>
              <input type="text" name="totp_code" class="form-control" maxlength="6" inputmode="numeric" required>
            </div>
          <?php else: ?>
            <div class="mb-3">
              <label class="form-label">Usuário</label>
              <input type="text" name="username" class="form-control" maxlength="80" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Senha</label>
              <input type="password" name="password" class="form-control" required>
            </div>
          <?php endif; ?>

          <button class="btn btn-primary w-100" type="submit">Entrar</button>
        </form>
      </div>
    </div>
  </div>
</div>
