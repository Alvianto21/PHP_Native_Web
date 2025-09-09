<div class="col-md-6">
	<form action="<?= ABSOLUTURL; ?>admin/store" method="post">
		<div class="form-group mb-3">
			<label for="title" class="form-label">Title</label>
			<input class="form-control" type="text" name="title" id="title" placeholder="Masukan judul artikel" required>
		</div>
		<div class="form-group mb-3">
			<label for="slug" class="form-label">Slug</label>
			<input class="form-control" type="text" name="slug" id="slug" placeholder="Masukkan-slug-artikel-sesuai-judul" required>
		</div>
		<div class="form-group mb-3">
			<label for="author" class="form-label">Author</label>
			<input class="form-control" type="text" name="author" id="author" placeholder="Nama penulis artikel" required>
		</div>
		<div class="form-group mb-3">
			<label for="body" class="form-label">body</label>
			<textarea class="form-control" name="body" id="body" cols="30" rows="10" placeholder="Konten artikel" required></textarea>
		</div>
		<button type="submit" class="btn btn-primary mt-4">Submit</button>
	</form>
</div>