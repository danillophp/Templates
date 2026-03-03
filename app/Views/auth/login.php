<div class="row justify-content-center">
  <div class="col-md-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h4 class="mb-3">Login da Equipe</h4>
        <?php if (!empty($error)): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if (!empty($success)): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <form method="post" action="<?= APP_BASE_PATH ?>/?r=auth/login" novalidate>
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
          <div class="mb-3"><label class="form-label">Usuário ou E-mail</label><input class="form-control" type="text" name="identifier" required></div>
          <div class="mb-3"><label class="form-label">Senha</label><input class="form-control" type="password" name="senha" required></div>
          <button class="btn btn-success w-100">Entrar</button>
        </form>

        <hr>
        <form method="post" action="<?= APP_BASE_PATH ?>/?r=auth/forgot" novalidate>
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
          <label class="form-label">Esqueci minha senha</label>
          <div class="input-group">
            <input class="form-control" type="email" name="email" placeholder="Informe seu e-mail" required>
            <button class="btn btn-outline-primary" type="submit">Recuperar</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
