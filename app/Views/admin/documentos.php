<h1>CRUD Documentos</h1>
<form method="post" action="index.php?r=admin/documentos/salvar" enctype="multipart/form-data">
<input type="hidden" name="_csrf" value="<?= \App\Core\Security::e($csrf) ?>">
<input class="form-control mb-2" name="title" placeholder="Título" required maxlength="200">
<input class="form-control mb-2" name="category" placeholder="Categoria" required maxlength="100">
<input class="form-control mb-2" name="tags" placeholder="Tags" maxlength="255">
<input class="form-control mb-2" name="year_ref" type="number" value="2026">
<input class="form-control mb-2" type="file" name="document" accept="application/pdf" required>
<button class="btn btn-primary">Salvar</button>
</form>
