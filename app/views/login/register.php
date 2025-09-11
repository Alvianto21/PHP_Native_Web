<div class="col-md-6">
	<form action="<?= ABSOLUTURL; ?>login/store" method="post">
	  <div class="mb-3 form-group">
		<label for="email" class="form-label">Email address</label>
		<input type="email" class="form-control" name="email" id="email" required autofocus>
	  <div class="mb-3 form-group">
		<label for="password" class="form-label">Password</label>
		<input type="password" class="form-control" name="password" id="password" required>
	  </div>
	  <button type="submit" class="btn btn-primary">Submit</button>
	</form>
</div>

<small class="mt-3">
	Have account?
	<a href="<?= ABSOLUTURL; ?>login" class="text-decoration-none ">login now</a>
</small>