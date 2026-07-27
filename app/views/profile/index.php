<?php $user = (array) ($data['user']); ?>
<section class="container marketing">
	<hr class="featurette-divider">
	<div class="row featurette">
		<div class="col-md-7 order-md-3">
			<h2 class="featurette-heading fw-normal lh-1">Account Info</h2>
			<p class="lead">Email: <?php echo htmlspecialchars($user['email']); ?></p>
			<p class="lead">Username: <?php echo htmlspecialchars($user['username']); ?></p>
		</div>
		<div class="col-md-5 order-md-1">
			<img class="featurette-image img-fluid mx-auto" src="<?php echo htmlspecialchars($user['photo_profile']); ?>" alt="<?php echo htmlspecialchars($user['username']); ?>" width="300" height="300">
		</div>
	</div>
	<div class="d-flex gap-2 justify-content-center py-5">
		<a href="http://" type="button" class="btn btn-warning rounded-pill px-3">
			<span class="class="badge rounded-pill text-bg-warning"">Edit Profile</span>
		</a>
		<a href="http://" type="button" class="btn btn-danger rounded-pill px-3">
			<span class="badge rounded-pill text-bg-danger">Delete</span>
		</a>
	</div>
	<hr class="featurette-divider">
</section>
<?php unset($user, $data['user']); ?>