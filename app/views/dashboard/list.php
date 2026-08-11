<?php $articles = (array)($data['articles'] ?? []); ?>
<div class="row mt-3">
	<div class="col-md-5">
		<?php Flasher::showFlash(); ?>
	</div>
</div>

<section>
	<div class="d-grid d-md-block mt-4">
		<a href="<?php echo ABSOLUTURL; ?>dashboard/create" type="button" class="btn btn-primary">New articles</a>
	</div>

	<div class="table-responsive-md mt-3">
		<?php if (!empty($articles)): ?>
			<table class="table table-striped-columns table-hover align-middle">
				<thead>
					<tr>
						<th scope="col">#</th>
						<th scope="col" style="width: 25%;">Cover</th>
						<th scope="col">Title</th>
						<th scope="col">Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php
					$index = (int) 1;
					foreach ($articles as $article): ?>
						<tr>
							<th scope="row"><?php echo $index; ?></th>
							<td><img src="<?php echo htmlspecialchars($article['photo_cover'] ?? '', ENT_QUOTES); ?>" alt="<?php echo htmlspecialchars($article['slug']); ?>" class="object-fit-md-contain"  height="100" width="100"></td>
							<td><?php echo $article['title']; ?></td>
							<td>
								<a href="<?php echo ABSOLUTURL; ?>dashboard/show/<?php echo htmlspecialchars($article['slug']); ?>" class="badge text-bg-primary text-decoration-none">Show</a>
								<a href="<?php echo ABSOLUTURL; ?>dashboard/edit/<?php echo htmlspecialchars($article['slug']); ?>" class="badge bg-warning text-decoration-none">Edit</a>
								<a href="<?php echo ABSOLUTURL; ?>dashboard/delete/<?php echo htmlspecialchars($article['slug']); ?>" class="badge bg-danger text-decoration-none" onclick="return confirm('Yakin?');">delete</a>
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
</section>

<div class="container">
	<div class="pagination mt-5">
		<nav aria-label="Page Navigation" class="blog-pagination">
			<ul class="pagination">
				<?php if ($data['pages'] > 1) { ?>
					<li class="page-item">
						<a href="<?php echo ABSOLUTURL; ?>dashboard?page<?= $data['pages'] - 1; ?>" class="page-link">&laquo; Prev</a>
					</li>
				<?php } ?>

				<?php for ($i = 1; $i <= $data['total']; $i++) { ?>
					<li class="page-item">
						<a href="<?php echo ABSOLUTURL; ?>dashboard?page=<?= $i; ?>" class="page-link <?= $data['pages'] == $i ? 'active' : '' ?>"><?= $i; ?></a>
					</li>
				<?php } ?>

				<?php if ($data['pages'] < $data['total']) { ?>
					<li class="page-item">
						<a href="<?php echo ABSOLUTURL; ?>dashboard?page=<?= $data['pages'] + 1; ?>" class="page-link">Next &raquo;</a>
					</li>
				<?php } ?>
			</ul>
		</nav>
	</div>
</div>