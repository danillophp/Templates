<section class="card auth-card">
    <h1>Login administrativo</h1>
    <?php if (!empty($error)): ?>
        <p class="alert alert-error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <form method="post" action="/admin/login" class="form-grid">
        <?= $csrfField ?>
        <label>E-mail
            <input type="email" name="email" required>
        </label>
        <label>Senha
            <input type="password" name="password" required>
        </label>
        <button type="submit">Entrar</button>
    </form>
</section>
