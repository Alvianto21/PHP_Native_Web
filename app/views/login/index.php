<?php
	$errors = (array)($_SESSION['errors'] ?? []);
	$old = (array)($_SESSION['old_input'] ?? []);
?>
<div class="row m-3">
	<div class="col-md-5">
		<?php Flasher::showFlash(); ?>
	</div>
</div>

<section class="d-flex align-items-center py-4 bg-body-tertiary">
	<div class="form-signin w-100 m-auto">
		<form action="<?php echo ABSOLUTURL; ?>login/authen" method="post" id="login_user">
			<h1 class="h3 mb-3 fw-normal">Please sign in</h1>
			<div class="mb-3 form-group form-floating">
				<input type="email" class="form-control <?php echo !empty($errors['email']) ? 'is-invalid' : ''; ?>" name="email" id="email" value="<?php echo htmlspecialchars($old['email'] ?? '', ENT_QUOTES); ?>" placeholder="name@example.com" required autofocus autocomplete="email">
				<label for="email" class="form-label">Email address</label>
				<?php if (!empty($errors["email"])): ?>
					<div class="invalid-feedback">
						<?php echo htmlspecialchars($errors['email']); ?>
					</div>
				<?php endif; ?>
			</div>
				<div class="mb-3 form-group form-floating">
					<input type="password" class="form-control <?php echo !empty($errors['password']) ? 'is-invalid' : ''; ?>" name="password" id="password" required autocomplete="current-password">
					<label for="password" class="form-label">Password</label>
					<?php if (!empty($errors["password"])): ?>
						<div class="invalid-feedback">
							<?php echo htmlspecialchars($errors['password']); ?>
						</div>
					<?php endif; ?>
				</div>
				<button type="submit" class="btn btn-primary w-100 py-2" id="submit_login">Submit</button>
		</form>

		<small class="mt-3 mb-4">
			New Users?
			<a href="<?= ABSOLUTURL; ?>login/register" class="text-decoration-none ">Register now</a>
		</small>
	</div>
</section>

<?php unset($_SESSION['errors'], $_SESSION['old_input'], $errors, $old); ?>

<!-- login users script -->
<script>
	document.getElementById('login_user').addEventListener('submit', function(event) {
		const submitButton = document.getElementById('submit_login');
		submitButton.disabled = true;
	})
</script>