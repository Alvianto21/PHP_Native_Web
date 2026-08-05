<?php $users = (array)($data['users'] ?? []); ?>

<div class="row mt-3">
	<div class="col-md-5">
		<?php Flasher::showFlash(); ?>
	</div>
</div>

<section>
	<div class="table-responsive-sm mt-5 text-center">
		<?php if (!empty($users)): ?>
			<table class="table table-sm table-striped-columns table-hover">
				<thead>
					<tr>
						<th scope="col">#</th>
						<th scope="col">Email</th>
						<th scope="col">Username</th>
						<th scope="col">Role</th>
						<th scope="col">Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php 
						$index = (int) 1;
						foreach($users as $user): 
						?>
							<tr>
								<th><?php echo $index; ?></th>
								<td><?php echo htmlspecialchars($user['email']); ?></td>
								<td><?php echo htmlspecialchars($user['username']); ?></td>
								<td><?php echo htmlspecialchars($user['role']); ?></td>
								<td>
									<a href="<?php echo ABSOLUTURL; ?>admin/showUser/<?php echo htmlspecialchars($user['username']); ?>" class="badge text-bg-primary text-decoration-none">Show</a>
									<a href="#" class="badge bg-warning text-decoration-none">Edit</a>
								</td>
							</tr>
						<?php 
							$index++; 
							endforeach; 
						?>
				</tbody>
			</table>
		<?php else: ?>
			<p class="alert alert-info text-center text-capitalize fx-3">
				Noting in here.
				<a href="<?php echo ABSOLUTURL; ?>admin/users" class="page-link">Go back</a>
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
						<a href="<?php echo ABSOLUTURL; ?>admin/user?page<?= $data['pages'] - 1; ?>" class="page-link">&laquo; Prev</a>
					</li>
				<?php } ?>

				<?php for ($i = 1; $i <= $data['total']; $i++) { ?>
					<li class="page-item">
						<a href="<?php echo ABSOLUTURL; ?>admin/users?page=<?= $i; ?>" class="page-link <?= $data['pages'] == $i ? 'active' : '' ?>"><?= $i; ?></a>
					</li>
				<?php } ?>

				<?php if ($data['pages'] < $data['total']) { ?>
					<li class="page-item">
						<a href="<?php echo ABSOLUTURL; ?>admin/users?page=<?= $data['pages'] + 1; ?>" class="page-link">Next &raquo;</a>
					</li>
				<?php } ?>
			</ul>
		</nav>
	</div>
</div>