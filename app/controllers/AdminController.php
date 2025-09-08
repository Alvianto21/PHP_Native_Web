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

	// validasi tambah data
	public function store() {
		// cek method
		if (isset($_POST['submit'])) {
			header('LOCATION: /public/admin');
			exit;
		}

		// cek data
		$data = [
			'title' => $_POST['title'] ?? '',
			'slug' => $_POST['slug'] ?? '',
			'author' => $_POST['author'] ?? '',
			'body' => $_POST['body'] ?? ''
		];
		
		if ($this->checkData($data)) {
			header('Location: /public/admin/create');
			exit;
		}

		// clear data
		$this->model('Validator')->clearData($data);
		
		if ($this->model('Article')->create($data) > 0) {
			header('Location: /public/admin');
			exit;
		} else {
			header('Location: /public/admin');
			exit;
		}
	}

	// edit article
	public function edit($id) {
		$data['judul'] = 'Edit Artikel';
		$data['article'] = $this->model('Article')->find($id);

		$this->view('templates/header', $data);
		$this->view('dashboard/edit', $data);
		$this->view('templates/footer');
	}

	// update data
	public function update() {
		if (isset($_POST['submit'])) {
			header('LOCATION: /public/admin');
			exit;
		}

		// cek data
		$data = [
			'title' => $_POST['title'] ?? '',
			'slug' => $_POST['slug'] ?? '',
			'author' => $_POST['author'] ?? '',
			'body' => $_POST['body'] ?? '',
			'id' => $_POST['id'] ?? ''
		];
		
		if ($this->checkData($data)) {
			header('Location: /public/admin/edit/' . $data['id']);
			exit;
		}

		if ($this->model('Article')->update($data) > 0) {
			header('Location: /public/admin');
			exit;
		} else {
			header('Location: /public/admin');
			exit;
		}
	}

	// hapus artikel
	public function delete($id) {
		if ($this->model('Article')->delete($id)) {
			header('Location: /public/admin');
			exit;
		} else {
			header('Location: /public/admin');
			exit;
		}
	}

	// cek data
	public function checkData($data) {
		foreach ($data as $value) {
			if (empty($value)) {
				return true;
			} else {
				return false;
			}
		}
	}
}