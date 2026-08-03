<?php $user = (array) ($data['user']); ?>
<section class="container marketing">
	<div class="row mt-3">
		<div class="col-md-5">
			<?php Flasher::showFlash(); ?>
		</div>
	</div>
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
		<a href="<?php echo ABSOLUTURL ?>profile/edit/<?php echo htmlspecialchars($user['username']); ?>" type="button" class="btn btn-warning rounded-pill px-3">
			<span class="class=" badge rounded-pill text-bg-warning"">Edit Profile</span>
		</a>
		<button href="/" type="button" class="btn btn-danger rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#account_remove">
			<span class="badge rounded-pill text-bg-danger">Delete Account</span>
		</button>
	</div>
	<hr class="featurette-divider">
</section>

<!-- Modal -->
<section class="modal fate" id="account_remove" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="account_remove_modal" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h1 class="modal-title fs-5">Delete Account?</h1>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<p>Are you sure want delete yor Account?</p>
				<p>Your articles will deleted too.</p>				
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
				<a type="button" class="btn btn-danger" href="<?php echo ABSOLUTURL; ?>/profile/delete">Delete anyway</a>
			</div>
		</div>
	</div>
</section>

<?php unset($user, $data['user']); ?>