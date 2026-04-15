<?php require __DIR__ . '/partials/header.php'; ?>
<section class="panel">
  <h2>Gestão de candidatas</h2>
  <form id="candidateForm" class="grid-form" method="post" action="?r=api/festival-admin/candidates/save">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(App\Core\Csrf::token()) ?>">
    <input type="hidden" name="id" value="">
    <label>Número<input type="number" name="numero" required></label>
    <label>Nome<input type="text" name="nome" required></label>
    <label>Slug<input type="text" name="slug" required></label>
    <label>Idade<input type="number" name="idade" required></label>
    <label>Cidade<input type="text" name="cidade" required></label>
    <label>Foto<input type="text" name="foto" placeholder="candidata-01.jpg" required></label>
    <label>Status
      <select name="status"><option value="ATIVA">ATIVA</option><option value="INATIVA">INATIVA</option></select>
    </label>
    <label>Descrição<textarea name="descricao" rows="3" required></textarea></label>
    <label class="check"><input type="checkbox" name="exibir_publicamente" checked> Exibir publicamente</label>
    <button type="submit">Salvar candidata</button>
  </form>
</section>

<section class="panel">
  <table>
    <thead><tr><th>ID</th><th>Número</th><th>Nome</th><th>Status</th><th>Exibição</th><th>Votos</th></tr></thead>
    <tbody>
      <?php foreach ($candidates as $candidate): ?>
        <tr>
          <td><?= (int) $candidate['id'] ?></td>
          <td><?= (int) $candidate['numero'] ?></td>
          <td><?= htmlspecialchars($candidate['nome']) ?></td>
          <td><?= htmlspecialchars($candidate['status']) ?></td>
          <td><?= (int) $candidate['exibir_publicamente'] ? 'SIM' : 'NÃO' ?></td>
          <td><?= (int) $candidate['votos_total'] ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
