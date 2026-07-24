<?php 
	$article = $data['article'];
?>
<article class="blog-post m-2">
	<h2 class="display-5 link-body-emphasis mb-1"><?php echo htmlspecialchars($article['title']) ?></h2>
	<div class="text-center m-4">
		<img src="<?php echo htmlspecialchars($article['photo_cover'] ?? '...', ENT_QUOTES); ?>" class="img-fluid img-thumbnail" alt="<?php echo htmlspecialchars($article['slug']); ?>" style="width: 50%; object-fit: cover">
	</div>
	<p class="fs-5 text-break"><?php echo htmlspecialchars($article['body']) ?></p>
</article>
<a href="<?php echo ABSOLUTURL; ?>/Dashboard">Back To Dashboard</a>
<?php unset($article); ?>