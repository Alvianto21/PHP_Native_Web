<?php 
	$errors = (array)($_SESSION['errors'] ?? []);
	$old = (array)($_SESSION['old_input'] ?? []);
	$article = (array)($data['article']); 
?>
<section class="d-flex align-items-center justify-content-center py-4">
	<div class="form-article w-100 m-auto">
		<form action="<?php echo ABSOLUTURL; ?>dashboard/update/<?php echo htmlspecialchars($article['slug']); ?>" method="post" enctype="multipart/form-data" id="update_article">
			<h1 class="h3 mb-3 fw-normal">Edit article</h1>
			<div class="form-group mb-3 form-floating">
				<input class="form-control <?php echo !empty($errors['title']) ? 'is-invalid' : ''; ?>" type="text" name="title" id="title" placeholder="Need Cars Insurance" value="<?php echo htmlspecialchars($old['title'] ?? $article['title']); ?>" required>
				<label for="title" class="form-label">Title</label>
				<?php if (!empty($errors['title'])): ?>
					<div class="invalid-feedback">
						<?php echo htmlspecialchars($errors['title']); ?>
					</div>
				<?php endif; ?>
			</div>
			<div class="form-group mb-3 form-floating">
				<input type="hidden" name="old_photo_cover" value="<?php echo htmlspecialchars($article['photo_cover']); ?>">
				<input class="form-control <?php echo (!empty($errors['photo_cover']) || !empty($errors['photo_path'])) ? 'is-invalid' : ''; ?>" type="file" name="photo_cover" id="photo" accept=".jpg, .png, .jpeg">
				<label for="photo" class="form-label">Article Cover</label>
				<div class="form-text" id="photoHelperBlock">
					Fill This if you update your article cover.
				</div>
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
				<label for="body" class="form-label">body</label>
				<textarea class="form-control <?php echo !empty($errors['body']) ? 'is-invalid' : ''; ?>" name="body" id="body" cols="30" rows="10" placeholder="If you need car insurance, choosing the right policy is one of the most important financial decisions you can make. Car insurance helps protect you from unexpected expenses caused by accidents, theft, natural disasters, or damage to your vehicle. Whether you're a first-time car owner or looking to switch providers, understanding your options can help you find the best coverage at an affordable price." required><?php echo htmlspecialchars($article['body'] ?? $old['body']); ?></textarea>
				<?php if (!empty($errors['body'])): ?>
					<div class="invalid-feedback">
						<?php echo htmlspecialchars($errors['body']); ?>
					</div>
				<?php endif; ?>
			</div>
			<input type="hidden" name="photo_path" id="photo_path">
			<button type="submit" class="btn btn-primary w-100 py-2" id="revise_article">Submit</button>
		</form>
	</div>
</section>

<?php unset($_SESSION['errors'], $_SESSION['old_input'], $errors, $old, $article); ?>

<!-- Update article script -->
<script>
	document.getElementById('update_article').addEventListener('submit', async function (event) {
		// Stop action form
		event.preventDefault();
		const form = this;

		// Target submit button, input photo and photo_path
		const submitButton = document.getElementById('revise_article');
		const photoField = document.getElementById('photo');
		const photoPath = document.getElementById('photo_path');

		submitButton.disabled = true;
		console.info("preparing upload....");

		// If photo not filled, continue normal action.
		// Otherwise get temp sign URL.
		if (!photoField.files || photoField.files.length === 0) {
			console.info('No file added, continue normal form.');
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