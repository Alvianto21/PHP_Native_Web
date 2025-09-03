<?php

class HomeController extends Controller{
	// home page
	public function index() {
		$data['articles'] = $this->model('Article')->all();
		$data['judul'] = 'Halaman Home';

		$this->view('templates/header', $data);
		$this->view('homes/home', $data);
		$this->view('templates/footer');
	}

	// detail article page
	public function detail($id) {
		$data['judul'] = 'Detail article';

		$this->view('templates/header', $data);
		$this->view('homes/detail');
		$this->view('templates/footer');
	}
}