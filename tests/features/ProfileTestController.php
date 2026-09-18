<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/controllers/ProfileController.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/Sessions.php';
require_once __DIR__ . '/../helpers/UserLogin.php';
require_once __DIR__ . '/../helpers/TestUsersModel.php';
require_once __DIR__ . '/../helpers/TestArticleModel.php';

use PhpParser\Node\Expr\Cast\Void_;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ProfileControllerStub extends ProfileController
{
	protected $userIdentifier;

	public function __construct(private ?PDO $db = null)
	{
		if (!isset($_SESSION['user_info'])) {
			Flasher::setFlash('Mohon maaf, ', 'aksess halaman ini ditolak!', 'danger');
			throw new RuntimeException('User is not logged in');
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
			'Users' => new TestUsersModel($this->db),
			'Article' => new TestArticleModel($this->db)
		};
	}

	public function view($view, $data = [])
	{
		echo "connecting to views.\n";
		$this->views[] = ['view' => $view, 'data' => $data];
	}

	public function editProfile(string $username)
	{
		$data['judul'] = 'Edit Profile';
		$data['style'] = "sign-up.css";

		$user = $this->model('Users')->findUsername($username, ['email', 'username', 'photo_profile']);

		if ($user) {
			$data['user'] = $user;
		} else {
			echo "user with {$username} not found.\n";
			return "user not found";
		}

		$this->view('templates/header', $data);
		$this->view('profile/edit', $data);
		$this->view('templates/footer');
	}

	public function updateProfile(string $username): string
	{
		require_once __DIR__ . '/../../app/request/UploadImage.php';
		require_once __DIR__ . '/../../app/request/Validator.php';

		if ($_SERVER['REQUEST_METHOD'] !== "POST") {
			return "method not allowed";
		}

		$validator = new Validator();
		$uploader = new UploadImage();

		$postData = $_POST;
		$fileData = $_FILES;
		$dataKey = [];
		$dataUpdate = [];

		$user = $this->model('Users')->findUsername($username, ['email', 'username', 'photo_profile', 'password']);
		$activeUser = $_SESSION['user_info']['user_id'];

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
				"regex" => "/^[A-Za-z0-9._]+@[A-Za-z0-9._]+$/",
				'unique' => function ($value) {
					$email = filter_var($value, FILTER_SANITIZE_EMAIL);
					$user_id = $_SESSION['user_info']['user_id'];
					$user = $this->model('Users')->isEmailExistExceptId($email, $user_id);
					return (bool) $user;
				}
			],
			"username" => [
				"required" => true,
				"min" => 5,
				"max" => 25,
				"regex" => "/^[A-Za-z0-9]+$/",
				'unique' => function ($value) {
					$user_id = $_SESSION['user_info']['user_id'];
					$user = $this->model('Users')->isUsernameExistExceptId($value, $user_id) ?? null;
					return (bool) $user;
				}
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

			if ($data['photo_profile']['error'] === UPLOAD_ERR_OK) {
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

			if ($this->model('Users')->update($dataUpdate, $dataKey, $username, $activeUser) > 0) {
				echo "user {$username} profile updated.\n";
				return "user updated";
			} else {
				var_dump($validator->errors());
				return "update failed";
			}
		} else {
			echo "validation failed.\n";
			var_dump($validator->errors());
			return "validation failed";
		}
	}

	public function deleteUser(): string {
		require_once __DIR__ . '/../../app/request/UploadImage.php';

		$uploader = new UploadImage();
		$user_id = $_SESSION['user_info']['user_id'];

		$userTarget = $this->model('Users')->findUser($user_id);

		if ($userTarget) {
			$articleTarget = $this->model('Article')->findArticleByUser($user_id, ['slug', 'photo_cover']);

			if ($articleTarget) {
				$this->model('Article')->deleteAll($user_id);
				
				foreach ($articleTarget as $article) {
					if (!empty($article['photo_cover'])) {
						$uploader->delete((string) $article['photo_cover']);
					}
				}

				$this->model('Users')->delete($user_id);

				if (!empty($userTarget['photo_profile'])) {
					$uploader->delete((string) $userTarget['photo_profile']);
				}

				$_SESSION = [];
				session_destroy();
				echo "user and article deleted.\n";

				return "user and article deleted";
			} else {
				$this->model('Users')->delete($user_id);

				if (!empty($userTarget['photo_profile'])) {
					$uploader->delete((string) $userTarget['photo_profile']);
				}

				$_SESSION = [];
				session_destroy();
				echo "user deleted.\n";

				return "user deleted";
			}
			
		} else {
			echo "user not found.\n";
			return "user not found";
		}
	}
}

#[TestDox("Profile controller")]
class ProfileTestController extends TestCase
{
	private $db;

	private array $new_user = [
		'email' => 'tanika76@example.com',
		'username' => 'tanika76',
		'photo_path' => '',
		'password' => 'qweasd1234',
		'password_confirm' => 'qweasd1234'
	];

	private array $new_user_img = [
		'photo_profile' => [
			'name' => 'user2.png',
			'type' => 'image/png',
			'tmp_name' => __DIR__ . '/../../storage/tests/user2.png',
			'error' => UPLOAD_ERR_OK,
			'size' => 0,
		]
	];

	use DatabaseUp;

	protected function fillForm(array $formData, array $formFile)
	{
		echo "filling form...\n";
		$_POST = $formData;
		$_FILES = $formFile;
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

	protected function login(array $postData, array $fileData)
	{
		echo "generate new user..\n";
		$userGene = new HelperLogin($this->db);
		$statusCreate = $userGene->createUser($postData, $fileData);

		if ($statusCreate !== 'user created') {
			echo $statusCreate;
			die($statusCreate);
		}

		echo "user generated.\nlogin new user..\n";

		$statusLogin = $userGene->userLogin([
			'email' => $postData['email'],
			'password' => $postData['password']
		]);

		if ($statusLogin !== 'login success' && !isset($_SESSION['user_info'])) {
			echo $statusLogin;
			die($statusLogin);
		}

		return $statusLogin;
	}

	protected function logout() {
		echo "prepare logout user..\n";

		$userGene = new HelperLogin($this->db);
		$statusLogout = $userGene->logoutUser();

		if (!$statusLogout) {
			echo $statusLogout;
			die($statusLogout);
		}
	}

	#[Test] #[TestDox("Profile index page is accessible")]
	public function profile_index_page_is_accessible(): void
	{
		$this->new_user['photo_path'] = $this->UrlGenerator();

		$this->login($this->new_user, $this->new_user_img);

		$controller = new ProfileControllerStub($this->db);

		$controller->index();

		$this->assertCount(3, $controller->views);
		$this->assertSame('templates/header', $controller->views[0]['view']);
		$this->assertSame('profile/index', $controller->views[1]['view']);
		$this->assertSame('templates/footer', $controller->views[2]['view']);
		$this->assertArrayHasKey('user', $controller->views[1]['data']);
		$this->assertCount(3, $controller->views[1]['data']['user']);
	}

	#[Test] #[TestDox("Profile index page is unaccessible because user not login")]
	public function profile_index_page_is_unaccessible_because_user_not_login(): void
	{
		try {
			new ProfileControllerStub($this->db);
			$this->fail('Expected RuntimeException');
		} catch (RuntimeException $e) {
			$this->assertSame('User is not logged in', $e->getMessage());
			$this->assertTrue(isset($_SESSION['flash']));
		}
	}

	#[Test] #[TestDox("Edit profile page is accessible")]
	public function edit_profile_page_is_accessible(): void
	{
		$this->new_user['photo_path'] = $this->UrlGenerator();

		$this->login($this->new_user, $this->new_user_img);

		$user = $_SESSION['user_info'];

		$controller = new ProfileControllerStub($this->db);
		$generator = new TestUsersModel($this->db);

		$target = $generator->findId($user['user_id']);

		$controller->editProfile($target['username']);

		$this->assertCount(3, $controller->views);
		$this->assertSame('templates/header', $controller->views[0]['view']);
		$this->assertSame('profile/edit', $controller->views[1]['view']);
		$this->assertSame('templates/footer', $controller->views[2]['view']);
		$this->assertArrayHasKey('user', $controller->views[1]['data']);
		$this->assertCount(3, $controller->views[1]['data']['user']);
		$this->assertSame($this->new_user['username'], $controller->views[1]['data']['user']['username']);
	}

	#[Test] #[TestDox("Edit profile page is unaccessible because user not found")]
	public function edit_page_is_unaccessible_because_user_not_found(): void
	{
		$this->new_user['photo_path'] = $this->UrlGenerator();

		$this->login($this->new_user, $this->new_user_img);

		$controller = new ProfileControllerStub($this->db);

		$user = 'gornal774';

		$action = $controller->editProfile($user);

		$this->assertTrue($action === 'user not found');
	}

	#[Test] #[TestDox("Success update user email")]
	public function success_update_user_email(): void {
		$this->new_user['photo_path'] = $this->UrlGenerator();

		$this->login($this->new_user, $this->new_user_img);

		$user = $_SESSION['user_info'];

		$controller = new ProfileControllerStub($this->db);
		$generator = new TestUsersModel($this->db);

		$target = $generator->findId($user['user_id']);

		$new_data_user = [
			'email' => 'tester@gmail.com',
			'username' => $this->new_user['username'],
			'photo_path' => '',
			'old_photo_profile' => $generator->findUsername($target['username'], ['photo_profile']),
			'password' => '',
			'password_confirm' => ''
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
		$this->fillForm($new_data_user, $new_data_user_img);
		echo "prepare storing...\n";

		$action = $controller->updateProfile($target['username']);

		$this->assertTrue($action === 'user updated');

		$new_data = $generator->findEmail($new_data_user['email']);

		$this->assertNotSame($this->new_user['email'], $new_data['email']);
	}
	
	#[Test]#[TestDox("Success update user username")]
	public function success_update_user_username(): void {
		$this->new_user['photo_path'] = $this->UrlGenerator();

		$this->login($this->new_user, $this->new_user_img);

		$user = $_SESSION['user_info'];

		$controller = new ProfileControllerStub($this->db);
		$generator = new TestUsersModel($this->db);

		$target = $generator->findId($user['user_id']);

		$new_data_user = [
			'email' => $this->new_user['email'],
			'username' => 'javelin71',
			'photo_path' => '',
			'old_photo_profile' => $generator->findUsername($target['username'], ['photo_profile']),
			'password' => '',
			'password_confirm' => ''
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
		$this->fillForm($new_data_user, $new_data_user_img);
		echo "prepare storing...\n";

		$action = $controller->updateProfile($target['username']);

		$this->assertTrue($action === 'user updated');

		$new_data = $generator->findEmail($new_data_user['email']);

		$this->assertNotSame($target['username'], $new_data['username']);
		$this->assertSame($new_data_user['username'], $new_data['username']);
	}
	
	#[Test] #[TestDox("Success update user photo profile")]
	public function success_update_photo_profile(): void {
		$this->new_user['photo_path'] = $this->UrlGenerator();

		$this->login($this->new_user, $this->new_user_img);

		$user = $_SESSION['user_info'];

		$controller = new ProfileControllerStub($this->db);
		$generator = new TestUsersModel($this->db);

		$target = $generator->findId($user['user_id']);
		$oldTargetImg = $generator->findUsername($target['username'], ['photo_profile']);

		$new_data_user = [
			'email' => $this->new_user['email'],
			'username' => $this->new_user['username'],
			'photo_path' => $this->UrlGenerator(),
			'old_photo_profile' => $oldTargetImg,
			'password' => '',
			'password_confirm' => ''
		];

		$new_data_user_img = [
			'photo_profile' => [
				'name' => 'user.jpg',
				'type' => 'image/jpg',
				'tmp_name' => __DIR__ . '/../../storage/tests/user.jpg',
				'error' => UPLOAD_ERR_OK,
				'size' => 5000
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->fillForm($new_data_user, $new_data_user_img);
		echo "prepare storing...\n";

		$action = $controller->updateProfile($target['username']);

		$this->assertTrue($action === 'user updated');

		$new_data = $generator->findUsername($new_data_user['username'], ['photo_profile']);

		$this->assertNotSame($oldTargetImg['photo_profile'], $new_data['photo_profile']);

		$this->assertStringStartsWith('profiles', $new_data['photo_profile']);
	}
	
	#[Test] #[TestDox("Success update user password")]
	public function success_update_user_password(): void {
		$this->new_user['photo_path'] = $this->UrlGenerator();

		$this->login($this->new_user, $this->new_user_img);

		$user = $_SESSION['user_info'];

		$controller = new ProfileControllerStub($this->db);
		$generator = new TestUsersModel($this->db);
		
		$target = $generator->findId($user['user_id']);

		$new_data_user = [
			'email' => $this->new_user['email'],
			'username' => $this->new_user['username'],
			'photo_path' => '',
			'old_photo_profile' => $generator->findUsername($target['username'], ['photo_profile']),
			'password' => 'password121',
			'password_confirm' => 'password121'
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
		$this->fillForm($new_data_user, $new_data_user_img);
		echo "prepare storing...\n";

		$action = $controller->updateProfile($target['username']);

		$this->assertTrue($action === 'user updated');
	}
	
	#[Test] #[TestDox("Success update user profile but nothing change")]
	public function success_update_user_profile_but_noting_change(): void {
		$this->new_user['photo_path'] = $this->UrlGenerator();

		$this->login($this->new_user, $this->new_user_img);

		$user = $_SESSION['user_info'];

		$controller = new ProfileControllerStub($this->db);
		$generator = new TestUsersModel($this->db);
		
		$target = $generator->findId($user['user_id']);

		$this->new_user['old_photo_profile'] = $generator->findUsername($target['username'], ['photo_profile']);

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
		$this->fillForm($this->new_user, $new_data_user_img);
		echo "prepare storing...\n";

		$action = $controller->updateProfile($target['username']);

		$this->assertTrue($action === 'user updated');

		$new_data = $generator->findUsername($target['username'], ['email', 'username', 'photo_profile']);

		$this->assertSame($this->new_user['email'], $new_data['email']);
		$this->assertSame($this->new_user['username'], $new_data['username']);
		$this->assertIsString($new_data['photo_profile']);
	}
	
	#[Test] #[TestDox("Failed update user because invalid email")]
	public function failed_update_user_because_invalid_email(): void {
		$this->new_user['photo_path'] = $this->UrlGenerator();

		$this->login($this->new_user, $this->new_user_img);

		$user = $_SESSION['user_info'];

		$controller = new ProfileControllerStub($this->db);
		$generator = new TestUsersModel($this->db);
		
		$target = $generator->findId($user['user_id']);

		$new_data_user = [
			'email' => 'tanikagmail.com',
			'username' => $this->new_user['username'],
			'photo_path' => '',
			'old_photo_profile' => $generator->findUsername($target['username'], ['photo_profile']),
			'password' => '',
			'password_confirm' => ''
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
		$this->fillForm($new_data_user, $new_data_user_img);
		echo "prepare storing...\n";

		$action = $controller->updateProfile($target['username']);

		$this->assertTrue($action === 'validation failed');

		$actual = $generator->findUsername($target['username'], ['email']);

		$this->assertFalse($actual['email'] === $new_data_user['email']);
	}
	
	#[Test] #[TestDox("Failed update user because email already use")]
	public function failed_update_user_because_email_already_use(): void {
		$this->new_user['photo_path'] = $this->UrlGenerator();

		$this->login($this->new_user, $this->new_user_img);

		$controller = new ProfileControllerStub($this->db);
		$generator = new TestUsersModel($this->db);

		$this->logout();

		$user2 = [
			'email' => 'gornal45@gmail.com',
			'username' => 'gorengan',
			'photo_path' => $this->UrlGenerator(),
			'password' => 'password212',
			'password_confirm' => 'password212'
		];

		$user2Img = [
			'photo_profile' => [
				'name' => 'user.jpg',
				'type' => 'image/jpg',
				'tmp_name' => __DIR__ . '/../../storage/tests/user.jpg',
				'error' => UPLOAD_ERR_OK,
				'size' => 4000
			]
		];

		$this->login($user2, $user2Img);
		$userId = $_SESSION['user_info'];
		$target = $generator->findId($userId['user_id']);

		$new_user_2 = [
			'email' => $this->new_user['email'],
			'username' => $user2['username'],
			'photo_path' => '',
			'old_photo_profile' => $generator->findUsername($user2['username'], ['photo_profile']),
			'password' => '',
			'password_confirm' => ''
		];

		$new_user_2_img = [
			'photo_profile' => [
				'name' => '',
				'type' => '',
				'tmp_name' => '',
				'error' => UPLOAD_ERR_NO_FILE,
				'size' => 0
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->fillForm($new_user_2, $new_user_2_img);
		echo "prepare storing...\n";

		$action = $controller->updateProfile($target['username']);

		$this->assertTrue($action === 'validation failed');

		$actual = $generator->findUsername($target['username'], ['email']);

		$this->assertFalse($actual['email'] === $new_user_2['email']);
	}
	
	#[Test] #[TestDox("Failed update user because invalid username")]
	public function failed_update_user_because_invalid_username(): void {
		$this->new_user['photo_path'] = $this->UrlGenerator();

		$this->login($this->new_user, $this->new_user_img);

		$user = $_SESSION['user_info'];

		$controller = new ProfileControllerStub($this->db);
		$generator = new TestUsersModel($this->db);
		
		$target = $generator->findId($user['user_id']);

		$new_data_user = [
			'email' => $this->new_user['email'],
			'username' => 'virnada@45',
			'photo_path' => '',
			'old_photo_profile' => $generator->findUsername($this->new_user['username'], ['photo_profile']),
			'password' => '',
			'password_confirm' => ''
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
		$this->fillForm($new_data_user, $new_data_user_img);
		echo "prepare storing...\n";

		$action = $controller->updateProfile($target['username']);

		$this->assertTrue($action === 'validation failed');

		$actual = $generator->findUsername($target['username'], ['username']);

		$this->assertFalse($actual['username'] === $new_data_user['username']);
	}
	
	#[Test] #[TestDox("Failed update user because username already use")]
	public function failed_update_user_because_username_already_use(): void {
		$this->new_user['photo_path'] = $this->UrlGenerator();

		$this->login($this->new_user, $this->new_user_img);

		$controller = new ProfileControllerStub($this->db);
		$generator = new TestUsersModel($this->db);

		$this->logout();

		$user2 = [
			'email' => 'gornal45@gmail.com',
			'username' => 'gorengan',
			'photo_path' => $this->UrlGenerator(),
			'password' => 'password212',
			'password_confirm' => 'password212'
		];

		$user2Img = [
			'photo_profile' => [
				'name' => 'user.jpg',
				'type' => 'image/jpg',
				'tmp_name' => __DIR__ . '/../../storage/tests/user.jpg',
				'error' => UPLOAD_ERR_OK,
				'size' => 4000
			]
		];

		$this->login($user2, $user2Img);
		$userId = $_SESSION['user_info'];
		$target = $generator->findId($userId['user_id']);

		$new_user_2 = [
			'email' => $user2['email'],
			'username' => $this->new_user['username'],
			'photo_path' => '',
			'old_photo_profile' => $generator->findUsername($user2['username'], ['photo_profile']),
			'password' => '',
			'password_confirm' => ''
		];

		$new_user_2_img = [
			'photo_profile' => [
				'name' => '',
				'type' => '',
				'tmp_name' => '',
				'error' => UPLOAD_ERR_NO_FILE,
				'size' => 0
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->fillForm($new_user_2, $new_user_2_img);
		echo "prepare storing...\n";

		$action = $controller->updateProfile($target['username']);

		$this->assertTrue($action === 'validation failed');

		$actual = $generator->findUsername($target['username'], ['username']);

		$this->assertFalse($actual['username'] === $new_user_2['username']);
	}
	
	#[Test] #[TestDox("Failed to update user because photo path empty but photo profile exist")]
	public function failed_update_user_because_photo_path_empty_but_photo_profile_exist(): void {
		$this->new_user['photo_path'] = $this->UrlGenerator();

		$this->login($this->new_user, $this->new_user_img);

		$user = $_SESSION['user_info'];

		$controller = new ProfileControllerStub($this->db);
		$generator = new TestUsersModel($this->db);
		
		$target = $generator->findId($user['user_id']);

		$new_data_user = [
			'email' => $this->new_user['email'],
			'username' => $this->new_user['username'],
			'photo_path' => '',
			'old_photo_profile' => $generator->findUsername($target['username'], ['photo_profile']),
			'password' => '',
			'password_confirm' => ''
		];

		$new_data_user_img = [
			'photo_profile' => [
				'name' => 'user.jpg',
				'type' => 'image/jpg',
				'tmp_name' => __DIR__ . '/../../storage/tests/user.jpg',
				'error' => UPLOAD_ERR_OK,
				'size' => 6000
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->fillForm($new_data_user, $new_data_user_img);
		echo "prepare storing...\n";

		$action = $controller->updateProfile($target['username']);

		$this->assertTrue($action === 'validation failed');
	}
	
	#[Test] #[TestDox("Failed update user because photo profile too large")]
	public function failed_update_user_because_photo_profile_too_large(): void {
		$this->new_user['photo_path'] = $this->UrlGenerator();

		$this->login($this->new_user, $this->new_user_img);

		$user = $_SESSION['user_info'];

		$controller = new ProfileControllerStub($this->db);
		$generator = new TestUsersModel($this->db);
		
		$target = $generator->findId($user['user_id']);

		$new_data_user = [
			'email' => $this->new_user['email'],
			'username' => $this->new_user['username'],
			'photo_path' => $this->UrlGenerator(),
			'old_photo_profile' => $generator->findUsername($target['username'], ['photo_profile']),
			'password' => '',
			'password_confirm' => ''
		];

		$new_data_user_img = [
			'photo_profile' => [
				'name' => 'user.jpg',
				'type' => 'image/jpg',
				'tmp_name' => __DIR__ . '/../../storage/tests/user.jpg',
				'error' => UPLOAD_ERR_OK,
				'size' => 600000
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->fillForm($new_data_user, $new_data_user_img);
		echo "prepare storing...\n";

		$action = $controller->updateProfile($target['username']);

		$this->assertTrue($action === 'validation failed');
	}
	
	#[Test] #[TestDox("Failed update user because photo profile not image")]
	public function failed_update_user_because_photo_profile_not_image(): void {
		$this->new_user['photo_path'] = $this->UrlGenerator();

		$this->login($this->new_user, $this->new_user_img);

		$user = $_SESSION['user_info'];

		$controller = new ProfileControllerStub($this->db);
		$generator = new TestUsersModel($this->db);
		
		$target = $generator->findId($user['user_id']);

		$new_data_user = [
			'email' => $this->new_user['email'],
			'username' => $this->new_user['username'],
			'photo_path' => $this->UrlGenerator(),
			'old_photo_profile' => $generator->findUsername($target['username'], ['photo_profile']),
			'password' => '',
			'password_confirm' => ''
		];

		$new_data_user_img = [
			'photo_profile' => [
				'name' => 'user.jpg',
				'type' => 'text/plain',
				'tmp_name' => __DIR__ . '/../../storage/tests/ini.txt',
				'error' => UPLOAD_ERR_OK,
				'size' => 6000
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->fillForm($new_data_user, $new_data_user_img);
		echo "prepare storing...\n";

		$action = $controller->updateProfile($target['username']);

		$this->assertTrue($action === 'validation failed');
	}
	
	#[Test] #[TestDox("Failed update user because photo path invalid")]
	public function failed_update_user_because_photo_path_invalid(): void {
		$this->new_user['photo_path'] = $this->UrlGenerator();

		$this->login($this->new_user, $this->new_user_img);

		$user = $_SESSION['user_info'];

		$controller = new ProfileControllerStub($this->db);
		$generator = new TestUsersModel($this->db);
		
		$target = $generator->findId($user['user_id']);

		$new_data_user = [
			'email' => $this->new_user['email'],
			'username' => $this->new_user['username'],
			'photo_path' => 'files/store?expires=1789534487&signature=1ee61dbbfc6df11552321a4ddf8c05aa5151d54814d34cf21bc90a3dfcfb4391',
			'old_photo_profile' => $generator->findUsername($target['username'], ['photo_profile']),
			'password' => '',
			'password_confirm' => ''
		];

		$new_data_user_img = [
			'photo_profile' => [
				'name' => 'user.jpg',
				'type' => 'image/jpg',
				'tmp_name' => __DIR__ . '/../../storage/tests/user.jpg',
				'error' => UPLOAD_ERR_OK,
				'size' => 6000
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->fillForm($new_data_user, $new_data_user_img);
		echo "prepare storing...\n";

		$action = $controller->updateProfile($target['username']);

		$this->assertTrue($action === 'validation failed');
	}
	
	#[Test] #[TestDox("Failed update user because password too short")]
	public function failed_update_user_because_password_too_short(): void {
		$this->new_user['photo_path'] = $this->UrlGenerator();

		$this->login($this->new_user, $this->new_user_img);

		$user = $_SESSION['user_info'];

		$controller = new ProfileControllerStub($this->db);
		$generator = new TestUsersModel($this->db);
		
		$target = $generator->findId($user['user_id']);

		$new_data_user = [
			'email' => $this->new_user['email'],
			'username' => $this->new_user['username'],
			'photo_path' => '',
			'old_photo_profile' => $generator->findUsername($target['username'], ['photo_profile']),
			'password' => 'password',
			'password_confirm' => 'password'
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
		$this->fillForm($new_data_user, $new_data_user_img);
		echo "prepare storing...\n";

		$action = $controller->updateProfile($target['username']);

		$this->assertTrue($action === 'validation failed');
	}
	
	#[Test] #[TestDox("Failed update user because password not match")]
	public function failed_update_user_because_password_not_match(): void {
		$this->new_user['photo_path'] = $this->UrlGenerator();

		$this->login($this->new_user, $this->new_user_img);

		$user = $_SESSION['user_info'];

		$controller = new ProfileControllerStub($this->db);
		$generator = new TestUsersModel($this->db);
		
		$target = $generator->findId($user['user_id']);

		$new_data_user = [
			'email' => $this->new_user['email'],
			'username' => $this->new_user['username'],
			'photo_path' => '',
			'old_photo_profile' => $generator->findUsername($target['username'], ['photo_profile']),
			'password' => 'password222',
			'password_confirm' => 'password212'
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
		$this->fillForm($new_data_user, $new_data_user_img);
		echo "prepare storing...\n";

		$action = $controller->updateProfile($target['username']);

		$this->assertTrue($action === 'validation failed');
	}
	
	#[Test] #[TestDox("Success delete user with articles")]
	public function success_delete_user_with_articles(): void {
		$this->new_user['photo_path'] = $this->UrlGenerator();

		$this->login($this->new_user, $this->new_user_img);

		$user = $_SESSION['user_info'];

		$controller = new ProfileControllerStub($this->db);
		$generatorArticles = new TestArticleModel($this->db);
		$generatorUsers = new TestUsersModel($this->db);

		$generatorArticles->articleGenerator($user['user_id']);

		$action = $controller->deleteUser();

		$this->assertTrue($action === 'user and article deleted');
		$this->assertNull($_SESSION['user_info']);

		$targetArticle = $generatorArticles->getByUsers($user['user_id'], 5, 0);

		$user_status = $generatorUsers->isDeleted($user['user_id']);

		$this->assertEmpty($targetArticle);
		$this->assertTrue($user_status);
	}
	
	#[Test] #[TestDox("Success delete user without articles")]
	public function success_delete_user_without_articles(): void {
		$this->new_user['photo_path'] = $this->UrlGenerator();

		$this->login($this->new_user, $this->new_user_img);

		$user = $_SESSION['user_info'];

		$controller = new ProfileControllerStub($this->db);
		$generator = new TestUsersModel($this->db);

		$action = $controller->deleteUser();

		$this->assertTrue($action === 'user deleted');
		$this->assertNull($_SESSION['user_info']);

		$user_status = $generator->isDeleted($user['user_id']);

		$this->assertTrue($user_status);
	}
	
	#[Test] #[TestDox("Failed delete user because user not found")]
	public function failed_delete_user_because_user_not_found(): void {
		$this->new_user['photo_path'] = $this->UrlGenerator();

		$this->login($this->new_user, $this->new_user_img);

		$user = $_SESSION['user_info'];
		$_SESSION['user_info']['user_id'] = 2;

		$controller = new ProfileControllerStub($this->db);
		$generator = new TestUsersModel($this->db);

		$action = $controller->deleteUser();

		$this->assertTrue($action === 'user not found');
		$this->assertNotSame($user['user_id'], $_SESSION['user_info']['user_id']);
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
