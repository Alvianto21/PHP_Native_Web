<?php 
	$errors = (array)($_SESSION['errors'] ?? []);
	$old = (array)($_SESSION['old_input'] ?? []); 
?>
<div class="col-md-6">
	<form action="<?= ABSOLUTURL; ?>login/store" method="post" enctype="multipart/form-data" id="create_users">
		<div class="mb-3 form-group">
			<label for="email" class="form-label">Email address</label>
			<input type="email" class="form-control <?php echo !empty($errors['email']) ? 'is-invalid' : ''; ?>" name="email" id="email" value="<?php echo htmlspecialchars($old['email'] ?? '', ENT_QUOTES); ?>" required autofocus autocomplete="email">
			<?php if (!empty($errors["email"])): ?>
				<div class="invalid-feedback">
        			<?php echo htmlspecialchars($errors['email']); ?>
      			</div>
			<?php endif; ?>
		</div>
		<div class="mb-3 form-group">
			<label for="username" class="form-label">Username</label>
			<input type="text" name="username" id="username" class="form-control <?php echo !empty($errors['username']) ? 'is-invalid' : ''; ?>" required value="<?php echo htmlspecialchars($old['username'] ?? '', ENT_QUOTES); ?>" autocomplete="username">
			<?php if (!empty($errors["username"])): ?>
				<div class="invalid-feedback">
					<?php echo htmlspecialchars($errors['username']); ?>
				  </div>
			<?php endif; ?>
		</div>
		<div class="mb-3">
			<label for="photo" class="form-label mb-2">Photo profile</label>
			<input type="file" name="photo_profile" id="photo" class="form-control <?php echo (!empty($errors['photo_profile']) || !empty($errors['photo_path'])) ? 'is-invalid' : ''; ?>" accept=".jpg, .png, .jpeg">
			<?php if (!empty($errors["photo_profile"]) || !empty($errors['photo_path'])): ?>
				<div class="invalid-feedback">
					<?php 
						echo htmlspecialchars($errors['photo_profile'] ?? '', ENT_QUOTES);
						echo !empty($errors['photo_profile']) && !empty($errors['photo_path']) ? '<br>' : '';
						echo htmlspecialchars($errors['photo_path'] ?? '', ENT_QUOTES); 
					?>
				  </div>
			<?php endif; ?>
		</div>
		<div class="mb-3 form-group">
			<label for="password" class="form-label">Password</label>
			<input type="password" class="form-control <?php echo !empty($errors['password']) ? 'is-invalid' : ''; ?>" name="password" id="password" required autocomplete="new-password">
			<?php if (!empty($errors["password"])): ?>
				<div class="invalid-feedback">
					<?php echo htmlspecialchars($errors['password']); ?>
				  </div>
			<?php endif; ?>
		</div>
		<div class="mb-3 form-group">
			<label for="password_confirm" class="form-label">Confirm password</label>
			<input type="password" class="form-control <?php echo !empty($errors['password_confirm']) ? 'is-invalid' : ''; ?>" name="password_confirm" id="password_confirm" required autocomplete="new-password">
			<?php if (!empty($errors["password_confirm"])): ?>
				<div class="invalid-feedback">
					<?php echo htmlspecialchars($errors['password_confirm']); ?>
				  </div>
			<?php endif; ?>
		</div>
		<input type="hidden" name="photo_path" id="photo_path">
		<button type="submit" class="btn btn-primary" id="submit_register">Submit</button>
	</form>
</div>

<small class="mt-3">
	Have account?
	<a href="<?= ABSOLUTURL; ?>login" class="text-decoration-none ">login now</a>
</small>

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
			const urlResponse = await fetch('<?= ABSOLUTURL; ?>upload/generate', {
				method: 'POST'
			});

			if (!urlResponse.ok) throw new Error("Failed to generate URL.");

			const data = await urlResponse.json();
			console.info(data);
			if (!data.url) throw new Error("No upload URL returned.");

			photoPathField.value = data.url;
			form.submit();
		} catch (error) {
			console.error("Error: Something wrong.", error);
			submitButton.disabled = false;
		}
	});
</script>