<section class="admin-header">
    <h1>Cadastro de Localizações</h1>
    <a href="/admin">← Voltar ao painel</a>
</section>

<?php if (!empty($flash)): ?>
    <p class="alert alert-success"><?= htmlspecialchars($flash) ?></p>
<?php endif; ?>

<section class="card">
    <h2><?= $editing ? 'Editar localização' : 'Nova localização' ?></h2>
    <form method="post" action="<?= $editing ? '/admin/locations/update' : '/admin/locations/create' ?>" class="form-grid">
        <?= $csrfField ?>
        <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int) $editing['id'] ?>"><?php endif; ?>
        <label>Nome do local<input name="name" required value="<?= htmlspecialchars($editing['name'] ?? '') ?>"></label>
        <label>Endereço<input name="address" required value="<?= htmlspecialchars($editing['address'] ?? '') ?>"></label>
        <label>CEP<input name="postal_code" value="<?= htmlspecialchars($editing['postal_code'] ?? '') ?>"></label>
        <label>Latitude<input type="number" step="0.000001" name="latitude" required value="<?= htmlspecialchars((string) ($editing['latitude'] ?? '')) ?>"></label>
        <label>Longitude<input type="number" step="0.000001" name="longitude" required value="<?= htmlspecialchars((string) ($editing['longitude'] ?? '')) ?>"></label>
        <label>Informações do município<textarea name="city_info" rows="3"><?= htmlspecialchars($editing['city_info'] ?? '') ?></textarea></label>
        <label>Informações da região<textarea name="region_info" rows="3"><?= htmlspecialchars($editing['region_info'] ?? '') ?></textarea></label>
        <button type="submit"><?= $editing ? 'Atualizar' : 'Salvar' ?></button>
    </form>
    <p class="hint">Dica: você pode obter latitude/longitude clicando no mapa público e inspecionando coordenadas no OpenStreetMap / Leaflet.</p>
</section>

<section class="card">
    <h2>Localizações cadastradas</h2>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Local</th><th>Endereço</th><th>Coord.</th><th>Ações</th></tr></thead>
        <tbody>
        <?php foreach ($locations as $location): ?>
            <tr>
                <td><?= htmlspecialchars($location['name']) ?></td>
                <td><?= htmlspecialchars($location['address']) ?></td>
                <td><?= htmlspecialchars($location['latitude']) ?>, <?= htmlspecialchars($location['longitude']) ?></td>
                <td>
                    <a href="/admin/locations/edit?id=<?= (int) $location['id'] ?>">Editar</a>
                    <form method="post" action="/admin/locations/delete" class="inline-form" onsubmit="return confirm('Excluir localização?');">
                        <?= $csrfField ?>
                        <input type="hidden" name="id" value="<?= (int) $location['id'] ?>">
                        <button type="submit">Excluir</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</section>
