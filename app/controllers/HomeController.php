<?php

class HomeController extends Controller{
	// home page
	public function index() {
		// // set pagination
		$perPage = 5;
		$totalPage = $this->model('Article')->count();
		$maxPage = ceil($totalPage / $perPage);
		$data['total'] = $maxPage;

		// // cari hakaman saat ini
		$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
		$page = max(1, min($page, $maxPage));
		$startPage = ($page - 1) * $perPage;
		$data['pages'] = $page;

		// ambil data berserta offset
		$data['articles'] = $this->model('Article')->paginator($perPage, $startPage);
		$data['judul'] = 'Halaman Home';

		$this->view('templates/header', $data);
		$this->view('homes/home', $data);
		$this->view('templates/footer');
	}

	// detail article page
	public function detail($id) {
		$data['judul'] = 'Detail article';
		$data['article'] = $this->model('Article')->find($id);

		$this->view('templates/header', $data);
		$this->view('homes/detail', $data);
		$this->view('templates/footer');
	}
}