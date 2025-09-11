<article aria-label="Article List">
	<div class="container">
		<div class="card mt-4" style="width: 25rem; height: 10rem;">
			  <div class="card-body">
				<h5 class="card-title"><?= $data['articles'][0]['title'] ?></h5>
				<small class="text-muted"><?= $data['articles'][0]['author'] ?></small>
				<p class="card-text"><?= substr($data['articles'][0]['body'], 0, 50); ?></p>
				<a href="<?= ABSOLUTURL; ?>home/detail/<?= $data['articles'][0]['id']; ?>" class="btn btn-primary">Read More</a>
			  </div>
		</div>
	</div>
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
								<h5 class="card-title"><?= $article['title']; ?></h5>
								<small class="text-muted"><?= $article['author']; ?></small>
								<p class="card-text"><?= substr($article['body'], 0, 60); ?></p>
								<a href="<?= ABSOLUTURL; ?>home/detail/<?= $article['id'] ?>" class="btn btn-primary">Read More</a>
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
						<a href="<?= ABSOLUTURL; ?>home/index?page=<?= $i; ?>" class="page-link"><?= $i; ?></a>
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