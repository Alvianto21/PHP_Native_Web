<?php

class AdminController extends Controller {
	// articles table
	public function index() {
		$data['judul'] = 'Halaman Dashboard';
		$data['articles'] = $this->model('Article')->all();

		$this->view('templates/header', $data);
		$this->view('dashboard/list', $data);
		$this->view('templates/footer');
	}

	// create article
	public function create() {
		$data['judul'] = 'Buat Artikel Baru';

		$this->view('templates/header', $data);
		$this->view('dashboard/create');
		$this->view('templates/footer');
	}

	// edit article
	public function edit($id) {
		$data['judul'] = 'Edit Artikel';

		$this->view('templates/header', $data);
		$this->view('dashboard/edit');
		$this->view('templates/footer');
	}
}