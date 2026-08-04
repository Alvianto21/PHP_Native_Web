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

		// cari hakaman saat ini
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

		// // cari hakaman saat ini
		$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
		$page = max(1, min($page, $maxPage));
		$startPage = ($page - 1) * $perPage;
		$data['pages'] = $page;

		// ambil data berserta offset
		$data['articles'] = $this->model('Article')->adminPaginator($perPage, $startPage);
		$data['judul'] = "Halaman articles admin";

		$this->view('templates/header', $data);
		$this->view('admin/articles', $data);
		$this->view('templates/footer');
	}
}
