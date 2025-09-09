<a href="<?= ABSOLUTURL; ?>admin/create" type="button">Tamabah data</a>
<table>
	<thead>
		<tr>
			<th>Nomor</th>
			<th>Judul</th>
			<th>Author</th>
			<th>Aksi</th>
		</tr>
	</thead>
	<tbody>
		<?php
		$i = 1;
		foreach($data['articles'] as $article) {
			?>
			<tr>
				<td><?= $i; ?></td>
				<td><?= $article['title']; ?></td>
				<td><?= $article['author']; ?></td>
				<td>
					<a href="<?= ABSOLUTURL; ?>admin/edit/<?= $article['id']; ?>">Edit</a>
					<a href="<?= ABSOLUTURL; ?>admin/delete/<?= $article['id']; ?>" onclick="return confirm('Yakin?');">Hapus</a>
				</td>
			</tr>
			<?php
			$i++;
		}
		?>
	</tbody>
</table>