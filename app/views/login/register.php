<?php 
	$errors = (array)($_SESSION['errors'] ?? []);
	$old = (array)($_SESSION['old_input'] ?? []); 
?>
<section class="d-flex align-items-center justify-content-center py-4 mt-4 bg-body-tertiary">
	<div class="form-signup w-100 m-auto">
		<form action="<?php echo ABSOLUTURL; ?>login/store" method="post" enctype="multipart/form-data" id="create_users">
			<h1 class="h3 mb-3 fw-normal text-center">Please sign up</h1>
			<div class="form-floating form-group mb-3">
				<input type="email" class="form-control <?php echo !empty($errors['email']) ? 'is-invalid' : ''; ?>" name="email" id="email" value="<?php echo htmlspecialchars($old['email'] ?? '', ENT_QUOTES); ?>" placeholder="name@example.com" required autofocus autocomplete="email">
				<label for="email" class="form-label">Email address</label>
				<?php if (!empty($errors["email"])): ?>
					<?php foreach ($errors['email'] as $errorsEmail): ?>
						<div class="invalid-feedback">
							<?php echo htmlspecialchars($errorsEmail); ?>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
			<div class="form-floating form-group mb-3">
				<input type="text" name="username" id="username" class="form-control <?php echo !empty($errors['username']) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($old['username'] ?? '', ENT_QUOTES); ?>" placeholder="name6767" required  autocomplete="username">
				<label for="username" class="form-label">Username</label>
				<?php if (!empty($errors["username"])): ?>
					<?php foreach ($errors['username'] as $errorUsername): ?>
						<div class="invalid-feedback">
							<?php echo htmlspecialchars($errorUsername); ?>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
			<div class="form-floating form-group mb-3">
				<input type="file" name="photo_profile" id="photo" class="form-control <?php echo (!empty($errors['photo_profile']) || !empty($errors['photo_path'])) ? 'is-invalid' : ''; ?>" accept=".jpg, .png, .jpeg">
				<label for="photo" class="form-label mb-2">Photo profile</label>
				<?php if (!empty($errors["photo_profile"]) || !empty($errors['photo_path'])): ?>
					<div class="invalid-feedback">
						<?php 
							if (!empty($errors["photo_profile"])) {
								foreach ($errors['photo_profile'] as $errorPhotoProfile) {
									echo htmlspecialchars($errorPhotoProfile ?? '', ENT_QUOTES);
								}
							}
							echo !empty($errors['photo_profile']) && !empty($errors['photo_path']) ? '<br>' : '';
							if (!empty($errors['photo_path'])) {
								foreach ($errors['photo_path'] as $errorPhotoPath) {
									echo htmlspecialchars($errorPhotoPath ?? '', ENT_QUOTES);
								}
							}
						?>
					</div>
				<?php endif; ?>
			</div>
			<div class="form-floating form-group mb-3">
				<input type="password" class="form-control <?php echo !empty($errors['password']) ? 'is-invalid' : ''; ?>" name="password" id="password" required autocomplete="new-password">
				<label for="password" class="form-label">Password</label>
				<?php if (!empty($errors["password"])): ?>
					<?php foreach ($errors['password'] as $errorPassword): ?>
						<div class="invalid-feedback">
							<?php echo htmlspecialchars($errorPassword); ?>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
			<div class="form-floating form-group mb-3">
				<input type="password" class="form-control <?php echo !empty($errors['password_confirm']) ? 'is-invalid' : ''; ?>" name="password_confirm" id="password_confirm" required autocomplete="new-password">
				<label for="password_confirm" class="form-label">Confirm password</label>
				<?php if (!empty($errors["password_confirm"])): ?>
					<?php foreach ($errors['password_confirm'] as $errorPasswordConfirm): ?>
						<div class="invalid-feedback">
							<?php echo htmlspecialchars($errorPasswordConfirm); ?>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
			<input type="hidden" name="photo_path" id="photo_path">
			<button type="submit" class="btn btn-primary w-100 py-2" id="submit_register">Submit</button>
		</form>
		
		<small class="mt-3 mb-4">
			Have account?
			<a href="<?= ABSOLUTURL; ?>login" class="text-decoration-none ">login now</a>
		</small>	
	</div>
</section>


<?php unset($_SESSION['errors'], $_SESSION['old_input'], $errors, $old); ?>

<!-- create users script -->
<script>
	document.getElementById('create_users').addEventListener('submit', async function (event) {
		event.preventDefault();

		const form = this;
		const submitButton = document.getElementById('submit_register');
		const photoField = document.getElementById('photo');
		const photoPathField = document.getElementById('photo_path');

		submitButton.disabled = true;

		if (!photoField.files || photoField.files.length === 0) {
			form.submit();
			return;
		}

		try {
			const urlResponse = await fetch('<?= ABSOLUTURL; ?>files/signUrl', {
				method: 'POST'
			});

			if (!urlResponse.ok) throw new Error("Failed to generate URL.");

			const data = await urlResponse.json();
			// console.info(data);
			if (!data.url) throw new Error("No upload URL returned.");

			photoPathField.value = data.url;
			form.submit();
		} catch (error) {
			console.error("Error: Something wrong.", error);
			submitButton.disabled = false;
		}
	});
</script>