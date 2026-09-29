<?php

declare(strict_types=1);

use PhpParser\Node\Expr\Cast\Void_;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\assertArrayIsEqualToArrayOnlyConsideringListOfKeys;

require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/controllers/AdminController.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/Sessions.php';
require_once __DIR__ . '/../helpers/UserLogin.php';
require_once __DIR__ . '/../helpers/TestArticleModel.php';
require_once __DIR__ . '/../helpers/TestUsersModel.php';

class AdminControllerStub extends AdminController
{
	protected $userIdentifier;

	public function __construct(private ?PDO $db = null)
	{
		if (!isset($_SESSION['user_info'])) {
			Flasher::setFlash('Mohon maaf, ', 'aksess halaman ini ditolak!', 'danger');
			throw new RuntimeException('User is not logged in');
		}

		if ($_SESSION['user_info']['user_role'] !== 'admin') {
			Flasher::setFlash('Mohon maaf, ', 'aksess halaman ini ditolak!', 'danger');
			throw new RuntimeException("Access is forbidden");
		}

		$clientIP = !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
		$this->userIdentifier = $clientIP;
	}

	public array $views = [];

	#[Override]
	public function model($model)
	{
		echo "model connect to model {$model}.\n";
		return match ($model) {
			'Article' => new TestArticleModel($this->db),
			'Users' => new TestUsersModel($this->db)
		};
	}

	public function view($view, $data = [])
	{
		echo "connecting to views.\n";
		$this->views[] = ['view' => $view, 'data' => $data];
	}

	public function generatorTempPassword(int $length = 16): string
	{
		if ($_SERVER['REQUEST_METHOD'] !== "POST") {
			return "method not allowed";
		}

		$characters = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%^&*';
		$max = strlen($characters) - 1;
		$password = '';

		for ($i = 0; $i < $length; $i++) {
			$password .= $characters[random_int(0, $max)];
		}

		return $password;
	}

	public function updateUser(string $username): string
	{
		require_once __DIR__ . '/../../app/request/Validator.php';
		require_once __DIR__ . '/../../app/request/UploadImage.php';

		if ($_SERVER['REQUEST_METHOD'] !== "POST") {
			return "method not allowed";
		}

		$validator = new Validator();
		$uploader = new UploadImage();

		$_SESSION['errors'] = [];
		$_SESSION['old_input'] = [];

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
				"regex" => "/^[A-Za-z0-9._]+@[A-Za-z0-9._]+$/",
				// 'unique' => function ($value) {
				// 	$email = filter_var($value, FILTER_SANITIZE_EMAIL);
				// 	$user_id = $_SESSION['user_info']['user_id'];
				// 	$user = $this->model('Users')->isEmailExistExceptId($email, $user_id);
				// 	return (bool) $user;
				// }
			],
			'username' => [
				"required" => true,
				"min" => 5,
				"max" => 25,
				"regex" => "/^[A-Za-z0-9]+$/",
				// 'unique' => function ($value) {
				// 	$user_id = $_SESSION['user_info']['user_id'];
				// 	$user = $this->model('Users')->isUsernameExistExceptId($value, $user_id) ?? null;
				// 	return (bool) $user;
				// }
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

		if ($validator->validate($data, $rules)) {
			if (!empty($data['photo_path'])) {
				$checkUrl = $validator->validateSignUrl($data['photo_path']);

				if ($checkUrl !== true) {
					http_response_code(403);
					echo $checkUrl;
					return "photo URL validation failed";
				}
			}

			$isDeletingUser = isset($data['is_deleted']) && $data['is_deleted'] === '1';
			$wasUserDeleted = isset($user['is_deleted']) && (string) $user['is_deleted'] === '1';

			if ($isDeletingUser) {
				$data['photo_profile'] = null;
			} elseif ($data['photo_profile']['error'] === UPLOAD_ERR_OK) {
				$data['photo_profile'] = $uploader->update($data['photo_profile'], (string) $postData['old_photo_profile'], 'profiles');
			} elseif ($data['photo_profile']['error'] === UPLOAD_ERR_NO_FILE) {
				$data['photo_profile'] = $postData['old_photo_profile'];
			}

			if ($shouldUpdatePassword) {
				$data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
			}

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

			if (empty($dataKey)) {
				echo "user {$username} profile updated.\n";
				return "user updated";
			}

			if ($this->model('Users')->update($dataUpdate, $dataKey, $username) > 0) {
				if ($isDeletingUser && !$wasUserDeleted) {
					$oldProfile = $postData['old_photo_profile'] ?? $user['photo_profile'] ?? '';
					$articleModel = $this->model('Article');
					$coverPhotos = $articleModel->getCoverPhotosByUser((int) $user['id']);
					$articleModel->deleteAll((int) $user['id']);

					if (!empty($oldProfile)) {
						$uploader->delete((string) $oldProfile);
					}

					foreach ($coverPhotos as $photo) {
						if (!empty($photo['photo_cover'])) {
							$uploader->delete((string) $photo['photo_cover']);
						}
					}

					echo "user {$username} deleted.\n";
					return 'user deleted';
				} else {
					echo "user {$username} profile updated.\n";
					return "user updated";
				}
			} else {
				var_dump($validator->errors());
				return "update failed";
			}
		} else {
			echo "validation failed.\n";
			$_SESSION['errors'] = $validator->errors();
			$_SESSION['old_input'] = [
				'email' => $data['email'],
				'username' => $data['username'],
				'role' => $data['role'],
				'is_deleted' => $data['is_deleted']
			];
			return "validation failed";
		}
	}

	public function updateArticle(string $slug): string
	{
		require_once __DIR__ . '/../../app/request/Validator.php';
		require_once __DIR__ . '/../../app/request/UploadImage.php';

		if ($_SERVER['REQUEST_METHOD'] !== "POST") {
			return "method not allowed";
		}

		$validator = new Validator();
		$uploader = new UploadImage();

		$_SESSION['errors'] = [];
		$_SESSION['old_input'] = [];

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

		if ($validator->validate($data, $rules)) {
			if ($article['title'] !== $data['title']) {
				$newSlug = strtolower(preg_replace("~[‘’`']+~", '', $data['title']));
				$newSlug = preg_replace("~[^a-z0-9]+~", ' ', $newSlug);
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
					return "photo URL validation failed";
				}
			}

			$isDeletingArticle = isset($data['is_deleted']) && $data['is_deleted'] === '1';
			$wasArticleDeleted = isset($article['is_deleted']) && (string) $article['is_deleted'] === '1';

			if ($isDeletingArticle) {
				$data['photo_cover'] = null;
			} elseif ($data['photo_cover']['error'] === UPLOAD_ERR_OK) {
				$data['photo_cover'] = $uploader->update($data['photo_cover'], (string) $postData['old_photo_cover'], 'covers');
			} elseif ($data['photo_cover']['error'] === UPLOAD_ERR_NO_FILE) {
				$data['photo_cover'] = $postData['old_photo_cover'];
			}

			foreach ($data as $updateData => $updateValue) {
				if ($updateData === 'photo_cover' && is_array($updateValue) && ($updateValue['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
					$updateValue = $postData['old_photo_cover'] ?? $article['photo_cover'];
				}

				if ($updateData === 'is_deleted') {
					if (array_key_exists($updateData, $article) && $article[$updateData] != $updateValue) {
						$dataKey[] = "{$updateData} = :{$updateData}";
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

			if (empty($dataKey)) {
				echo "article with title " . substr($article['title'], 0, 50) . " updated.\n";
				return "article updated";
			}

			if ($this->model('Article')->updateAdmin($dataUpdate, $dataKey, $slug) > 0) {
				if ($isDeletingArticle && !$wasArticleDeleted) {
					if (!empty($article['photo_cover'])) {
						$uploader->delete((string) $article['photo_cover']);
					}

					echo "article with title " . substr($article['title'], 0, 50) . " deleted.\n";
					return "article deleted";
				} else {
					echo "article with title " . substr($article['title'], 0, 50) . " updated.\n";
					return "article updated";
				}
			} else {
				var_dump($validator->errors());
				return "update failed";
			}
		} else {
			echo "validation failed.\n";
			$_SESSION['errors'] = $validator->errors();
			$_SESSION['old_input'] = [
				'title' => $data['title'],
				'photo_cover' => $data['photo_cover'],
				'is_deleted' => $data['is_deleted'],
				'body' => $data['body']
			];
			return "validation failed";
		}
	}
}

#[TestDox("admin controller")]
class AdminTest extends TestCase
{
	private $db;

	private array $userAdmin = [
		'email' => 'akagi@gmail.com',
		'username' => 'javelin1',
		'photo_path' => '',
		'password' => 'union777union',
		'password_confirm' => 'union777union'
	];

	private array $userAdminImg = [
		'photo_profile' => [
			'name' => 'users.jpg',
			'type' => 'image/jpg',
			'tmp_name' => __DIR__ . '/../../storage/tests/user3.jpg',
			'error' => UPLOAD_ERR_OK,
			'size' => 10000
		]
	];

	use DatabaseUp;

	protected function fillFormWithFile(array $formData, array $formFile)
	{
		echo "filling form...\n";
		$_POST = $formData;
		$_FILES = $formFile;
		echo "form is filled.\n";
	}

	protected function fillForm(array $formData)
	{
		echo "filling form...\n";
		$_POST = $formData;
		echo "form is filled.\n";
	}

	#[Before]
	protected function UrlGenerator(): string
	{
		$secretKey = getenv('APP_KEY') ?: 'change-me';
		$expired = time() + 300; // 5 mins
		$signature = hash_hmac('sha256', (string) $expired, $secretKey);
		return ABSOLUTURL . 'files/store?expires=' . $expired . '&sig=' . $signature;
	}

	#[Override]
	protected function setUp(): void
	{
		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

		echo "starting sessions.\n";
		$sessionHandler = new TestSessions($this->db);
		session_set_save_handler($sessionHandler, true);
		if (!session_id()) session_start();
		echo "session started.\n";
	}

	protected function createUser()
	{
		echo "creating users...\n";
		$generator = new TestUsersModel($this->db);

		$generator->usersGenerator();
		return $generator->userRandomizer();
	}

	protected function createAdminUser()
	{
		echo "creating admin user...\n";
		$generator = new TestUsersModel($this->db);

		$this->fillFormWithFile($this->userAdmin, $this->userAdminImg);
		$statusCreate = $generator->createAdminUser($_POST, $_FILES);

		return $statusCreate;
	}

	protected function loginUser(array $data)
	{
		echo "prepare to login.\n";

		$_SERVER['REQUEST_METHOD'] = 'POST';

		$controller = new HelperLogin($this->db);

		$statusLogin = $controller->userLogin($data);

		if ($statusLogin !== 'login success' && !isset($_SESSION['user_info'])) {
			echo $statusLogin;
			die($statusLogin);
		}

		unset($_SERVER['REQUEST_METHOD']);
		return $statusLogin;
	}

	protected function logoutUser()
	{
		echo "prepare logout user..\n";

		$userGene = new HelperLogin($this->db);
		$statusLogout = $userGene->logoutUser();

		if (!$statusLogout) {
			echo $statusLogout;
			die($statusLogout);
		}
	}

	protected function createArticles(int $user_id)
	{
		echo "creating data dummy articles...\n";
		$generator = new TestArticleModel($this->db);

		$status = $generator->articleGenerator($user_id);

		if ($status !== "articles created") {
			echo $status;
			die($status);
		}

		echo "articles data generated.\n";
	}

	protected function deleteUser(string $username)
	{
		require_once __DIR__ . '/../../app/request/UploadImage.php';

		$uploader = new UploadImage();
		$generator = new TestUsersModel($this->db);

		$user = $generator->findUsername($username, ['id', 'photo_profile']);

		$status = $generator->delete($user['id']);

		if ($status && $user['photo_profile'] !== null) {
			$uploader->delete((string) $user['photo_profile']);
			echo "user {$username} and their photo profile deleted.\n";
			return "user and their photo profile deleted";
		} elseif ($status) {
			echo "user {$username} deleted.\n";
			return "user deleted";
		} else {
			return "something wrong";
		}
	}

	protected function deleteArticle(string $slug, int $user_id)
	{
		require_once __DIR__ . '/../../app/request/UploadImage.php';

		$uploader = new UploadImage();
		$generator = new TestArticleModel($this->db);

		$article = $generator->findArticleUser($slug, $user_id);

		$status = $generator->delete($article['slug'], $user_id);

		if ($status && $article['photo_cover'] !== null) {
			$uploader->delete((string) $article['photo_cover']);
			echo "article with title {$article['title']} and photo cover deleted.\n";
			return "article and photo cover deleted";
		} elseif ($status) {
			echo "article with title {$article['title']} deleted.\n";
			return "article deleted";
		} else {
			return "something wrong";
		}
	}

	#[Test] #[TestDox("Users page is accessible")]
	public function users_page_is_accessible(): void
	{
		$this->createUser();
		$this->createAdminUser();
		$user = [
			'email' => $this->userAdmin['email'],
			'password' => $this->userAdmin['password']
		];
		$this->fillForm($user);
		$this->loginUser($_POST);

		$controller = new AdminControllerStub($this->db);

		$controller->users();

		$this->assertCount(3, $controller->views);
		$this->assertSame('templates/header', $controller->views[0]['view']);
		$this->assertSame('admin/users', $controller->views[1]['view']);
		$this->assertSame('templates/footer', $controller->views[2]['view']);
		$this->assertArrayHasKey('users', $controller->views[1]['data']);
		$this->assertCount(6, $controller->views[1]['data']['users']);
	}

	#[Test] #[TestDox("Users page is unaccessible because user not login")]
	public function users_page_is_unaccessible_because_user_not_login(): void
	{
		try {
			new AdminControllerStub($this->db);
			$this->fail('Expected RuntimeException');
		} catch (RuntimeException $e) {
			$this->assertSame('User is not logged in', $e->getMessage());
			$this->assertTrue(isset($_SESSION['flash']));
		}
	}

	#[Test] #[TestDox("Users page is unaccessible because user not admin")]
	public function users_page_is_unaccessible_because_user_not_admin(): void
	{
		$target = $this->createUser();
		$user = [
			'email' => $target['email'],
			'password' => $target['password']
		];

		$this->fillForm($user);
		$this->loginUser($_POST);

		try {
			new AdminControllerStub($this->db);
			$this->fail("Expected RuntimeException");
		} catch (RuntimeException $e) {
			$this->assertSame("Access is forbidden", $e->getMessage());
			$this->assertTrue(isset($_SESSION['flash']));
		}
	}

	#[Test] #[TestDox("Admin can see deleted user")]
	public function admin_can_see_deleted_user(): void
	{
		$target = $this->createUser();
		$this->createAdminUser();
		$user = [
			'email' => $this->userAdmin['email'],
			'password' => $this->userAdmin['password']
		];

		$this->fillForm($user);
		$this->loginUser($_POST);

		$controller = new AdminControllerStub($this->db);

		$this->deleteUser($target['username']);

		$controller->showUser($target['username']);

		$this->assertCount(3, $controller->views);
		$this->assertSame('templates/header', $controller->views[0]['view']);
		$this->assertSame('admin/showUser', $controller->views[1]['view']);
		$this->assertSame('templates/footer', $controller->views[2]['view']);
		$this->assertArrayHasKey('user', $controller->views[1]['data']);
		$this->assertSame('Deleted', $controller->views[1]['data']['user']['is_deleted']);
	}

	#[Test] #[TestDox("Admin can delete user")]
	public function admin_can_delete_user(): void
	{
		$target = $this->createUser();
		$this->createAdminUser();
		$user = [
			'email' => $this->userAdmin['email'],
			'password' => $this->userAdmin['password']
		];

		$this->fillForm($user);
		$this->loginUser($_POST);

		$controller = new AdminControllerStub($this->db);
		$generator = new TestUsersModel($this->db);

		$user_status = $generator->findUsernameAdmin($target['username'], ['photo_profile', 'role', 'is_deleted']);

		$new_data_user = [
			'email' => $target['email'],
			'username' => $target['username'],
			'photo_path' => '',
			'old_photo_profile' => $user_status['photo_profile'],
			'password' => '',
			'password_confirm' => '',
			'role' => $user_status['role'],
			'is_deleted' => '1'
		];

		$new_data_user_img = [
			'photo_profile' => [
				'name' => '',
				'type' => '',
				'tmp_name' => '',
				'error' => UPLOAD_ERR_NO_FILE,
				'size' => 0
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		echo "prepare storing data...\n";
		$this->fillFormWithFile($new_data_user, $new_data_user_img);

		$action = $controller->updateUser($target['username']);

		$this->assertTrue($action === 'user deleted');

		$new_user_status = $generator->findUsernameAdmin($target['username'], ['is_deleted']);

		$this->assertSame((int) $new_data_user['is_deleted'], $new_user_status['is_deleted']);
		$this->assertTrue($new_user_status['is_deleted'] === 1);
	}

	#[Test] #[TestDox("Admin can delete user with articles")]
	public function admin_can_delete_user_with_articles(): void
	{
		$target = $this->createUser();
		$this->createAdminUser();
		$user = [
			'email' => $this->userAdmin['email'],
			'password' => $this->userAdmin['password']
		];

		$this->fillForm($user);
		$this->loginUser($_POST);

		$controller = new AdminControllerStub($this->db);
		$GeneratorUsers = new TestUsersModel($this->db);
		$generatorArticles = new TestArticleModel($this->db);

		$user_status = $GeneratorUsers->findUsernameAdmin($target['username'], ['id', 'photo_profile', 'role', 'is_deleted']);

		$this->createArticles($user_status['id']);

		$user_articles = $generatorArticles->findArticleByUser($user_status['id'], ['slug']);

		$new_user_data = [
			'email' => $target['email'],
			'username' => $target['username'],
			'photo_path' => '',
			'old_photo_profile' => $user_status['photo_profile'],
			'password' => '',
			'password_confirm' => '',
			'role' => $user_status['role'],
			'is_deleted' => '1'
		];

		$new_user_data_img = [
			'photo_profile' => [
				'name' => '',
				'type' => '',
				'tmp_name' => '',
				'error' => UPLOAD_ERR_NO_FILE,
				'size' => 0
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		echo "prepare storing data...\n";
		$this->fillFormWithFile($new_user_data, $new_user_data_img);

		$action = $controller->updateUser($target['username']);

		$this->assertTrue($action === 'user deleted');

		$new_user_status = $GeneratorUsers->findUsernameAdmin($target['username'], ['is_deleted']);
		$new_user_articles_status = $generatorArticles->findArticleByUser($user_status['id'], ['slug']);

		$this->assertSame((int) $new_user_data['is_deleted'], $new_user_status['is_deleted']);
		$this->assertNull($new_user_articles_status['slug']);
		$this->assertNotSame($user_articles, $new_user_articles_status['slug']);
		$this->assertTrue($new_user_status['is_deleted'] === 1);
	}

	#[Test] #[TestDox("Admin can revive deleted user")]
	public function admin_can_revive_deleted_user(): void
	{
		$target = $this->createUser();
		$this->createAdminUser();
		$user = [
			'email' => $this->userAdmin['email'],
			'password' => $this->userAdmin['password']
		];

		$this->fillForm($user);
		$this->loginUser($_POST);
		$this->deleteUser($target['username']);

		$controller = new AdminControllerStub($this->db);
		$generator = new TestUsersModel($this->db);

		$user_status = $generator->findUsernameAdmin($target['username'], ['photo_profile', 'role', 'is_deleted']);

		$new_user_data = [
			'email' => $target['email'],
			'username' => $target['username'],
			'photo_path' => '',
			'old_photo_profile' => $user_status['photo_profile'],
			'password' => '',
			'password_confirm' => '',
			'role' => $user_status['role'],
			'is_deleted' => '0'
		];

		$new_user_data_img = [
			'photo_profile' => [
				'name' => '',
				'type' => '',
				'tmp_name' => '',
				'error' => UPLOAD_ERR_NO_FILE,
				'size' => 0
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		echo "prepare storing data...\n";
		$this->fillFormWithFile($new_user_data, $new_user_data_img);

		$action = $controller->updateUser($target['username']);

		$this->assertTrue($action === 'user updated');

		$new_user_status = $generator->findUsernameAdmin($target['username'], ['is_deleted']);

		$this->assertSame((int) $new_user_data['is_deleted'], $new_user_status['is_deleted']);
		$this->assertTrue($new_user_status['is_deleted'] === 0);
	}

	#[Test] #[TestDox("Admin failed delete user because invalid input")]
	public function admin_failed_delete_user_because_invalid_input(): Void
	{
		$target = $this->createUser();
		$this->createAdminUser();
		$user = [
			'email' => $this->userAdmin['email'],
			'password' => $this->userAdmin['password']
		];

		$this->fillForm($user);
		$this->loginUser($_POST);

		$controller = new AdminControllerStub($this->db);
		$generator = new TestUsersModel($this->db);

		$user_status = $generator->findUsernameAdmin($target['username'], ['photo_profile', 'role', 'is_deleted']);

		$new_user_data = [
			'email' => $target['email'],
			'username' => $target['username'],
			'photo_path' => '',
			'old_photo_profile' => $user_status['photo_profile'],
			'password' => '',
			'password_confirm' => '',
			'role' => $user_status['role'],
			'is_deleted' => 'true'
		];

		$new_user_data_img = [
			'photo_profile' => [
				'name' => '',
				'type' => '',
				'tmp_name' => '',
				'error' => UPLOAD_ERR_NO_FILE,
				'size' => 0
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		echo "prepare storing data...\n";
		$this->fillFormWithFile($new_user_data, $new_user_data_img);

		$action = $controller->updateUser($target['username']);

		$this->assertTrue($action === 'validation failed');
		$this->assertNotNull($_SESSION['errors']);
		$this->assertNotNull($_SESSION['old_input']);
		$this->assertArrayHasKey('is_deleted', $_SESSION['errors']);
		$this->assertArrayHasKey('is_deleted', $_SESSION['old_input']);
		$this->assertSame("The 'is_deleted' input is invalid.", $_SESSION['errors']['is_deleted']);
		$this->assertArrayIsEqualToArrayIgnoringListOfKeys($new_user_data, $_SESSION['old_input'], ['photo_path', 'photo_profile', 'old_photo_profile', 'password', 'password_confirm']);
	}

	#[Test] #[TestDox("Admin can change user role")]
	public function admin_can_change_user_role(): void
	{
		$target = $this->createUser();
		$this->createAdminUser();
		$user = [
			'email' => $this->userAdmin['email'],
			'password' => $this->userAdmin['password']
		];

		$this->fillForm($user);
		$this->loginUser($_POST);

		$controller = new AdminControllerStub($this->db);
		$generator = new TestUsersModel($this->db);

		$user_status = $generator->findUsernameAdmin($target['username'], ['photo_profile', 'role', 'is_deleted']);

		$new_user_data = [
			'email' => $target['email'],
			'username' => $target['username'],
			'photo_path' => '',
			'old_photo_profile' => $user_status['photo_profile'],
			'password' => '',
			'password_confirm' => '',
			'role' => 'admin',
			'is_deleted' => (string) $user_status['is_deleted']
		];

		$new_user_data_img = [
			'photo_profile' => [
				'name' => '',
				'type' => '',
				'tmp_name' => '',
				'error' => UPLOAD_ERR_NO_FILE,
				'size' => 0
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		echo "prepare storing data...\n";
		$this->fillFormWithFile($new_user_data, $new_user_data_img);

		$action = $controller->updateUser($target['username']);

		$this->assertTrue($action === 'user updated');

		$new_user_role = $generator->findUsernameAdmin($target['username'], ['role']);

		$this->assertSame($new_user_data['role'], $new_user_role['role']);
		$this->assertNotSame($user_status['role'], $new_user_role['role']);
	}

	#[Test] #[TestDox("Admin failed change user role because invalid input")]
	public function admin_failed_change_user_role_because_invalid_input(): void {
		$target = $this->createUser();
		$this->createAdminUser();
		$user = [
			'email' => $this->userAdmin['email'],
			'password' => $this->userAdmin['password']
		];

		$this->fillForm($user);
		$this->loginUser($_POST);

		$controller = new AdminControllerStub($this->db);
		$generator = new TestUsersModel($this->db);

		$user_status = $generator->findUsernameAdmin($target['username'], ['photo_profile', 'role', 'is_deleted']);

		$new_user_data = [
			'email' => $target['email'],
			'username' => $target['username'],
			'photo_path' => '',
			'old_photo_profile' => $user_status['photo_profile'],
			'password' => '',
			'password_confirm' => '',
			'role' => 'superAdmin',
			'is_deleted' => (string) $user_status['is_deleted']
		];

		$new_user_data_img = [
			'photo_profile' => [
				'name' => '',
				'type' => '',
				'tmp_name' => '',
				'error' => UPLOAD_ERR_NO_FILE,
				'size' => 0
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		echo "prepare storing data...\n";
		$this->fillFormWithFile($new_user_data, $new_user_data_img);

		$action = $controller->updateUser($target['username']);

		$this->assertTrue($action === 'validation failed');
		$this->assertNotNull($_SESSION['errors']);
		$this->assertNotNull($_SESSION['old_input']);
		$this->assertArrayHasKey('role', $_SESSION['errors']);
		$this->assertArrayHasKey('role', $_SESSION['old_input']);
		$this->assertSame("The 'role' input is invalid.", $_SESSION['errors']['role']);
		$this->assertArrayIsEqualToArrayIgnoringListOfKeys($new_user_data, $_SESSION['old_input'], ['photo_path', 'photo_profile', 'old_photo_profile', 'password', 'password_confirm']);
	}
	

	#[Test] #[TestDox("Admin can generate temporary password for user")]
	public function admin_can_generate_temporary_password_for_user(): void
	{
		$target = $this->createUser();
		$this->createAdminUser();
		$user = [
			'email' => $this->userAdmin['email'],
			'password' => $this->userAdmin['password']
		];

		$this->fillForm($user);
		$this->loginUser($_POST);

		$controller = new AdminControllerStub($this->db);
		$generator = new TestUsersModel($this->db);

		$user_status = $generator->findUsernameAdmin($target['username'], ['photo_profile', 'role', 'is_deleted']);
		$generatorPassword = $controller->generatorTempPassword();

		$new_user_data = [
			'email' => $target['email'],
			'username' => $target['username'],
			'photo_path' => '',
			'old_photo_profile' => $user_status['photo_profile'],
			'password' => $generatorPassword,
			'password_confirm' => $generatorPassword,
			'role' => $user_status['role'],
			'is_deleted' => (string) $user_status['is_deleted']
		];

		$new_user_data_img = [
			'photo_profile' => [
				'name' => '',
				'type' => '',
				'tmp_name' => '',
				'error' => UPLOAD_ERR_NO_FILE,
				'size' => 0
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		echo "prepare storing data...\n";
		$this->fillFormWithFile($new_user_data, $new_user_data_img);

		$action = $controller->updateUser($target['username']);

		$this->assertTrue($action === 'user updated');

		$this->logoutUser();

		$user2 = [
			'email' => $target['email'],
			'password' => $generatorPassword
		];

		$this->fillForm($user2);

		$action2 = $this->loginUser($_POST);

		$this->assertTrue($action2 === 'login success');
	}

	#[Test] #[TestDox("Articles page is accessible")]
	public function articles_page_is_accessible(): void
	{
		$user1 = $this->createUser();

		$this->fillForm(['email' => $user1['email'], 'password' => $user1['password']]);
		$this->loginUser($_POST);
		$this->createArticles($_SESSION['user_info']['user_id']);
		$this->logoutUser();
		$this->createAdminUser();

		$user2 = [
			'email' => $this->userAdmin['email'],
			'password' => $this->userAdmin['password']
		];

		$this->fillForm($user2);
		$this->loginUser($_POST);

		$controller = new AdminControllerStub($this->db);

		$controller->articles();

		$this->assertCount(3, $controller->views);
		$this->assertSame('templates/header', $controller->views[0]['view']);
		$this->assertSame('admin/articles', $controller->views[1]['view']);
		$this->assertSame('templates/footer', $controller->views[2]['view']);
		$this->assertArrayHasKey('articles', $controller->views[1]['data']);
		$this->assertCount(5, $controller->views[1]['data']['articles']);
	}

	#[Test] #[TestDox("Admin can see deleted article")]
	public function admin_can_see_deleted_article(): void
	{
		$user1 = $this->createUser();
		$generator = new TestArticleModel($this->db);

		$this->fillForm(['email' => $user1['email'], 'password' => $user1['password']]);
		$this->loginUser($_POST);

		$user1Id = $_SESSION['user_info']['user_id'];
		$this->createArticles($user1Id);

		$targetArticle = $generator->articlesRadomizer();
		$this->deleteArticle($targetArticle['slug'], $user1Id);

		$this->logoutUser();
		$this->createAdminUser();

		$user2 = [
			'email' => $this->userAdmin['email'],
			'password' => $this->userAdmin['password']
		];

		$this->fillForm($user2);
		$this->loginUser($_POST);

		$controller = new AdminControllerStub($this->db);

		$controller->showArticle($targetArticle['slug']);
		$article_status = $generator->findArticlesUsers($targetArticle['slug']);

		$this->assertCount(3, $controller->views);
		$this->assertSame('templates/header', $controller->views[0]['view']);
		$this->assertSame('admin/showArticle', $controller->views[1]['view']);
		$this->assertSame('templates/footer', $controller->views[2]['view']);
		$this->assertArrayHasKey('article', $controller->views[1]['data']);
		$this->assertTrue($article_status['is_deleted'] === 1);
	}

	#[Test] #[TestDox("Admin can delete article from other user")]
	public function admin_can_delete_article_from_other_user(): void {
		$user1 = $this->createUser();

		$this->fillForm(['email' => $user1['email'], 'password' => $user1['password']]);
		$this->loginUser($_POST);
		$user1Id = $_SESSION['user_info']['user_id'];
		$this->createArticles($user1Id);

		$this->logoutUser();
		$this->createAdminUser();

		$user2 = [
			'email' => $this->userAdmin['email'],
			'password' => $this->userAdmin['password']
		];

		$this->fillForm($user2);
		$this->loginUser($_POST);

		$controller = new AdminControllerStub($this->db);
		$generator = new TestArticleModel($this->db);

		$targetArticle = $generator->articlesRadomizer();

		$new_article_data = [
			'title' => $targetArticle['title'],
			'photo_path' => '',
			'old_photo_cover' => $targetArticle['photo_cover'],
			'body' => $targetArticle['body'],
			'is_deleted' => '1'
		];

		$new_article_data_img = [
			'photo_cover' => [
				'name' => '',
				'type' => '',
				'tmp_name' => '',
				'error' => UPLOAD_ERR_NO_FILE,
				'size' => 0
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		echo "prepare storing data...\n";
		$this->fillFormWithFile($new_article_data, $new_article_data_img);

		$action = $controller->updateArticle($targetArticle['slug']);

		$this->assertTrue($action === 'article deleted');

		$new_article_status = $generator->isArticleDeleted($targetArticle['slug'],$user1Id);

		$this->assertTrue($new_article_status);
	}
	
	#[Test] #[TestDox("Admin can revive deleted article")]
	public function admin_can_revive_deleted_article(): void {
		$user1 = $this->createUser();

		$this->fillForm(['email' => $user1['email'], 'password' => $user1['password']]);
		$this->loginUser($_POST);

		$user1Id = $_SESSION['user_info']['user_id'];
		$this->createArticles($user1Id);
		$generator = new TestArticleModel($this->db);

		$targetArticle = $generator->articlesRadomizer();
		$this->deleteArticle($targetArticle['slug'], $user1Id);

		$this->logoutUser();
		$this->createAdminUser();

		$user2 = [
			'email' => $this->userAdmin['email'],
			'password' => $this->userAdmin['password']
		];

		$this->fillForm($user2);
		$this->loginUser($_POST);

		$controller = new AdminControllerStub($this->db);
		
		$new_article_data = [
			'title' => $targetArticle['title'],
			'photo_path' => '',
			'old_photo_cover' => $targetArticle['photo_cover'],
			'body' => $targetArticle['body'],
			'is_deleted' => '0'
		];

		$new_article_data_img = [
			'photo_cover' => [
				'name' => '',
				'type' => '',
				'tmp_name' => '',
				'error' => UPLOAD_ERR_NO_FILE,
				'size' => 0
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		echo "prepare storing data...\n";
		$this->fillFormWithFile($new_article_data, $new_article_data_img);

		$action = $controller->updateArticle($targetArticle['slug']);

		$this->assertTrue($action === 'article updated');

		$new_article_status = $generator->findArticleUser($targetArticle['slug'], $user1Id);

		$this->assertIsArray($new_article_status);
	}
	
	#[Test] #[TestDox("Admin failed delete article from other user because invalid input")]
	public function admin_failed_delete_article_from_other_user_because_invalid_input(): void {
		$user1 = $this->createUser();

		$this->fillForm(['email' => $user1['email'], 'password' => $user1['password']]);
		$this->loginUser($_POST);
		$user1Id = $_SESSION['user_info']['user_id'];
		$this->createArticles($user1Id);

		$this->logoutUser();
		$this->createAdminUser();

		$user2 = [
			'email' => $this->userAdmin['email'],
			'password' => $this->userAdmin['password']
		];

		$this->fillForm($user2);
		$this->loginUser($_POST);

		$controller = new AdminControllerStub($this->db);
		$generator = new TestArticleModel($this->db);

		$targetArticle = $generator->articlesRadomizer();

		$new_article_data = [
			'title' => $targetArticle['title'],
			'photo_path' => '',
			'old_photo_cover' => $targetArticle['photo_cover'],
			'body' => $targetArticle['body'],
			'is_deleted' => 'true'
		];

		$new_article_data_img = [
			'photo_cover' => [
				'name' => '',
				'type' => '',
				'tmp_name' => '',
				'error' => UPLOAD_ERR_NO_FILE,
				'size' => 0
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		echo "prepare storing data...\n";
		$this->fillFormWithFile($new_article_data, $new_article_data_img);

		$action = $controller->updateArticle($targetArticle['slug']);

		$this->assertTrue($action === 'validation failed');
		$this->assertNotNull($_SESSION['errors']);
		$this->assertNotNull($_SESSION['old_input']);
		$this->assertArrayHasKey('is_deleted', $_SESSION['errors']);
		$this->assertArrayHasKey('is_deleted', $_SESSION['old_input']);
		$this->assertSame("The 'is_deleted' input is invalid.", $_SESSION['errors']['is_deleted']);
		$this->assertArrayIsEqualToArrayIgnoringListOfKeys($new_article_data, $_SESSION['old_input'], ['photo_path', 'photo_cover', 'old_photo_cover']);
	}
	

	#[Override]
	protected function tearDown(): void
	{
		$this->db = null;
		$_SERVER = [];
		$_POST = [];
		$_FILES = [];
		session_unset();
		session_destroy();
		parent::tearDown();
	}
}
