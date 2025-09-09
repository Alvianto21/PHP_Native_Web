<section>
	<h2><?= $data['articles'][0]['title'] ?></h2>
	<h6><?= $data['articles'][0]['author'] ?></h6>
	<p><?= substr($data['articles'][0]['body'], 0, 50); ?></p>
	<a href="/public/home/detail/<?= $data['articles'][0]['id']; ?>">Read More</a>
</section>
<article>
	<?php
	foreach ($data['articles'] as $article) {
		if ($article['id'] == 1) {
			continue;
		}
		?>
		<h2><?= $article['title']; ?></h2>
		<h6><?= $article['author']; ?></h6>
		<p><?= substr($article['body'], 0, 60); ?></p>
		<a href="<?= ABSOLUTURL; ?>home/detail/<?= $article['id'] ?>">Read More</a>
		<?php
	}
	?>
</article>