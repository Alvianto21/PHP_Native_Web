<?php

class LoginController extends Controller
{
	// halaman login
	public function index()
	{
		if (isset($_SESSION['user_info'])) {
			header('LOCATION: ' . ABSOLUTURL . 'dashboard');
			exit;
		}

		$data['judul'] = 'Halaman Login';

		$this->view('templates/header', $data);
		$this->view('login/index');
		$this->view('templates/footer');
	}

	// proses login
	public function authen()
	{
		require_once __DIR__ . '/../request/Validator.php';

		// Cek method
		if ($_SERVER['REQUEST_METHOD'] !== "POST") {
			header('LOCATION: ' . ABSOLUTURL . 'login');
			exit;
		}

		$validator = new Validator();
		$_SESSION['errors'] = [];
		$_SESSION['old_input'] = [];
		$data = [
			'email' => $validator->clearData($_POST['email'] ?? ''),
			'password' => $validator->clearData($_POST['password'] ?? '')
		];

		$rules = [
			"email" => [
				"required" => true,
				"email" => true,
				"regex" => "/^[A-Za-z0-9._]+@[A-Za-z0-9._]+$/"
			],
			"password" => [
				"required" => true,
				"min" => 10,
				"max" => 45
			]
		];

		// Cek data
		if ($validator->checkData($data)) {
			header('LOCATION: ' . ABSOLUTURL . 'login');
			exit;
		}

		// Validate data
		if ($validator->validate($data, $rules)) {
			// Find user
			$user = $this->model('Users')->findEmail($data['email']);

			// Check users if exist
			if ($user && password_verify($data['password'], $user['password'])) {
				session_regenerate_id(true);
				$_SESSION['user_info'] = [
					'user_id' => $user['id'],
					'user_role' => $user['role']
				];
				Flasher::setFlash('users berhasil', 'ditemukan. Selamat datang, ' . $user['username'], 'info');
				header('LOCATION: ' . ABSOLUTURL . 'dashboard');
				exit;
			} else {
				Flasher::setFlash('users gagal', 'ditemukan. Coba lagi', 'danger');
				header('LOCATION: ' . ABSOLUTURL . 'login');
				exit;
			}
		} else {
			$_SESSION['errors'] = $validator->errors();
			$_SESSION['old_input'] = $data['email'];
			header('LOCATION: ' . ABSOLUTURL . 'login');
			exit;
		}
	}

	// halaman register
	public function register()
	{
		$data['judul'] = 'Halaman Register';

		$this->view('templates/header', $data);
		$this->view('login/register');
		$this->view('templates/footer');
	}

	// create user
	public function store()
	{
		require_once __DIR__ . '/../request/Validator.php';
		require_once __DIR__ . '/../request/UploadImage.php';

		// cek method
		if ($_SERVER['REQUEST_METHOD'] !== "POST") {
			header('LOCATION: ' . ABSOLUTURL . 'login/register');
			exit;
		}

		$_SESSION['errors'] = [];
		$_SESSION['old_input'] = [];

		$validator = new Validator();
		$uploader = new UploadImage();

		$secret = getenv("APP_KEY");
		$postData = $_POST;
		$fileData = $_FILES;

		// cek data
		$data = [
			'email' => $validator->clearData($postData['email'] ?? ''),
			'username' => $validator->clearData($postData['username'] ?? ''),
			'photo_profile' => $fileData['photo_profile'] ?? '',
			'photo_path' => $postData['photo_path'] ?? '',
			'password' => $validator->clearData($postData['password'] ?? ''),
			'password_confirm' => $postData['password_confirm'] ?? ''
		];

		$rules = [
			"email" => [
				"required" => true,
				"email" => true,
				"regex" => "/^[A-Za-z0-9._]+@[A-Za-z0-9._]+$/"
			],
			"username" => [
				"required" => true,
				"min" => 5,
				"max" => 25,
				"regex" => "/^[A-Za-z0-9]+$/"
			],
			"photo_profile" => [
				"size" => 500000, // 500 Kb
				"img" => true
			],
			"photo_path" => [
				"signature" => true,
				"required_if" => "photo_profile"
			],
			"password" => [
				"required" => true,
				"min" => 10,
				"max" => 45
			],
			"password_confirm" => [
				"required" => true,
				"min" => 10,
				"max" => 45,
				"match" => "password"
			]
		];

		// Validate input
		if ($validator->validate($data, $rules)) {
			// Verify img sign url if exist
			if (!empty($data['photo_path'])) {
				$checkUrl = $validator->validateSignUrl($data['photo_path']);

				if ($checkUrl !== true) {
					http_response_code(403);
					echo $checkUrl;
					exit($checkUrl);
				}
			}

			// File handling
			$data['photo_profile'] = $uploader->store($data['photo_profile'], "profiles");

			// hash password
			$data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

			if ($this->model('Users')->create($data) > 0) {
				Flasher::setFlash('user berhasil', 'ditambah', 'success');
				unset($_SESSION['errors'], $_SESSION['old_input']);
				header('LOCATION: ' . ABSOLUTURL . 'login');
				exit;
			} else {
				Flasher::setFlash('user gagal', 'ditambah', 'warning');
				header('LOCATION: ' . ABSOLUTURL . 'login');
				exit;
			}
		} else {
			$_SESSION['errors'] = $validator->errors();
			$_SESSION['old_input'] = [
				'email' => $data['email'],
				'username' => $data['username']
			];
			header('LOCATION: ' . ABSOLUTURL . 'login/register');
			exit;
		}
	}

	// logout
	public function logout()
	{
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
		exit;
	}
}
