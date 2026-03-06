<h1>CRUD Notícias</h1>
<form method="post" action="index.php?r=admin/noticias/salvar" enctype="multipart/form-data">
<input type="hidden" name="_csrf" value="<?= \App\Core\Security::e($csrf) ?>">
<input class="form-control mb-2" name="title" placeholder="Título" required maxlength="200">
<input class="form-control mb-2" name="category" placeholder="Categoria" required maxlength="80">
<textarea class="form-control mb-2" name="summary" placeholder="Resumo"></textarea>
<textarea class="form-control mb-2" name="body" placeholder="Conteúdo"></textarea>
<input class="form-control mb-2" type="file" name="image" accept="image/jpeg,image/png">
<button class="btn btn-primary">Publicar</button>
</form>
