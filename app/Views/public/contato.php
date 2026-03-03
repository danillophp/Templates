<h1>Fale Conosco</h1>
<?php if (!empty($success)): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<form method="post" action="index.php?r=contato/enviar" class="row g-2">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
    <input type="text" name="company" class="d-none" tabindex="-1" autocomplete="off">
    <div class="col-6"><input class="form-control" name="name" required placeholder="Nome"></div>
    <div class="col-6"><input class="form-control" type="email" name="email" required placeholder="Email"></div>
    <div class="col-12"><input class="form-control" name="subject" required placeholder="Assunto"></div>
    <div class="col-12"><textarea class="form-control" name="message" required placeholder="Mensagem"></textarea></div>
    <div class="col-12"><button class="btn btn-primary">Enviar</button></div>
</form>
