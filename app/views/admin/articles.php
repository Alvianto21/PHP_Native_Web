<?php $articles = (array)($data['articles'] ?? []) ?>

<div class="row mt-3">
	<div class="col-md-5">
		<?php Flasher::showFlash(); ?>
	</div>
</div>

<section>
	<div class="table-responsive-lg mt-5 text-center">
		<?php if(!empty($articles)): ?>
			<table class="table table-striped-columns table-hover">
				<thead>
					<tr>
						<th scope="col">#</th>
						<th scope="col">Title</th>
						<th scope="col">Author</th>
						<th scope="col">Body</th>
						<th scope="col">Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php 
						$index = 1;
						foreach($articles as $article): 
					?>
						<tr>
							<th><?php echo $index; ?></th>
							<td><?php echo htmlspecialchars($article['title']); ?></td>
							<td><?php echo htmlspecialchars($article['author']); ?></td>
							<td><?php echo htmlspecialchars(substr($article['body'], 0, 60)) ?></td>
							<td>
								<a href="#" class="badge bg-warning text-decoration-none">Edit</a>
								<a href="#" class="badge bg-danger text-decoration-none">Delete</a>
							</td>
						</tr>
					<?php 
						$index++;
						endforeach;
					?>
				</tbody>
			</table>
		<?php else: ?>
			<p class="alert alert-info text-center text-capitalize">
				Noting in here.
				<a href="<?php echo ABSOLUTURL; ?>admin/articles" class="page-link">Go back</a> 
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
						<a href="<?php echo ABSOLUTURL; ?>admin/articles?page<?php echo $data['pages'] - 1; ?>" class="page-link">&laquo; Prev</a>
					</li>
				<?php } ?>

				<?php for ($i = 1; $i <= $data['total']; $i++) { ?>
					<li class="page-item">
						<a href="<?php echo ABSOLUTURL; ?>admin/articles?page=<?php echo $i; ?>" class="page-link <?= $data['pages'] == $i ? 'active' : '' ?>"><?= $i; ?></a>
					</li>
				<?php } ?>

				<?php if ($data['pages'] < $data['total']) { ?>
					<li class="page-item">
						<a href="<?php echo ABSOLUTURL; ?>dashboard?page=<?php echo $data['pages'] + 1; ?>" class="page-link">Next &raquo;</a>
					</li>
				<?php } ?>
			</ul>
		</nav>
	</div>
</div>