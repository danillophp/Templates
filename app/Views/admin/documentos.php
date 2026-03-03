<h1>CRUD Documentos</h1>
<form method="post" action="index.php?r=admin/documentos/salvar">
<input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
<input class="form-control mb-2" name="title" placeholder="Título" required>
<input class="form-control mb-2" name="category" placeholder="Categoria" required>
<input class="form-control mb-2" name="tags" placeholder="Tags">
<input class="form-control mb-2" name="year_ref" type="number" value="2026">
<input class="form-control mb-2" name="file_path" placeholder="Caminho do PDF" required>
<button class="btn btn-primary">Salvar</button>
</form>
