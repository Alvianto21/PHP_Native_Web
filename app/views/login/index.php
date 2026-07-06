<?php
	$errors = (array)($_SESSION['errors'] ?? []);
	$old = (array)($_SESSION['old_input'] ?? []);
?>
<div class="row">
	<div class="col-md-5">
		<?php Flasher::showFlash(); ?>
	</div>
</div>
<div class="col-md-6 mt-4">
	<form action="<?= ABSOLUTURL; ?>login/authen" method="post" id="login_user">
		<div class="mb-3 form-group">
			<label for="email" class="form-label">Email address</label>
			<input type="email" class="form-control <?php echo !empty($errors['email']) ? 'is-invalid' : ''; ?>" name="email" id="email" value="<?php echo htmlspecialchars($old['email'] ?? '', ENT_QUOTES); ?>" required autofocus autocomplete="email">
			<?php if (!empty($errors["email"])): ?>
				<div class="invalid-feedback">
					<?php echo htmlspecialchars($errors['email']); ?>
				</div>
			<?php endif; ?>
			<div class="mb-3 form-group">
				<label for="password" class="form-label">Password</label>
				<input type="password" class="form-control <?php echo !empty($errors['password']) ? 'is-invalid' : ''; ?>" name="password" id="password" required autocomplete="current-password">
				<?php if (!empty($errors["password"])): ?>
					<div class="invalid-feedback">
						<?php echo htmlspecialchars($errors['password']); ?>
					</div>
				<?php endif; ?>
			</div>
			<button type="submit" class="btn btn-primary" id="submit_login">Submit</button>
	</form>
</div>

<small class="mt-3">
	New Users?
	<a href="<?= ABSOLUTURL; ?>login/register" class="text-decoration-none ">Register now</a>
</small>

<?php unset($_SESSION['errors'], $_SESSION['old_input'], $errors, $old); ?>

<!-- login users script -->
<script>
	document.getElementById('login_user').addEventListener('submit', function(event) {
		const submitButton = document.getElementById('submit_login');
		submitButton.disabled = true;
	})
</script>