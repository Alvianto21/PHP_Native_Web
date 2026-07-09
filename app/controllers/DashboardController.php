<?php

class DashboardController extends Controller
{
	// articles table
	public function index()
	{
		// cek login
		if (!isset($_SESSION['user_info'])) {
			header('LOCATION: ' . ABSOLUTURL . 'login');
			exit;
		}

		$data['judul'] = 'Halaman Dashboard';
		$data['articles'] = $this->model('Article')->all();

		$this->view('templates/header', $data);
		$this->view('dashboard/list', $data);
		$this->view('templates/footer');
	}

	// create article
	public function create()
	{
		// cek login
		if (!isset($_SESSION['user_info'])) {
			header('LOCATION: ' . ABSOLUTURL . 'login');
			exit;
		}

		$data['judul'] = 'Buat Artikel Baru';

		$this->view('templates/header', $data);
		$this->view('dashboard/create');
		$this->view('templates/footer');
	}

	// Create new article
	public function store()
	{
		require_once __DIR__ . '/../request/Validator.php';

		// cek login
		if (!isset($_SESSION['user_info'])) {
			header('LOCATION: ' . ABSOLUTURL . 'login');
			exit;
		}

		// cek method
		if ($_SERVER['REQUEST_METHOD'] !== "POST") {
			header('LOCATION: ' . ABSOLUTURL . 'dashboard');
			exit;
		}

		$_SESSION['errors'] = [];
		$_SESSION['old_input'] = [];

		$validator = new Validator();
		$postData = $_POST;
		$fileData = $_FILES;
		$user = $_SESSION['user_info']['user_id'];
		$secret = getenv("APP_KEY");

		// cek data
		$data = [
			'title' => $validator->clearData($postData['title'] ?? ''),
			'slug' => '',
			'photo_cover' => $fileData['photo_cover']['name'] ?? '',
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
				'signature' => true
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
				parse_str(parse_url($data['photo_path'], PHP_URL_QUERY), $signUrlData);
				$expired = (int)($signUrlData['expires'] ?? 0);
				$signature = $signUrlData['sig'] ?? '';
				$expected = hash_hmac('sha256', (string) $expired, $secret);

				if ($expired < time()) {
					http_response_code(403);
					exit("Link expired");
				} elseif (!hash_equals($expected, $signature)) {
					http_response_code(403);
					exit("Invalid signature");
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
			$photoFile =  $_FILES['photo_cover'] ?? null;

			if ($photoFile && ($photoFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
				$imgName = basename($photoFile['name']);
				$imgTemp = $photoFile['tmp_name'];
				$imgExt = strtolower(pathinfo($imgName, PATHINFO_EXTENSION));

				// Use bin2hex to generate new file name
				$newImgName = bin2hex(random_bytes(16)) . '.' . $imgExt;
				$uploadDir = __DIR__ . '/../../storage/covers/';
				$savePath = $uploadDir . $newImgName;

				switch ($imgExt) {
					case 'jpg':
						$img = imagecreatefromjpeg($imgTemp);
						imagejpeg($img, $savePath, 90);
						$data['photo_cover'] = '/covers/' . $newImgName;
						unset($img);
						break;
					case 'jpeg':
						$img = imagecreatefromjpeg($imgTemp);
						imagejpeg($img, $savePath, 90);
						$data['photo_cover'] = '/covers/' . $newImgName;
						unset($img);
						break;
					case 'png':
						$img = imagecreatefrompng($imgTemp);
						imagepng($img, $savePath, 90);
						$data['photo_cover'] = '/covers/' . $newImgName;
						unset($img);
						break;
					default:
						// Log the extension and throw exception
						$message = "Unsupported image extension '{$imgExt}' for upload file.";
						error_log($message);
						throw new Exception($message);
						break;
				}
			}

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
		}
	}

	// edit article
	public function edit($id)
	{
		// cek login
		if (!isset($_SESSION['user_info'])) {
			header('LOCATION: ' . ABSOLUTURL . 'login');
			exit;
		}

		$data['judul'] = 'Edit Artikel';
		$article = $this->model('Article')->find($id);

		if ($article) {
			$data['article'] = $article;
		} else {
			header('LOCATION: ' . ABSOLUTURL . 'admin');
			exit;
		}

		$this->view('templates/header', $data);
		$this->view('dashboard/edit', $data);
		$this->view('templates/footer');
	}

	// update data
	public function update()
	{
		if (isset($_POST['submit'])) {
			header('LOCATION:  . ABSOLUTURL . admin');
			exit;
		}

		// cek data
		$data = [
			'title' => $_POST['title'] ?? '',
			'author' => $_POST['author'] ?? '',
			'body' => $_POST['body'] ?? '',
			'id' => $_POST['id'] ?? ''
		];

		if ($this->checkData($data)) {
			header('Location:  . ABSOLUTURL . admin/edit/' . $data['id']);
			exit;
		}

		// clear data
		foreach ($data as $key) {
			$this->clearData($key);
		}

		// add slug
		$data['slug'] = preg_replace('/\s+/', '-', $data['title']);
		$data['slug'] = preg_replace('/[^a-zA-Z0-9\-]/', '', $data['slug']);

		if ($this->model('Article')->update($data) > 0) {
			Flasher::setFlash('artikel berhasil', 'diperbarui', 'success');
			header('Location:  . ABSOLUTURL . admin');
			exit;
		} else {
			Flasher::setFlash('arikel gagal', 'diperbarui', 'danger');
			header('Location:  . ABSOLUTURL . admin');
			exit;
		}
	}

	// hapus artikel
	public function delete($id)
	{
		// cek login
		if (!isset($_SESSION['user_info'])) {
			header('LOCATION: ' . ABSOLUTURL . 'login');
			exit;
		}

		$article = $this->model('Article')->delete($id);

		if ($article) {
			Flasher::setFlash('artikel berhasil', 'dihapus', 'success');
			header('Location:  . ABSOLUTURL . admin');
			exit;
		} else {
			Flasher::setFlash('artikel gagal', 'dihapus', 'danger');
			header('Location:  . ABSOLUTURL . admin');
			exit;
		}
	}

	// cek data
	public function checkData($data)
	{
		foreach ($data as $value) {
			if (empty($value)) {
				return true;
			} else {
				return false;
			}
		}
	}

	// bersihkan data
	public function clearData($data)
	{
		$data = trim($data);
		$data = stripslashes($data);
		$data = htmlspecialchars($data);
		return $data;
	}
}
