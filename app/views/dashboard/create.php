<form action="/public/admin/store" method="post">
	<div>
		<label for="title">Title</label>
		<input type="text" name="title" id="title" placeholder="Masukan judul artikel" >
	</div>
	<div>
		<label for="slug">Slug</label>
		<input type="text" name="slug" id="slug" placeholder="Masukkan-slug-artikel-sesuai-judul" required>
	</div>
	<div>
		<label for="author">Author</label>
		<input type="text" name="author" id="author" placeholder="Nama penulis artikel" required>
	</div>
	<div>
		<label for="body">body</label>
		<textarea name="body" id="body" cols="30" rows="10" placeholder="Konten artikel" required></textarea>
	</div>
	<input type="submit" value="Simpan">
</form>