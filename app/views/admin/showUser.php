<?php $user = (array) ($data['user']); ?>
<section class="container marketing">
	<hr class="featurette-divider">
	<div class="row featurette">
		<div class="col-md-7 order-md-4">
			<h2 class="featurette-heading fw-normal lh-1">Account Info</h2>
			<p class="lead">Email: <?php echo htmlspecialchars($user['email']); ?></p>
			<p class="lead">Username: <?php echo htmlspecialchars($user['username']); ?></p>
			<p class="lead">Status: <?php echo htmlspecialchars($user['is_deleted']); ?></p>
		</div>
		<div class="col-md-5 order-md-1">
			<img class="featurette-image img-fluid mx-auto" src="<?php echo htmlspecialchars($user['photo_profile']); ?>" alt="<?php echo htmlspecialchars($user['username']); ?>" width="300" height="300">
		</div>
	</div>
	<hr class="featurette-divider">
	<div class="d-flex gap-2 justify-content-center py-5">
		<a href="<?php echo ABSOLUTURL ?>admin/users" type="button" class="btn btn-primary d-inline-flex align-items-center">
			<i class="bi bi-arrow-left-short"></i>
			Go back
		</a>
	</div>
</section>

<?php unset($user, $data['user']); ?>