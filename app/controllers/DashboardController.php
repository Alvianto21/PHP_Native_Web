<?php

class DashboardController extends Controller
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
			Flasher::setFlash('Mohon maaf, ', 'aksess halaman ini ditolak!', 'danger');
			header('LOCATION: ' . ABSOLUTURL . 'login');
			exit;
		}

		$clientIP = !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
		$this->userIdentifier = $clientIP;
	}

	// articles table
	public function index()
	{
		require_once __DIR__ . '/../request/UploadImage.php';

		$uploader = new UploadImage();

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

		$data['judul'] = 'Halaman Dashboard';
		$data['articles'] = $this->model('Article')->getByUsers($_SESSION['user_info']['user_id'], $perPage, $startPage);

		if (!empty($data['articles'])) {
			foreach ($data['articles'] as &$article) {
				if (!empty($article['photo_cover'])) {
					$article['photo_cover'] = $uploader->show($article['photo_cover']);
				}
			}
		}

		$this->view('templates/header', $data);
		$this->view('dashboard/list', $data);
		$this->view('templates/footer');
	}

	// create article
	public function create()
	{
		$data['judul'] = 'Buat Artikel Baru';
		$data['style'] = "article.css";

		$this->view('templates/header', $data);
		$this->view('dashboard/create');
		$this->view('templates/footer');
	}

	// Create new article
	public function store()
	{
		require_once __DIR__ . '/../request/Validator.php';
		require_once __DIR__ . '/../request/UploadImage.php';

		$limiter = new RateLimiter(30, 60, 100, 3600);

		// cek method
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
		$user = $_SESSION['user_info']['user_id'];

		// cek data
		$data = [
			'title' => $validator->clearData($postData['title'] ?? ''),
			'slug' => '',
			'photo_cover' => $fileData['photo_cover'] ?? '',
			'photo_path' => $postData['photo_path'] ?? '',
			'body' => $validator->clearData($postData['body'] ?? '')
		];

		$rules = [
			'title' => [
				'required' => true,
				'min' => 10,
				'max' => 200,
			],
			'photo_cover' => [
				"size" => 500000, // 500 Kb
				"img" => true
			],
			'photo_path' => [
				'required_if' => 'photo_cover',
				'signature' => true,
			],
			'body' => [
				'required' => true,
				'min' => 200
			]
		];

		// Validate data
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

			// Lowercase and remove apostrophes 
			$slug = strtolower(preg_replace("~[‘’`']+~", '', $data['title']));

			// Replace non-alphanumeric characters with spaces
			$slug = preg_replace("~[^a-z0-9]+~", ' ', $slug);

			// Replace spaces with hyphens and add it to data
			$baseSlug = preg_replace('~[ ]+~', '-', trim($slug));
			$slug = $baseSlug;
			$slugCount = 1;

			while ($this->model('Article')->findSlug($slug)) {
				$slug = $baseSlug . '-' . $slugCount;
				$slugCount++;
			}

			$data['slug'] = $slug;

			// File handling
			$data['photo_cover'] = $uploader->store($data['photo_cover'], "covers");

			if ($this->model('Article')->create($data, $user)) {
				Flasher::setFlash('artikel berhasil', 'ditambahkan', 'success');
				header('Location: ' . ABSOLUTURL . 'dashboard');
				exit;
			} else {
				Flasher::setFlash('artikel gagal', 'ditambahkan', 'danger');
				header('Location: ' . ABSOLUTURL . 'dashboard');
				exit;
			}
		} else {
			$_SESSION['errors'] = $validator->errors();
			$_SESSION['old_input'] = $data;
			header('Location: ' . ABSOLUTURL . 'dashboard/create');
			exit;
		}
	}

	// Show article
	public function show(string $slug)
	{
		require_once __DIR__ . '/../request/UploadImage.php';

		$uploader = new UploadImage();
		$user = $_SESSION['user_info']['user_id'];

		$data['judul'] = 'Detail article';
		$data['style'] = "blog.css";

		$article = $this->model('Article')->findArticleUser($slug, $user);

		if ($article) {
			if (!empty($article['photo_cover'])) {
				$article['photo_cover'] = $uploader->show($article['photo_cover'], 300);
			}

			$data['article'] = $article;
		} else {
			header('LOCATION: ' . ABSOLUTURL . 'dashboard');
			exit;
		}

		$this->view('templates/header', $data);
		$this->view('dashboard/show', $data);
		$this->view('templates/footer');
	}

	// edit article
	public function edit(string $slug)
	{
		$data['judul'] = 'Edit Artikel';
		$data['style'] = "article.css";

		$user = $_SESSION['user_info']['user_id'];
		$article = $this->model('Article')->findArticleUser($slug, $user);

		if ($article) {
			$data['article'] = $article;
		} else {
			header('LOCATION: ' . ABSOLUTURL . 'dashboard');
			exit;
		}

		$this->view('templates/header', $data);
		$this->view('dashboard/edit', $data);
		$this->view('templates/footer');
	}

	// update data
	public function update(string $slug)
	{
		require_once __DIR__ . '/../request/Validator.php';
		require_once __DIR__ . '/../request/UploadImage.php';

		$limiter = new RateLimiter(30, 60, 100, 3600);

		if ($_SERVER['REQUEST_METHOD'] !== "POST") {
			header('LOCATION:  . ABSOLUTURL . dashboard');
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
		$user = $_SESSION['user_info']['user_id'];
		$newSlug = '';
		$article = $this->model('Article')->findArticleUser($slug, $user);
		$dataKey = [];
		$dataUpdate = [];

		if (!$article) {
			Flasher::setFlash('artikel gagal', 'diperbarui', 'danger');
			header('Location: ' . ABSOLUTURL . 'dashboard');
			exit;
		}

		// Get data
		$data = [
			'title' => $validator->clearData($postData['title'] ?? ''),
			'slug' => '',
			'photo_cover' => $fileData['photo_cover'] ?? '',
			'photo_path' => $postData['photo_path'] ?? '',
			'body' => $validator->clearData($_POST['body'] ?? ''),
		];

		$rules = [
			'title' => [
				'required' => true,
				'min' => 10,
				'max' => 200,
			],
			'photo_cover' => [
				"size" => 500000, // 500 Kb
				"img" => true
			],
			'photo_path' => [
				'required_if' => 'photo_cover',
				'signature' => true,
			],
			'body' => [
				'required' => true,
				'min' => 200
			]
		];

		// Validate form
		if ($validator->validate($data, $rules)) {
			// Preserve the current slug so the existing article row is updated correctly.
			$data['slug'] = $article['slug'];

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

				// $data['slug'] = $newSlug;
			} else {
				$data['slug'] = $article['slug'];
			}

			// error_log("old: {$article['slug']} new: {$newSlug} query: {$slug}");

			// Verify img sign url if exist
			if (!empty($data['photo_path'])) {
				$checkUrl = $validator->validateSignUrl($data['photo_path']);

				if ($checkUrl !== true) {
					http_response_code(403);
					echo $checkUrl;
					exit($checkUrl);
				}
			}

			// If photo_cover updated, upload new photo cover and destroy old photo
			if ($data['photo_cover']['error'] === UPLOAD_ERR_OK) {
				$data['photo_cover'] = $uploader->update($data['photo_cover'], $postData['old_photo_cover'], 'covers');
			} elseif ($data['photo_cover']['error'] === UPLOAD_ERR_NO_FILE) {
				$data['photo_cover'] = $postData['old_photo_cover'];
			}

			// Separate key and value for update
			foreach ($data as $updateData => $updateValue) {
				if ($updateData === 'photo_cover' && is_array($updateValue) && ($updateValue['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
					$updateValue = $postData['old_photo_cover'] ?? $article['photo_cover'];
				} elseif (array_key_exists($updateData, $article) && $article[$updateData] != $updateValue) {
					$dataKey[] = "{$updateData} = :{$updateData}";
					$dataUpdate[$updateData] = $updateValue;
				}
			}

			if ($this->model('Article')->update($dataUpdate, $dataKey, $user, $slug) > 0) {
				Flasher::setFlash('artikel berhasil', 'diperbarui', 'success');
				header('Location: ' . ABSOLUTURL . 'dashboard');
				exit;
			} else {
				Flasher::setFlash('artikel gagal', 'diperbarui', 'danger');
				header('Location: ' . ABSOLUTURL . 'dashboard');
				exit;
			}
		} else {
			$_SESSION['errors'] = $validator->errors();
			$_SESSION['old_input'] = [
				'title' => $data['title'],
				'photo_cover' =>  $data['photo_cover'],
				'body' => $data['body']
			];
			header('Location: ' . ABSOLUTURL . 'dashboard/edit/' . $slug);
			exit;
		}
	}

	// Soft delete article
	public function delete(string $slug)
	{
		require_once __DIR__ . '/../request/UploadImage.php';

		$uploader = new UploadImage();
		$user = $_SESSION['user_info']['user_id'];

		$articleTarget = $this->model('Article')->findArticleUser($slug, $user);

		if ($articleTarget) {
			$article = $this->model('Article')->delete($slug, $user);

			if ($article) {
				$uploader->delete($articleTarget['photo_cover']);
				Flasher::setFlash('artikel berhasil', 'dihapus', 'success');
				header('Location: ' . ABSOLUTURL . 'dashboard');
				exit;
			} else {
				Flasher::setFlash('artikel gagal', 'dihapus', 'danger');
				header('Location: ' . ABSOLUTURL . 'dashboard');
				exit;
			}
		} else {
			Flasher::setFlash('artikel gagal', 'dihapus', 'danger');
			header('Location: ' . ABSOLUTURL . 'dashboard');
			exit;
		}
	}
}
