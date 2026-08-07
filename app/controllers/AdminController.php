<?php

class AdminController extends Controller
{
	// Users page
	public function users()
	{
		// check session and permissions
		if (!isset($_SESSION['user_info'])) {
			Flasher::setFlash('Mohon maaf, ', 'aksess halaman ini ditolak!', 'danger');
			header('LOCATION: ' . ABSOLUTURL . 'login');
			exit;
		}

		if ($_SESSION['user_info']['user_role'] !== 'admin') {
			Flasher::setFlash('Mohon maaf, ', 'aksess halaman ini ditolak!', 'danger');
			header('LOCATION: ' . ABSOLUTURL . 'dashboard');
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
	public function showUser(string $username)
	{
		require_once __DIR__ . '/../request/UploadImage.php';

		// check session and permissions
		if (!isset($_SESSION['user_info'])) {
			Flasher::setFlash('Mohon maaf, ', 'aksess halaman ini ditolak!', 'danger');
			header('LOCATION: ' . ABSOLUTURL . 'login');
			exit;
		}

		if ($_SESSION['user_info']['user_role'] !== 'admin') {
			Flasher::setFlash('Mohon maaf, ', 'aksess halaman ini ditolak!', 'danger');
			header('LOCATION: ' . ABSOLUTURL . 'dashboard');
			exit;
		}

		$uploader = new UploadImage();

		$data['judul'] = "Halaman Info User";
		$data['style'] = "profile.css";
		$user = $this->model('Users')->findUserAdmin($username);

		if ($user) {
			$data['user'] = $this->model('Users')->findUserAdmin($username);
		} else {
			header('Location: ' . ABSOLUTURL . 'admin/users');
			exit;
		}

		if (!empty($data['user']['photo_profile'])) {
			$data['user']['photo_profile'] = $uploader->show($data['user']['photo_profile']);
		}

		$data['user']['is_deleted'] = $data['user']['is_deleted'] === 0 ? "Available" : "Deleted";

		$this->view('templates/header', $data);
		$this->view('admin/showUser', $data);
		$this->view('templates/footer');
	}

	// Edit user page
	public function editUser(string $username)
	{
		// check session and permissions
		if (!isset($_SESSION['user_info'])) {
			Flasher::setFlash('Mohon maaf, ', 'aksess halaman ini ditolak!', 'danger');
			header('LOCATION: ' . ABSOLUTURL . 'login');
			exit;
		}

		if ($_SESSION['user_info']['user_role'] !== 'admin') {
			Flasher::setFlash('Mohon maaf, ', 'aksess halaman ini ditolak!', 'danger');
			header('LOCATION: ' . ABSOLUTURL . 'dashboard');
			exit;
		}

		$data['judul'] = "Halaman Edit User";
		$data['style'] = 'sign-up.css';
		$user = $this->model('Users')->findUserAdmin($username);

		if ($user) {
			$data['user'] = $this->model('Users')->findUserAdmin($username);
		} else {
			header('Location: ' . ABSOLUTURL . 'admin/users');
			exit;
		}

		$this->view('templates/header', $data);
		$this->view('admin/editUser', $data);
		$this->view('templates/footer');
	}

	// Generator temporary password
	public function tempPasswordGenerator(int $length = 16)
	{
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			http_response_code(405);
			echo json_encode(['error' => 'Method Not Allowed']);
			return;
		}

		if ($_SESSION['user_info']['user_role'] !== 'admin') {
			http_response_code(403);
			echo json_encode(['error' => "You did'n have requirement"]);
			exit;
		}

		$characters = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%^&*';
		$max = strlen($characters) - 1;
		$password = '';

		for ($i = 0; $i < $length; $i++) {
			$password .= $characters[random_int(0, $max)];
		}

		header('Content-Type: application/json');
		echo json_encode(['regenerate_password' => $password]);
		exit;
	}

	// Update user
	public function userUpdate(string $username) {
		require_once __DIR__ . '/../request/Validator.php';
		require_once __DIR__ . '/../request/UploadImage.php';

		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			header('LOCATION: ' . ABSOLUTURL . 'admin/users');
			return;
		}

		// check session and permissions
		if (!isset($_SESSION['user_info'])) {
			Flasher::setFlash('Mohon maaf, ', 'aksess halaman ini ditolak!', 'danger');
			header('LOCATION: ' . ABSOLUTURL . 'login');
			exit;
		}

		if ($_SESSION['user_info']['user_role'] !== 'admin') {
			Flasher::setFlash('Mohon maaf, ', 'aksess halaman ini ditolak!', 'danger');
			header('LOCATION: ' . ABSOLUTURL . 'dashboard');
			exit;
		}

		$_SESSION['errors'] = [];
		$_SESSION['old_input'] = [];

		$validator = new Validator();
		$uploader = new UploadImage();

		$postData = $_POST;
		$fileData = $_FILES;
		$dataKey = [];
		$dataUpdate = [];

		$user = $this->model('Users')->findUsernameAdmin($username);
		$data = [
			'email' => $validator->clearData($postData['email'] ?? ''),
			'username' => $validator->clearData($postData['username'] ?? ''),
			'photo_profile' => $fileData['photo_profile'] ?? '',
			'photo_path' => $postData['photo_path'] ?? '',
			'password' => $validator->clearData($postData['password'] ?? ''),
			'password_confirm' => $validator->clearData($postData['password_confirm']),
			'role' => $validator->clearData($postData['role'] ?? ''),
			'is_deleted' => $validator->clearData($postData['is_deleted'] ?? '')
		];
		$rules = [
			'email' => [
				"required" => true,
				"email" => true,
				"regex" => "/^[A-Za-z0-9._]+@[A-Za-z0-9._]+$/"
			],
			'username' => [
				"required" => true,
				"min" => 5,
				"max" => 25,
				"regex" => "/^[A-Za-z0-9]+$/"
			],
			'photo_profile' => [
				"size" => 500000, // 500 Kb
				"img" => true
			],
			'photo_path' => [
				"signature" => true,
				"required_if" => "photo_profile"
			],
			'role' => [
				'required' => true,
				'role_user' => ['user', 'admin']
			],
			'is_deleted' => [
				'required' => true,
				'boolean' => true
			]
		];

		/**
		 * Password validation is added only when either password field has a non-empty value.
		 * @var bool
		 */
		$shouldUpdatePassword = trim((string) $data['password']) !== '' || trim((string) $data['password_confirm']) !== '';

		if ($shouldUpdatePassword) {
			$rules['password'] = [
				"min" => 10,
				"max" => 45
			];
			$rules['password_confirm'] = [
				"required_if" => 'password',
				"min" => 10,
				"max" => 45,
				"match" => "password"
			];
		}

		// Validate form
		if ($validator->validate($data, $rules)) {
			// Verify img sign URL if exist
			if (!empty($data['photo_path'])) {
				$checkUrl = $validator->validateSignUrl($data['photo_path']);

				if ($checkUrl !== true) {
					http_response_code(403);
					echo $checkUrl;
					exit($checkUrl);
				}
			}

			// If photo_profile updated, upload new photo profile and destroy old photo
			if ($data['photo_profile']['error'] === UPLOAD_ERR_OK) {
				$data['photo_profile'] = $uploader->update($data['photo_profile'], $postData['old_photo_profile'], 'profiles');
			} elseif ($data['photo_profile']['error'] === UPLOAD_ERR_NO_FILE) {
				$data['photo_profile'] = $postData['old_photo_profile'];
			}

			// If password updated, hash it 
			if ($shouldUpdatePassword) {
				$data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
			}

			// Separate key and value for update
			foreach ($data as $updateData => $updateValue) {
				if ($updateData === 'password_confirm' || $updateData === 'photo_path') {
					continue;
				} elseif ($updateData === 'password' && $updateValue === '') {
					continue;
				} elseif ($updateData === 'photo_profile' && is_array($updateValue) && ($updateValue['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
					$updateValue = $postData['old_photo_profile'] ?? $user[$updateData] ?? '';
				} elseif (array_key_exists($updateData, $user) && $user[$updateData] != $updateValue) {
					$dataKey[] = "{$updateData} = :{$updateData}";
					$dataUpdate[$updateData] = $updateValue;
				}
			}

			if ($this->model('Users')->update($dataUpdate, $dataKey, $username) > 0) {
				Flasher::setFlash('Profil ' . $username . ' berhasil', 'diperbarui', 'success');
				header('Location: ' . ABSOLUTURL . 'admin/users');
				exit;
			};
		} else {
			$_SESSION['errors'] = $validator->errors();
			$_SESSION['old_input'] = [
				'email' => $data['email'],
				'username' => $data['username'],
				'role' => $data['role'],
				'is_deleted' => $data['is_deleted']
			];
			header('Location: ' . ABSOLUTURL . 'admin/editUser/' . $username);
			exit;
		}		
	}


	// Articles page
	public function articles()
	{
		// check session and permissions
		if (!isset($_SESSION['user_info'])) {
			Flasher::setFlash('Mohon maaf, ', 'aksess halaman ini ditolak!', 'danger');
			header('LOCATION: ' . ABSOLUTURL . 'login');
			exit;
		}

		if ($_SESSION['user_info']['user_role'] !== 'admin') {
			Flasher::setFlash('Mohon maaf, ', 'aksess halaman ini ditolak!', 'danger');
			header('LOCATION: ' . ABSOLUTURL . 'dashboard');
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

		foreach ($data['articles'] as &$article) {
			$article['is_deleted'] = $article['is_deleted'] === 0 ? 'Available' : 'Deleted';
		}

		$this->view('templates/header', $data);
		$this->view('admin/articles', $data);
		$this->view('templates/footer');
	}

	// Show article
	public function showArticle(string $slug)
	{
		require_once __DIR__ . '/../request/UploadImage.php';

		// check session and permissions
		if (!isset($_SESSION['user_info'])) {
			Flasher::setFlash('Mohon maaf, ', 'aksess halaman ini ditolak!', 'danger');
			header('LOCATION: ' . ABSOLUTURL . 'login');
			exit;
		}

		$uploader = new UploadImage();

		$data['judul'] = "Halaman Info Article";
		$data['style'] = "blog.css";
		$article = $this->model('Article')->findArticleAdmin($slug);

		if ($article) {
			$data['article'] = $article;
		} else {
			header('Location: ' . ABSOLUTURL . 'admin/articles');
			exit;
		}

		if (!empty($data['article']['photo_cover'])) {
			$data['article']['photo_cover'] = $uploader->show($data['article']['photo_cover']);
		}

		$this->view('templates/header', $data);
		$this->view('admin/showArticle', $data);
		$this->view('templates/footer');
	}
}
