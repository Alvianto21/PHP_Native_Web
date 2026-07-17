<article aria-label="Article List" class="m-4">
	<?php
	$articles = (array)($data['articles'] ?? []);
	$firstArticle = $articles[0] ?? null;

	if (!empty($articles) && !is_null($firstArticle)):
	?>
		<div class="container">
			<div class="row g-4">
				<div class="col-12">
					<div class="card h-100 shadow-sm overflow-hidden">
						<img src="<?php echo htmlspecialchars($firstArticle['photo_cover'] ?? '...', ENT_QUOTES); ?>" class="card-img-top img-fluid" alt="<?php echo htmlspecialchars($firstArticle['slug'] ?? '', ENT_QUOTES); ?>" style="height: 240px; object-fit: cover;">
						<div class="card-body d-flex flex-column">
							<h5 class="card-title"><?php echo htmlspecialchars($firstArticle['title'] ?? '', ENT_QUOTES); ?></h5>
							<small class="text-muted mb-2"><?php echo htmlspecialchars($firstArticle['author'] ?? '', ENT_QUOTES); ?></small>
							<p class="card-text flex-grow-1"><?php echo htmlspecialchars(substr($firstArticle['body'] ?? '', 0, 120), ENT_QUOTES); ?></p>
							<a href="<?php echo ABSOLUTURL; ?>home/detail/<?php echo htmlspecialchars($firstArticle['slug'] ?? '', ENT_QUOTES); ?>" class="btn btn-primary mt-auto">Read More</a>
						</div>
					</div>
				</div>
			</div>

			<?php if (count($articles) > 1) : ?>
				<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-4 mt-2">
					<?php foreach (array_slice($articles, 1) as $article) : ?>
						<div class="col">
							<div class="card h-100 shadow-sm overflow-hidden">
								<img src="<?php echo htmlspecialchars($article['photo_cover'] ?? '...', ENT_QUOTES); ?>" class="card-img-top img-fluid" alt="<?php echo htmlspecialchars($article['slug'] ?? '', ENT_QUOTES); ?>" style="height: 180px; object-fit: cover;">
								<div class="card-body d-flex flex-column">
									<h5 class="card-title"><?php echo htmlspecialchars($article['title'] ?? '', ENT_QUOTES); ?></h5>
									<small class="text-muted mb-2"><?php echo htmlspecialchars($article['author'] ?? '', ENT_QUOTES); ?></small>
									<p class="card-text flex-grow-1"><?php echo htmlspecialchars(substr($article['body'] ?? '', 0, 80), ENT_QUOTES); ?></p>
									<a href="<?php echo ABSOLUTURL; ?>home/detail/<?php echo htmlspecialchars($article['slug'] ?? '', ENT_QUOTES); ?>" class="btn btn-primary mt-auto">Read More</a>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	<?php else: ?>
		<div class="alert alert-info text-center mt-4">No articles available yet.</div>
	<?php endif; ?>
</article>

<div class="container">
	<div class="pagination mt-5">
		<nav aria-label="Page Navigation">
			<ul class="pagination">
				<?php if ($data['pages'] > 1) { ?>
					<li class="page-item">
						<a href="<?= ABSOLUTURL; ?>home/index?page<?= $data['pages'] - 1; ?>" class="page-link">&laquo; Prev</a>
					</li>
				<?php } ?>

				<?php for ($i = 1; $i <= $data['total']; $i++) { ?>
					<li class="page-item">
						<a href="<?= ABSOLUTURL; ?>home/index?page=<?= $i; ?>" class="page-link <?= $data['pages'] == $i ? 'active' : '' ?>"><?= $i; ?></a>
					</li>
				<?php } ?>

				<?php if ($data['pages'] < $data['total']) { ?>
					<li class="page-item">
						<a href="<?= ABSOLUTURL; ?>home/index?page=<?= $data['pages'] + 1; ?>" class="page-link">Next &raquo;</a>
					</li>
				<?php } ?>
			</ul>
		</nav>
	</div>
</div>