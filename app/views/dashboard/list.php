<?php $articles = (array)($data['articles'] ?? []); ?>
<div class="row">
	<div class="col-md-5">
		<?php Flasher::showFlash(); ?>
	</div>
</div>
<div class="d-grid d-md-block mt-4">
	<a href="<?php echo ABSOLUTURL; ?>dashboard/create" type="button" class="btn btn-primary">New articles</a>
</div>
<div class="col-md-6 table-responsive mt-3">
	<?php if (!empty($articles)): ?>
		<table class="table table-striped-columns table-hover">
			<thead>
				<tr>
					<th scope="col">#</th>
					<th scope="col">Title</th>
					<th scope="col">Cover</th>
					<th scope="col">Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php
				$index = 1;
				foreach ($articles as $article): ?>
					<tr>
						<th scope="row"><?php echo $index; ?></th>
						<td><?php echo $article['title']; ?></td>
						<!-- <td><?php //echo $article['author']; ?></td> -->
						<td>
							<a href="<?php echo ABSOLUTURL; ?>dashboard/edit/<?php echo $article['slug']; ?>" class="badge bg-warning text-decoration-none">Edit</a>
							<a href="<?php echo ABSOLUTURL; ?>dashboard/delete/<?php echo $article['slug']; ?>" class="badge bg-danger text-decoration-none" onclick="return confirm('Yakin?');">delete</a>
						</td>
					</tr>
				<?php $index++;
				endforeach; ?>
			</tbody>
		</table>
	<?php else: ?>
		<p class="alert alert-info text-center">
			You didn't have an articles yet.
			Please create a new article first.
		</p>
	<?php endif; ?>
</div>