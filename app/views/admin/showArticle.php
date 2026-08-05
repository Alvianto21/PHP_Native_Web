<?php 
	$article = $data['article'];
?>
<article class="blog-post m-2">
	<h2 class="display-5 link-body-emphasis mb-1"><?php echo htmlspecialchars($article['title']) ?></h2>
	<h6 class="text-start blog-post-meta"><?php echo htmlspecialchars($article['author']) ?></h6>
	<div class="text-center m-4">
		<img src="<?php echo htmlspecialchars($article['photo_cover'] ?? '...', ENT_QUOTES); ?>" class="img-fluid img-thumbnail" alt="<?php echo htmlspecialchars($article['slug']); ?>" style="width: 50%; object-fit: cover">
	</div>
	<p class="fs-5 text-break"><?php echo htmlspecialchars($article['body']) ?></p>
	<a href="<?php echo ABSOLUTURL ?>admin/articles" type="button" class="btn btn-primary d-inline-flex align-items-center">
		<i class="bi bi-arrow-left-short"></i>
		Go back
	</a>
</article>
<?php unset($article); ?>