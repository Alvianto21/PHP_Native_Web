<form action="/public/admin/update" method="post">
	<input type="hidden" name="id" id="id" value="<?= $data['article']['id'] ?>">
	<div>
		<label for="title">Title</label>
		<input type="text" name="title" id="title" placeholder="Masukan judul artikel" value="<?= $data['article']['title'] ?>" required>
	</div>
	<div>
		<label for="slug">Slug</label>
		<input type="text" name="slug" id="slug" placeholder="Masukkan-slug-artikel-sesuai-judul" value="<?= $data['article']['slug'] ?>" required>
	</div>
	<div>
		<label for="author">Author</label>
		<input type="text" name="author" id="author" placeholder="Nama penulis artikel" value="<?= $data['article']['author'] ?>" required>
	</div>
	<div>
		<label for="body">body</label>
		<textarea name="body" id="body" cols="30" rows="10" placeholder="Konten artikel" required><?= $data['article']['body'] ?></textarea>
	</div>
	<input type="submit" value="Simpan">
</form>