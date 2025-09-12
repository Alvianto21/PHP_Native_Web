<?php

class LoginController extends Controller {
	// halaman login
	public function index() {
		$data['judul'] = 'Halaman Login';

		$this->view('templates/header', $data);
		$this->view('login/index');
		$this->view('templates/footer');
	}

	// proses login
	public function authen() {
		// cek method
		if (isset($_POST['submit'])) {
			header('LOCATION: ' . ABSOLUTURL . 'login');
			exit;
		}

		$data = [
			'email' => $_POST['email'],
			'password' => $_POST['password']
		];

		// cek data
		if($this->checkData($data)) {
			header('LOCATION: ' . ABSOLUTURL . 'login');
			exit;
		}

		// clear data
		foreach ($data as $key) {
			$this->clearData($key);
		}

		// cari user
		$user = $this->model('Users')->find($data['email']);

		// validasi
		if($user && password_verify($data['password'], $user['password'])) {
			$_SESSION['user_info'] = [
				'user_id' => $user['id'],
				'user_email' => $user['email']
			];
			Flasher::setFlash('users berhasil', 'ditemukan. Selamat datang', 'info');
			header('LOCATION: ' . ABSOLUTURL . 'admin');
			exit;
		} else {
			Flasher::setFlash('users gagal', 'ditemukan. Coba lagi', 'danger');
			header('LOCATION: ' . ABSOLUTURL . 'login');
			exit;
		}
	}

	// halaman register
	public function register() {
		$data['judul'] = 'Halaman Register';

		$this->view('templates/header', $data);
		$this->view('login/register');
		$this->view('templates/footer');
	}

	// create user
	public function store() {
		// cek method
		if (isset($_POST['submit'])) {
			header('LOCATION: ' . ABSOLUTURL . 'login/register');
			exit;
		}

		// cek data
		$data = [
			'email' => $_POST['email'],
			'password' => $_POST['password']
		];

		if ($this->checkData($data)) {
			header('LOCATION: ' . ABSOLUTURL . 'login/register');
			exit;
		}

		// clear data
		foreach ($data as $key) {
			$this->clearData($key);
		}

		// hash password
		$data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

		if ($this->model('Users')->create($data) > 0) {
			Flasher::setFlash('user berhasil', 'ditambah', 'success');
			header('LOCATION: ' . ABSOLUTURL . 'login');
			exit;
		} else {
			Flasher::setFlash('user gagal', 'ditambah', 'success');
			header('LOCATION: ' . ABSOLUTURL . 'login');
			exit;
		}
	}

	// logout
	public function logout() {
		// cel login
		if (!isset($_SESSION['user_info'])) {
			header('LOCATION: ' . ABSOLUTURL . 'login');
			exit;
		}

		// hapus semua session
		$_SESSION = [];

		// hapus session
		session_destroy();
		header('LOCATION: ' . BASEURL);
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

	// bersihkan data
	public function clearData($data) {
		$data = trim($data);
		$data = stripslashes($data);
		$data = htmlspecialchars($data);
		return $data;
	}
}