<!DOCTYPE html>
<html lang="en" data-bs-theme="auto">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="description" content="Primitive blogpost">
	<title>Gornal Blog | <?php echo $data['judul'] ?? 'Primitive blogpost'; ?></title>

	<!-- Bootstrap CSS -->
	<link href="<?php echo BASEURL; ?>css/bootstrap/bootstrap.min.css" rel="stylesheet">

	<?php if (!empty($data['style'])) : ?>
		<!-- Custom styles for this template -->
		<link rel="stylesheet" href="<?php echo BASEURL; ?>css/templates/<?php echo $data['style']; ?>">
		<?php endif; ?>

		<!-- Mainstyles for this template -->
		<link rel="stylesheet" href="<?php echo BASEURL; ?>css/templates/app.css">

	<!-- Bootstrap icons -->
	<link rel="stylesheet" href="<?php echo BASEURL; ?>css/icons/bootstrap-icons.min.css">

	<!-- Bootstrap theme switcher -->
	<script src="<?php echo BASEURL; ?>js/theme/color-modes.js"></script>
</head>

<body data-bs-theme="light">
	<!-- Themes button -->
	<div class="dropdown position-fixed bottom-0 end-0 mb-3 me-3 bd-mode-toggle">
		<button class="btn btn-bd-primary py-2 dropdown-toggle d-flex align-items-center" id="bd-theme" type="button" aria-expanded="false" data-bs-toggle="dropdown" aria-label="Toggle theme (auto)">
			<i class="bi bi-circle-half"></i>
			<span class="visually-hidden" id="bd-theme-text">Toggle theme</span>
		</button>
		<ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="bd-theme-text">
			<li>
				<button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="light" aria-pressed="false">
					<i class="bi bi-sun-fill"></i>
					Light
					<i class="bi bi-check2"></i>
				</button>
			</li>
			<li>
				<button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="dark" aria-pressed="false">
					<i class="bi bi-moon-stars-fill"></i>
					Dark
					<i class="bi bi-check2"></i>
				</button>
			</li>
			<li>
				<button type="button" class="dropdown-item d-flex align-items-center active" data-bs-theme-value="auto" aria-pressed="true">
					<i class="bi bi-circle-half"></i>
					Auto
					<i class="bi bi-check2"></i>
				</button>
			</li>
		</ul>
	</div>

	<!-- Navbar -->
	<nav class="navbar navbar-expand-lg bg-body-tertiary px-5" data-bs-theme="dark" id="navbar">
		<div class="container-fluid">
			<a class="navbar-brand" href="/">Gornal Blog</a>
			<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
				<span class="navbar-toggler-icon"></span>
			</button>
			<div class="collapse navbar-collapse" id="navbarCollapse">
				<ul class="navbar-nav me-auto mb-2 mb-md-0">
					<li class="nav-item">
						<a class="nav-link <?php echo $data['judul'] === 'Halaman Home' ? 'active' : ''; ?>" aria-current="<?php echo $data['judul'] === 'Halaman Home' ? 'page' : ''; ?>" href="/">Home</a>
					</li>
					<?php if (isset($_SESSION['user_info'])) { ?>
						<li class="nav-item">
							<a class="nav-link <?php echo $data['judul'] === 'Halaman Dashboard' ? 'active' : ''; ?>" aria-current="<?php echo $data['judul'] === 'Halaman Dashboard' ? 'page' : ''; ?>" href="<?= ABSOLUTURL; ?>dashboard">Dashboard</a>
						</li>
					<?php } ?>
				</ul>
				<div class="mb-2 mb-md-0 navbar-nav">
					<div class="nav-item justify-content-end">
						<?php if (!isset($_SESSION['user_info'])) { ?>
							<a class="nav-link link-info <?php echo $data['judul'] === 'Halaman Login' ? 'active' : ''; ?>" aria-current="<?php echo $data['judul'] === 'Halaman Login' ? 'page' : ''; ?>" href="<?= ABSOLUTURL; ?>login">Login</a>
						<?php } else { ?>
							<a class="nav-link link-danger" href="<?= ABSOLUTURL; ?>login/logout">logout</a>
						<?php } ?>
					</div>
				</div>
			</div>
		</div>
	</nav>

	<!-- Main content -->
	<main class="container">