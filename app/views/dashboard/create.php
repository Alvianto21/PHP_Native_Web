<?php 
	$errors = (array)($_SESSION['errors'] ?? []);
	$old = (array)($_SESSION['old_input'] ?? []); 
?>
<div class="col-md-6">
	<form action="<?php echo ABSOLUTURL; ?>dashboard/store" method="post" enctype="multipart/form-data" id="create_article">
		<div class="form-group mb-3">
			<label for="title" class="form-label">Title</label>
			<input class="form-control <?php echo !empty($errors['title']) ? 'is-invalid' : ''; ?>" type="text" name="title" id="title" placeholder="Need Cars Insurance" value="<?php echo htmlspecialchars($old['title'] ?? '', ENT_QUOTES); ?>" required>
			<?php if (!empty($errors['title'])): ?>
				<div class="invalid-feedback">
					<?php echo htmlspecialchars($errors['title']); ?>
				</div>
			<?php endif; ?>
		</div>
		<div class="form-group mb-3">
			<label for="photo" class="form-label">Article Cover</label>
			<input class="form-control <?php echo (!empty($errors['photo_cover']) || !empty($errors['photo_path'])) ? 'is-invalid' : ''; ?>" type="file" name="photo_cover" id="photo" accept=".jpg, .png, .jpeg">
			<?php if (!empty($errors["photo_cover"]) || !empty($errors['photo_path'])): ?>
				<div class="invalid-feedback">
					<?php 
						echo htmlspecialchars($errors['photo_cover'] ?? '', ENT_QUOTES);
						echo !empty($errors['photo_cover']) && !empty($errors['photo_path']) ? '<br>' : '';
						echo htmlspecialchars($errors['photo_path'] ?? '', ENT_QUOTES); 
					?>
				  </div>
			<?php endif; ?>
		</div>
		<div class="form-group mb-3">
			<label for="body" class="form-label">Body article</label>
			<textarea class="form-control <?php echo !empty($errors['body']) ? 'is-invalid' : ''; ?>" name="body" id="body" cols="30" rows="10" placeholder="If you need car insurance, choosing the right policy is one of the most important financial decisions you can make. Car insurance helps protect you from unexpected expenses caused by accidents, theft, natural disasters, or damage to your vehicle. Whether you're a first-time car owner or looking to switch providers, understanding your options can help you find the best coverage at an affordable price." required><?php echo htmlspecialchars($old['body'] ?? '', ENT_QUOTES); ?></textarea>
			<?php if (!empty($errors['body'])): ?>
				<div class="invalid-feedback">
					<?php echo htmlspecialchars($errors['body']); ?>
				</div>
			<?php endif; ?>
		</div>
		<input type="hidden" name="photo_path" id="photo_path">
		<button type="submit" class="btn btn-primary mt-4" id="new_article">Submit</button>
	</form>
</div>
<?php unset($_SESSION['errors'], $_SESSION['old_input'], $errors, $old); ?>

<!-- Create article script -->
 <script>
	document.getElementById('create_article').addEventListener('submit', async function (event) {
		// Stop action form
		event.preventDefault();
		const form = this;

		// Target submit button, input photo and photo_path
		const submitButton = document.getElementById('new_article');
		const photoField = document.getElementById('photo');
		const photoPath = document.getElementById('photo_path');

		submitButton.disabled = true;

		// If photo not filled, continue normal action.
		// Otherwise get temp sign URL.
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

			photoPath.value = data.url;
			console.info('uploading...');
			form.submit();
		} catch (error) {
			console.error("Error: Something wrong.", error);
			submitButton.disabled = false;
		}
	})
 </script>