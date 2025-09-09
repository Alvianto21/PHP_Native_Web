<div class="row">
	<div class="col-md-5">
		<?php Flasher::showFlash(); ?>
	</div>
</div>
<div class="d-grid d-md-block mt-4">
	<a href="<?= ABSOLUTURL; ?>admin/create" type="button" class="btn btn-primary">Tamabah data</a>
</div>
<div class="col-md-6 table-responsive mt-3">
	<table class="table table-striped-columns table-hover">
		<thead>
			<tr>
				<th scope="col">Nomor</th>
				<th scope="col">Judul</th>
				<th scope="col">Author</th>
				<th scope="col">Aksi</th>
			</tr>
		</thead>
		<tbody>
			<?php
			$i = 1;
			foreach($data['articles'] as $article) {
				?>
				<tr>
					<th scope="row"><?= $i; ?></th>
					<td><?= $article['title']; ?></td>
					<td><?= $article['author']; ?></td>
					<td>
						<a href="<?= ABSOLUTURL; ?>admin/edit/<?= $article['id']; ?>" class="badge bg-warning text-decoration-none">Edit</a>
						<a href="<?= ABSOLUTURL; ?>admin/delete/<?= $article['id']; ?>" class="badge bg-danger text-decoration-none" onclick="return confirm('Yakin?');">Hapus</a>
					</td>
				</tr>
				<?php
				$i++;
			}
			?>
		</tbody>
	</table>
</div>