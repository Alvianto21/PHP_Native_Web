<article>
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
				foreach ($data['articles'] as $article) {
					if ($article['id'] == 1) {
						continue;
					}
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