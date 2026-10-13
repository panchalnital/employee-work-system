
<section class="login"><p class="eyebrow">JOIN YOUR TEAM</p><h1>Create an account.</h1><p>Register as an employee to track tasks and share daily updates.</p>
<form method="post" action="?page=register">
<?=csrf()?><input type="hidden" name="action" value="register">
<label>Full name<input name="name" maxlength="100" autocomplete="name" value="<?=e($oldName)?>" required></label>
<label>Email<input type="email" name="email" maxlength="190" autocomplete="username" value="<?=e($oldEmail)?>" required></label>
<label>Password<input type="password" name="password" minlength="12" maxlength="72" autocomplete="new-password" aria-describedby="password-help" required></label>
<p id="password-help">Use 12–72 characters for an ASCII password. Non-ASCII characters may use multiple bytes; the limit is 72 bytes.</p>
<label>Confirm password<input type="password" name="password_confirmation" minlength="12" maxlength="72" autocomplete="new-password" required></label>
<button>Create employee account</button>
</form><p>Already registered? <a href="?page=login">Sign in</a></p></section>
