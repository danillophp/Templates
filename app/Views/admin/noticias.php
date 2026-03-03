<h1>CRUD Notícias</h1>
<form method="post" action="index.php?r=admin/noticias/salvar">
<input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
<input class="form-control mb-2" name="title" placeholder="Título" required>
<input class="form-control mb-2" name="category" placeholder="Categoria" required>
<textarea class="form-control mb-2" name="summary" placeholder="Resumo"></textarea>
<textarea class="form-control mb-2" name="body" placeholder="Conteúdo"></textarea>
<button class="btn btn-primary">Publicar</button>
</form>
