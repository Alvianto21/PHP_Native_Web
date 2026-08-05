<?php

class AdminController extends Controller
{
	// Users page
	public function users()
	{
		// check session and permissions
		if (isset($_SESSION['user_info']) && $_SESSION['user_info']['user_role'] !== 'admin') {
			Flasher::setFlash('Mohon maaf, ', 'aksess halaman ini ditolak!', 'danger');
			header('LOCATION: ' . ABSOLUTURL . 'login');
			exit;
		}

		// cari halaman saat ini
		$perPage = 10;
		$startPage = isset($_GET['page']) ? (int) $_GET['page'] + $perPage : (int) 0;
		$data['users'] = $this->model('Users')->showAll($perPage, $startPage);

		// Find max Page	
		$maxPage = isset($data['users'][0]['total'])
			? ceil((int) $data['users'][0]['total'] / $perPage)
			: 1;

		// Set pagination
		$data['total'] = $maxPage;
		$page = max(1, min($startPage, $maxPage));
		$data['pages'] = $page;

		$data['judul'] = 'Halaman users admin';

		$this->view('templates/header', $data);
		$this->view('admin/users', $data);
		$this->view('templates/footer');
	}

	// Show user
	public function showUser(string $username) {
		require_once __DIR__ . '/../request/UploadImage.php';

		// check session and permissions
		if (isset($_SESSION['user_info']) && $_SESSION['user_info']['user_role'] !== 'admin') {
			Flasher::setFlash('Mohon maaf, ', 'aksess halaman ini ditolak!', 'danger');
			header('LOCATION: ' . ABSOLUTURL . 'login');
			exit;
		}

		$uploader = new UploadImage();

		$data['judul'] = "Halaman Info User";
		$data['style'] = "profile.css";
		$data['user'] = $this->model('Users')->findUserAdmin($username);

		if (!empty($data['user']['photo_profile'])) {
			$data['user']['photo_profile'] = $uploader->show($data['user']['photo_profile']);
		}

		$data['user']['is_deleted'] = $data['user']['is_deleted'] === 0 ? "Available" : "Deleted";

		$this->view('templates/header', $data);
		$this->view('admin/showUser', $data);
		$this->view('templates/footer');
	}

	// Articles page
	public function articles() {
		// check session and permissions
		if (isset($_SESSION['user_info']) && $_SESSION['user_info']['user_role'] !== 'admin') {
			Flasher::setFlash('Mohon maaf, ', 'aksess halaman ini ditolak!', 'danger');
			header('LOCATION: ' . ABSOLUTURL . 'login');
			exit;
		}

		// Set pagination
		$perPage = 5;
		$totalPage = $this->model('Article')->count();
		$maxPage = ceil($totalPage / $perPage);
		$data['total'] = $maxPage;

		// // cari halaman saat ini
		$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
		$page = max(1, min($page, $maxPage));
		$startPage = ($page - 1) * $perPage;
		$data['pages'] = $page;

		// ambil data berserta offset
		$data['articles'] = $this->model('Article')->adminPaginator($perPage, $startPage);
		$data['judul'] = "Halaman articles admin";

		foreach($data['articles'] as &$article) {
			$article['is_deleted'] = $article['is_deleted'] === 0 ? 'Available' : 'Deleted';
		}

		$this->view('templates/header', $data);
		$this->view('admin/articles', $data);
		$this->view('templates/footer');
	}

	// Show article
	public function showArticle(string $slug) {
		require_once __DIR__ . '/../request/UploadImage.php';

		// check session and permissions
		if (isset($_SESSION['user_info']) && $_SESSION['user_info']['user_role'] !== 'admin') {
			Flasher::setFlash('Mohon maaf, ', 'aksess halaman ini ditolak!', 'danger');
			header('LOCATION: ' . ABSOLUTURL . 'login');
			exit;
		}

		$uploader = new UploadImage();

		$data['judul'] = "Halaman Info Article";
		$data['style'] = "blog.css";
		$data['article'] = $this->model('Article')->findArticleAdmin($slug);

		if (!empty($data['article']['photo_cover'])) {
			$data['article']['photo_cover'] = $uploader->show($data['article']['photo_cover']);
		}

		$this->view('templates/header', $data);
		$this->view('admin/showArticle', $data);
		$this->view('templates/footer');
	}
}
