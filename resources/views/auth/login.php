<!doctype html><html><body><h2>Login Admin</h2><?php if(!empty($error)) echo '<p>'.$error.'</p>'; ?>
<form method='post' action='<?=base_path('/login')?>'><input type='hidden' name='_csrf' value='<?=$csrf?>'>
<input name='login' placeholder='Usuário ou email' required><input type='password' name='senha' required><button>Entrar</button></form>
<a href='<?=base_path('/forgot-password')?>'>Esqueci a senha</a></body></html>
