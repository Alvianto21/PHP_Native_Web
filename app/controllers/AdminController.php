<?php

class AdminController extends Controller
{

	/**
	 * User identifier.
	 * @var string User IP address.
	 */
	protected $userIdentifier;

	public function __construct()
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

		$clientIP = !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
		$this->userIdentifier = $clientIP;
	}

	// Users page
	public function users()
	{
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
	public function userUpdate(string $username)
	{
		require_once __DIR__ . '/../request/Validator.php';
		require_once __DIR__ . '/../request/UploadImage.php';

		$limiter = new RateLimiter(30, 60, 100, 3600);

		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			header('LOCATION: ' . ABSOLUTURL . 'admin/users');
			return;
		}

		if (!filter_var($this->userIdentifier, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)) {
			exit("Error: Invalid IP address.");
		}

		if (!$limiter->limiter($this->userIdentifier)) {
			$retryAt = $limiter->attemptRetryAfter();

			http_response_code(429);
			header('Content-Type: application/json');
			header("Retry-After: {$retryAt}");

			echo json_encode([
				'error' => 'Too many request.',
				'retry_after' => $retryAt
			]);
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

			$isDeletingUser = isset($data['is_deleted']) && $data['is_deleted'] === '1';
			$wasUserDeleted = isset($user['is_deleted']) && (string) $user['is_deleted'] === '1';

			// If admin deletes the user, clear the profile photo path in the update data.
			if ($isDeletingUser) {
				$data['photo_profile'] = null;
			} elseif ($data['photo_profile']['error'] === UPLOAD_ERR_OK) {
				$data['photo_profile'] = $uploader->update($data['photo_profile'], (string) $postData['old_photo_profile'], 'profiles');
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
				if ($isDeletingUser && !$wasUserDeleted) {
					$oldProfile = $postData['old_photo_profile'] ?? $user['photo_profile'] ?? '';
					$articleModel = $this->model('Article');
					$coverPhotos = $articleModel->getCoverPhotosByUser((int) $user['id']);
					$articleModel->deleteAll((int) $user['id']);

					if (!empty($oldProfile)) {
						$uploader->delete($oldProfile);
					}

					foreach ($coverPhotos as $photo) {
						if (!empty($photo['photo_cover'])) {
							$uploader->delete($photo['photo_cover']);
						}
					}
				}

				Flasher::setFlash('Profil ' . $username . ' berhasil', 'diperbarui', 'success');
				header('Location: ' . ABSOLUTURL . 'admin/users');
				exit;
			} else {
				Flasher::setFlash('Profil ' . $username . ' gagal', 'diperbarui', 'danger');
				header('Location: ' . ABSOLUTURL . 'admin/users');
				exit;
			}
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

	// Edit article page
	public function editArticle(string $slug)
	{
		$data['judul'] = 'Halaman Edit article';
		$data['style'] = "article.css";
		$article = $this->model('Article')->findArticlesUsers($slug);

		if ($article) {
			$data['article'] = $article;
		} else {
			header('Location: ' . ABSOLUTURL . 'admin/articles');
			exit;
		}

		$this->view('templates/header', $data);
		$this->view('admin/editArticle', $data);
		$this->view('templates/footer');
	}

	// Update article
	public function articleUpdate(string $slug)
	{
		require_once __DIR__ . '/../request/Validator.php';
		require_once __DIR__ . '/../request/UploadImage.php';

		$limiter = new RateLimiter(30, 60, 100, 3600);

		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			header('LOCATION: ' . ABSOLUTURL . 'admin/users');
			return;
		}

		if (!filter_var($this->userIdentifier, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)) {
			exit("Error: Invalid IP address.");
		}

		if (!$limiter->limiter($this->userIdentifier)) {
			$retryAt = $limiter->attemptRetryAfter();

			http_response_code(429);
			header('Content-Type: application/json');
			header("Retry-After: {$retryAt}");

			echo json_encode([
				'error' => 'Too many request.',
				'retry_after' => $retryAt
			]);
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

		$article = $this->model('Article')->findArticlesUsers($slug);
		$data = [
			'title' => $validator->clearData($postData['title'] ?? ''),
			'slug' => '',
			'photo_cover' => $fileData['photo_cover'] ?? '',
			'photo_path' => $postData['photo_path'] ?? '',
			'is_deleted' => $validator->clearData($postData['is_deleted'] ?? ''),
			'body' => $validator->clearData($postData['body'] ?? '')
		];
		$rules = [
			'title' => [
				'required' => true,
				'min' => 10,
				'max' => 200
			],
			'photo_cover' => [
				"size" => 500000, // 500 Kb
				"img" => true
			],
			'photo_path' => [
				'required_if' => 'photo_cover',
				'signature' => true,
			],
			'is_deleted' => [
				'required' => true,
				'boolean' => true
			],
			'body' => [
				'required' => true,
				'min' => 200
			]
		];

		// Validate input
		if ($validator->validate($data, $rules)) {
			// If title is new, generate new slug
			if ($article['title'] !== $data['title']) {
				// Lowercase and remove apostrophes 
				$newSlug = strtolower(preg_replace("~[‘’`']+~", '', $data['title']));

				// Replace non-alphanumeric characters with spaces
				$newSlug = preg_replace("~[^a-z0-9]+~", ' ', $newSlug);

				// Replace spaces with hyphens and add it to data
				$baseSlug = preg_replace('~[ ]+~', '-', trim($newSlug));
				$newSlug = $baseSlug;
				$slugCount = 1;

				while ($this->model('Article')->findSlug($newSlug)) {
					$newSlug = $baseSlug . '-' . $slugCount;
					$slugCount++;
				}
			} else {
				$data['slug'] = $article['slug'];
			}

			if (!empty($data['photo_path'])) {
				$checkUrl = $validator->validateSignUrl($data['photo_path']);

				if ($checkUrl !== true) {
					http_response_code(403);
					echo $checkUrl;
					exit($checkUrl);
				}
			}

			$isDeletingArticle = isset($data['is_deleted']) && $data['is_deleted'] === '1';
			$wasArticleDeleted = isset($article['is_deleted']) && (string) $article['is_deleted'] === '1';

			// When admin deletes the article, clear photo_cover in the update payload.
			if ($isDeletingArticle) {
				$data['photo_cover'] = null;
			} elseif ($data['photo_cover']['error'] === UPLOAD_ERR_OK) {
				$data['photo_cover'] = $uploader->update($data['photo_cover'], (string) $postData['old_photo_cover'], 'covers');
			} elseif ($data['photo_cover']['error'] === UPLOAD_ERR_NO_FILE) {
				$data['photo_cover'] = $postData['old_photo_cover'];
			}

			// Separate key and value for update
			foreach ($data as $updateData => $updateValue) {
				if ($updateData === 'photo_cover' && is_array($updateValue) && ($updateValue['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
					$updateValue = $postData['old_photo_cover'] ?? $article['photo_cover'];
				}

				// Always prefer explicit articles.is_deleted to avoid ambiguity
				if ($updateData === 'is_deleted') {
					if (array_key_exists($updateData, $article) && $article[$updateData] != $updateValue) {
						$dataKey[] = "articles.{$updateData} = :{$updateData}";
						$dataUpdate[$updateData] = $updateValue;
					}
					continue;
				}

				if ($updateData === 'photo_path') {
					continue;
				}

				if (array_key_exists($updateData, $article) && $article[$updateData] != $updateValue) {
					$dataKey[] = "{$updateData} = :{$updateData}";
					$dataUpdate[$updateData] = $updateValue;
				}
			}

			if ($this->model('Article')->updateAdmin($dataUpdate, $dataKey, $slug) > 0) {
				if ($isDeletingArticle && !$wasArticleDeleted && !empty($article['photo_cover'])) {
					$uploader->delete($article['photo_cover']);
				}

				Flasher::setFlash('artikel berhasil', 'diperbarui', 'success');
				header('Location: ' . ABSOLUTURL . 'admin/articles');
				exit;
			} else {
				Flasher::setFlash('artikel gagal', 'diperbarui', 'success');
				header('Location: ' . ABSOLUTURL . 'admin/articles');
				exit;
			}
		} else {
			$_SESSION['errors'] = $validator->errors();
			$_SESSION['old'] = [
				'title' => $data['title'],
				'photo_cover' => $data['photo_cover'],
				'is_deleted' => $data['is_deleted'],
				'body' => $data['body']
			];

			header('Location: ' . ABSOLUTURL . 'admin/editArticle/' . $slug);
			exit;
		}
	}
}
