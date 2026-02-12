<section class="admin-header">
    <h1>Painel Administrativo</h1>
    <p>Bem-vindo, <?= htmlspecialchars($user['name'] ?? 'Administrador') ?>.</p>
    <form method="post" action="/admin/logout">
        <?= $csrfField ?>
        <button type="submit">Sair</button>
    </form>
</section>
<section class="stats-grid">
    <article class="card"><h2>Localizações</h2><strong><?= (int) $locationsCount ?></strong></article>
    <article class="card"><h2>Políticos</h2><strong><?= (int) $politiciansCount ?></strong></article>
</section>
<nav class="admin-nav">
    <a href="/admin/locations">Gerenciar Localizações</a>
    <a href="/admin/politicians">Gerenciar Políticos</a>
    <a href="/" target="_blank" rel="noopener">Ver site público</a>
</nav>
