<?php

class ProfileController extends Controller
{

	/**
	 * User identifier.
	 * @var string User IP address.
	 */
	protected $userIdentifier;

	public function __construct()
	{
		// cek login
		if (!isset($_SESSION['user_info'])) {
			header('LOCATION: ' . ABSOLUTURL . 'login');
			exit;
		}

		$clientIP = !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
		$this->userIdentifier = $clientIP;
	}

	// View profile
	public function index()
	{
		require_once __DIR__ . '/../request/UploadImage.php';

		$uploader = new UploadImage();

		$data['judul'] = 'Halaman Profile';
		$data['style'] = "profile.css";
		$user = $this->model('Users')->show($_SESSION['user_info']['user_id']);

		if ($user) {
			$data['user'] = $user;
		} else {
			header('LOCATION: ' . ABSOLUTURL . 'dashboard');
			exit;
		}

		if (!empty($data['user']['photo_profile'])) {
			$data['user']['photo_profile'] = $uploader->show($data['user']['photo_profile']);
		}

		$this->view('templates/header', $data);
		$this->view('profile/index', $data);
		$this->view('templates/footer');
	}

	// Edit profile
	public function edit(string $username)
	{
		$data['judul'] = 'Edit Profile';
		$data['style'] = "sign-up.css";

		$user = $this->model('Users')->findUsername($username, ['email', 'username', 'photo_profile']);

		if ($user) {
			$data['user'] = $user;
		} else {
			header('LOCATION: ' . ABSOLUTURL . 'dashboard');
			exit;
		}

		$this->view('templates/header', $data);
		$this->view('profile/edit', $data);
		$this->view('templates/footer');
	}

	// Update user
	public function update(string $username)
	{
		require_once __DIR__ . '/../request/Validator.php';
		require_once __DIR__ . '/../request/UploadImage.php';

		$limiter = new RateLimiter(30, 60, 100, 3600);

		if ($_SERVER['REQUEST_METHOD'] !== "POST") {
			header('LOCATION: ' . ABSOLUTURL . 'dashboard');
			exit;
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

		$user = $this->model('Users')->findUsername($username, ['email', 'username', 'photo_profile', 'password']);
		$data = [
			'email' => $validator->clearData($postData['email'] ?? ''),
			'username' => $validator->clearData($postData['username'] ?? ''),
			'photo_profile' => $fileData['photo_profile'] ?? '',
			'photo_path' => $postData['photo_path'] ?? '',
			'password' => $validator->clearData($postData['password'] ?? ''),
			'password_confirm' => $validator->clearData($postData['password_confirm'] ?? '')
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
				Flasher::setFlash('Profil berhasil', 'diperbarui', 'success');
				header('Location: ' . ABSOLUTURL . 'profile');
				exit;
			};
		} else {
			$_SESSION['errors'] = $validator->errors();
			$_SESSION['old_input'] = [
				'email' => $data['email'],
				'username' => $data['username']
			];
			header('Location: ' . ABSOLUTURL . 'profile/edit/' . $username);
			exit;
		}
	}

	// Delete user
	public function delete()
	{
		require_once __DIR__ . '/../request/UploadImage.php';

		$uploader = new UploadImage();
		$user_id = $_SESSION['user_info']['user_id'];

		$userTarget = $this->model('Users')->findUser($user_id);

		// If user exist, find corresponding article by that user
		if ($userTarget) {
			$articleTarget = $this->model('Article')->findArticlesUsers($user_id);

			// If article exist remove photo_cover
			if ($articleTarget) {
				$this->model('Article')->deleteAll($user_id);
			}

			$this->model('Users')->delete($user_id);

			if ($userTarget && isset($articleTarget)) {
				foreach ($articleTarget as $article) {
					$uploader->delete($article['photo_profile']);
				}

				$uploader->delete($userTarget['photo_profile']);

				// Clear and destroy sessions
				$_SESSION = [];
				session_destroy();

				// Redirect to home page
				header('LOCATION: ' . BASEURL);
				exit;
			} elseif ($userTarget) {
				$uploader->delete($userTarget['photo_profile']);

				// Clear and destroy sessions
				$_SESSION = [];
				session_destroy();

				// Redirect to home page
				header('LOCATION: ' . BASEURL);
				exit;
			}
		} else {
			// Redirect to home page
			header('LOCATION: ' . BASEURL);
			exit;
		}
	}
}
