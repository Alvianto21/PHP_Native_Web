<article aria-label="Article List">
	<?php 
		$articles = (array)($data['articles'] ?? []);
		$firstArticle = $articles[0] ?? null;
		
		if (!is_null($firstArticle)):
	?>
	<div class="container">
		<div class="card mt-4" style="width: 25rem; height: 10rem;">
			  <div class="card-body">
				<h5 class="card-title"><?php echo htmlspecialchars($firstArticle['title'] ?? '', ENT_QUOTES); ?></h5>
				<small class="text-muted"><?php echo htmlspecialchars($firstArticle['author'] ?? '', ENT_QUOTES); ?></small>
				<p class="card-text"><?php echo htmlspecialchars(substr($firstArticle['body'] ?? '', 0, 50), ENT_QUOTES); ?></p>
				<a href="<?php echo ABSOLUTURL; ?>home/detail/<?php echo htmlspecialchars($firstArticle['id'] ?? '', ENT_QUOTES); ?>" class="btn btn-primary">Read More</a>
			  </div>
		</div>
	</div>
	<?php else: ?>
		<div class="alert alert-info text-center mt-4">No articles available yet. </div>
	<?php endif; ?>
</article>
<article>
	<div class="container">
		<div class="row">
			<?php
				foreach (array_slice($data['articles'], 1) as $article) {
					// if ($article['id'] == 1) {
					// 	continue;
					// }
					?>
					<div class="col-md-3 mt-4">
						<div class="card mt-3" style="width: 18rem;">
						  	<div class="card-body">
								<h5 class="card-title"><?php echo $article['title']; ?></h5>
								<small class="text-muted"><?php echo $article['author']; ?></small>
								<p class="card-text"><?php echo substr($article['body'], 0, 60); ?></p>
								<a href="<?php echo ABSOLUTURL; ?>home/detail/<?php echo $article['id'] ?>" class="btn btn-primary">Read More</a>
						  	</div>
						</div>
					</div>
				<?php
				}
				?>
		</div>
	</div>
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