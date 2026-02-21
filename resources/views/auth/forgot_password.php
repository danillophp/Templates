<!doctype html><html><body><h2>Recuperar senha</h2><?php if(!empty($success)) echo '<p>'.$success.'</p>'; ?>
<form method='post' action='<?=base_path('/forgot-password')?>'><input type='hidden' name='_csrf' value='<?=$csrf?>'>
<input type='email' name='email' required><button>Gerar senha temporária</button></form></body></html>
