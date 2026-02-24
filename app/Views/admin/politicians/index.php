<section class="admin-header">
    <h1>Cadastro de Dados Políticos</h1>
    <a href="/admin">← Voltar ao painel</a>
</section>

<?php if (!empty($flash)): ?>
    <p class="alert alert-success"><?= htmlspecialchars($flash) ?></p>
<?php endif; ?>

<section class="card">
    <h2><?= $editing ? 'Editar político' : 'Novo político' ?></h2>
    <form method="post" enctype="multipart/form-data" action="<?= $editing ? '/admin/politicians/update' : '/admin/politicians/create' ?>" class="form-grid">
        <?= $csrfField ?>
        <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int) $editing['id'] ?>"><?php endif; ?>
        <label>Localização
            <select name="location_id" required>
                <option value="">Selecione</option>
                <?php foreach ($locations as $location): ?>
                    <option value="<?= (int) $location['id'] ?>" <?= ((int) ($editing['location_id'] ?? 0) === (int) $location['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($location['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Nome completo<input name="full_name" required value="<?= htmlspecialchars($editing['full_name'] ?? '') ?>"></label>
        <label>Cargo político<input name="position" required value="<?= htmlspecialchars($editing['position'] ?? '') ?>"></label>
        <label>Partido político<input name="party" required value="<?= htmlspecialchars($editing['party'] ?? '') ?>"></label>
        <label>Idade<input type="number" min="18" name="age" required value="<?= htmlspecialchars((string) ($editing['age'] ?? '')) ?>"></label>
        <label>Telefone<input name="phone" value="<?= htmlspecialchars($editing['phone'] ?? '') ?>"></label>
        <label>E-mail<input type="email" name="email" value="<?= htmlspecialchars($editing['email'] ?? '') ?>"></label>
        <label>Nome dos assessores<input name="advisors" value="<?= htmlspecialchars($editing['advisors'] ?? '') ?>"></label>
        <label>Biografia<textarea name="biography" rows="3"><?= htmlspecialchars($editing['biography'] ?? '') ?></textarea></label>
        <label>Histórico da carreira<textarea name="career_history" rows="3"><?= htmlspecialchars($editing['career_history'] ?? '') ?></textarea></label>
        <label>História município/região<textarea name="municipality_history" rows="3"><?= htmlspecialchars($editing['municipality_history'] ?? '') ?></textarea></label>
        <label>Foto<input type="file" name="photo" accept="image/png,image/jpeg,image/webp"></label>
        <button type="submit"><?= $editing ? 'Atualizar' : 'Salvar' ?></button>
    </form>
</section>

<section class="card">
    <h2>Políticos cadastrados</h2>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Nome</th><th>Cargo</th><th>Partido</th><th>Local</th><th>Ações</th></tr></thead>
        <tbody>
        <?php foreach ($politicians as $politician): ?>
            <tr>
                <td><?= htmlspecialchars($politician['full_name']) ?></td>
                <td><?= htmlspecialchars($politician['position']) ?></td>
                <td><?= htmlspecialchars($politician['party']) ?></td>
                <td><?= htmlspecialchars($politician['location_name']) ?></td>
                <td>
                    <a href="/admin/politicians/edit?id=<?= (int) $politician['id'] ?>">Editar</a>
                    <form method="post" action="/admin/politicians/delete" class="inline-form" onsubmit="return confirm('Excluir político?');">
                        <?= $csrfField ?>
                        <input type="hidden" name="id" value="<?= (int) $politician['id'] ?>">
                        <button type="submit">Excluir</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</section>
