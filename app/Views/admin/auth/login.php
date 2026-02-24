<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h1 class="h4 mb-3">CATA TRECO - Login</h1>
                <?php if (!empty($error)): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                <form method="post" action="<?= htmlspecialchars($baseUrl) ?>/public/admin/login">
                    <?= $csrfField ?>
                    <div class="mb-3"><label class="form-label">Usuário</label><input type="text" name="username" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Senha</label><input type="password" name="password" class="form-control" required></div>
                    <button class="btn btn-success w-100" type="submit">Entrar</button>
                </form>
            </div>
        </div>
    </div>
</div>
