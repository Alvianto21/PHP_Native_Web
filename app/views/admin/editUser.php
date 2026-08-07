<?php 
	$errors = (array)($_SESSION['errors'] ?? []);
	$old = (array)($_SESSION['old_input'] ?? []);
	$user = (array)($data['user']);
?>

<section class="d-flex align-items-center justify-content-center py-4 mt-4 bg-body-tertiary">
	<div class="form-signup w-100 m-auto overflow-y-scroll">
		<form action="<?php echo ABSOLUTURL; ?>admin/userUpdate/<?php echo htmlspecialchars($user['username']); ?>" method="post" enctype="multipart/form-data" id="update_users_admin" class="">
			<h1 class="h3 mb-3 fw-normal text-center">Update profile <?php echo htmlspecialchars($user['username']); ?></h1>
			<div class="form-floating form-group mb-3">
				<input type="email" class="form-control <?php echo !empty($errors['email']) ? 'is-invalid' : ''; ?>" name="email" id="email" value="<?php echo htmlspecialchars($old['email'] ?? $user['email'], ENT_QUOTES); ?>" placeholder="name@example.com" required autofocus autocomplete="email">
				<label for="email" class="form-label">Email address</label>
				<?php if (!empty($errors["email"])): ?>
					<div class="invalid-feedback">
						<?php echo htmlspecialchars($errors['email']); ?>
					  </div>
				<?php endif; ?>
			</div>
			<div class="form-floating form-group mb-3">
				<input type="text" name="username" id="username" class="form-control <?php echo !empty($errors['username']) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($old['username'] ?? $user['username'], ENT_QUOTES); ?>" placeholder="name6767" required  autocomplete="username">
				<label for="username" class="form-label">Username</label>
				<?php if (!empty($errors["username"])): ?>
					<div class="invalid-feedback">
						<?php echo htmlspecialchars($errors['username']); ?>
					  </div>
				<?php endif; ?>
			</div>
			<div class="form-floating form-group mb-3">
				<input type="hidden" name="old_photo_profile" value="<?php echo htmlspecialchars($user['photo_profile'] ?? '', ENT_QUOTES); ?>">
				<input type="file" name="photo_profile" id="photo" class="form-control <?php echo (!empty($errors['photo_profile']) || !empty($errors['photo_path'])) ? 'is-invalid' : ''; ?>" accept=".jpg, .png, .jpeg">
				<label for="photo" class="form-label mb-2">Photo profile</label>
				<div class="form-text" id="photoHelperBlock">
					Fill This if you update photo cover for this user.
				</div>
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
			<div class="form-floating form-control mb-3">
				<select name="is_deleted" id="is_deleted" class="form-select" required>
					<option value="">Select Account Status</option>
					<option value="0" <?php echo $user['is_deleted'] === 0 ? 'selected' : ''; ?>>Available</option>
					<option value="1" <?php echo $user['is_deleted'] === 1 ? 'selected' : ''; ?>>Deleted</option>
				</select>
				<label for="is_deleted" class="form-label">Account Status</label>
			</div>
			<div class="form-floating form-control mb-3">
				<select name="role" id="role" class="form-select" required>
					<option value="">Select User Role</option>
					<option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
					<option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
				</select>
				<label for="role" class="form-label">User Role</label>
			</div>
			<div class="form-floating form-group form-check mb-3">
				<input type="text" name="password" id="password" readonly class="form-control-plaintext">
				<button type="button" style="display: none;" id="copyButton" onclick="copyValue()">Copy <i class="bi bi-clipboard"></i></button>
				<button type="button" class="btn btn-warning m-3" onclick="passwordGenerator()" id="generate_password">New Password</button>
				<label for="password" class="form-label form-check-label">Regenerate new password</label>
				<div class="form-text" id="regeneratePasswordHelper">
					Click this if you need reset this user password.
					Copy generate value before send the form.
				</div>
			</div>
			<input type="hidden" name="photo_path" id="photo_path">
			<input type="hidden" name="password_confirm" id="password_confirm">
			<button type="submit" class="btn btn-primary w-100 py-2" id="revise_user_admin">Submit</button>
		</form>	
	</div>
</section>


<?php unset($_SESSION['errors'], $_SESSION['old_input'], $errors, $old, $user); ?>

<!-- Update users script -->
<script>
	// Regenerate password
	async function passwordGenerator() {
		const btnTrigger = document.getElementById('generate_password');
		const btnCopy = document.getElementById('copyButton');
		const btnSubmit = document.getElementById('revise_user_admin');
		const passwordInput = document.getElementById('password');
		const passwordNewInput = document.getElementById('password_confirm');

		btnTrigger.disabled = true;
		btnSubmit.disabled = true;

		try {
			const urlResponse = await fetch('<?php echo ABSOLUTURL; ?>admin/tempPasswordGenerator', {
				method: 'POST'
			});

			if (!urlResponse.ok) throw new Error("Failed to Generate password.")

			const data = await urlResponse.json();
			
			if (!data.regenerate_password) throw new Error('Failed to get new password');

			passwordInput.value = data.regenerate_password;
			passwordNewInput.value = data.regenerate_password;

			btnCopy.style.display = 'block';
			btnTrigger.style.display = 'none';
			btnSubmit.disabled = false;
		} catch (error) {
			console.error("Error: Something wrong!");
			alert('Failed to generate new password.');

			btnTrigger.disabled = false;
			btnSubmit.disabled = false;
			btnCopy.style.display = 'none';
		}
	}

	// Copy new password to clipboard
	function copyValue() {
		const btnTrigger = document.getElementById('copyButton');
		const passwordInput = document.getElementById('password');
		const inputTarget = passwordInput ? passwordInput.value : '';

		if (!inputTarget) {
			console.warn('No password text available to copy.');
			return;
		}

		const copyText = () => {
			if (document.getElementById('copyAlert')) return;

			const alertDiv = document.createElement('div');
			alertDiv.id = 'copyAlert';
			alertDiv.className = 'alert alert-success mt-2';
			alertDiv.innerText = 'Text Copied!';

			btnTrigger.insertAdjacentElement('afterend', alertDiv);
			setTimeout(() => alertDiv.remove(), 3000);
		};

		if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
			navigator.clipboard.writeText(inputTarget)
				.then(copyText)
				.catch(() => {
					fallbackCopyText(inputTarget, copyText);
				});
		} else {
			fallbackCopyText(inputTarget, copyText);
		}
	}

	function fallbackCopyText(text, onSuccess) {
		const textarea = document.createElement('textarea');
		textarea.value = text;
		textarea.style.position = 'fixed';
		textarea.style.left = '-9999px';
		document.body.appendChild(textarea);
		textarea.focus();
		textarea.select();

		try {
			if (document.execCommand('copy')) {
				onSuccess();
			} else {
				console.error('Fallback copy command failed.');
			}
		} catch (error) {
			console.error('Fallback copy error', error);
		} finally {
			document.body.removeChild(textarea);
		}
	}

	// Submit form
	document.getElementById('update_users_admin').addEventListener('submit', async function (event) {
		event.preventDefault();

		const form = this;
		const submitButton = document.getElementById('revise_user_admin');
		const photoField = document.getElementById('photo');
		const photoPathField = document.getElementById('photo_path');

		submitButton.disabled = true;

		if (!photoField.files || photoField.files.length === 0) {
			form.submit();
			return;
		}

		try {
			const urlResponse = await fetch('<?php echo ABSOLUTURL; ?>files/signUrl', {
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
			alert(error);
			submitButton.disabled = false;
		}
	});
</script>