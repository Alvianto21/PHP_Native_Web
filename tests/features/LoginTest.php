<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/controllers/LoginController.php';
require_once __DIR__ . '/../helpers/Sessions.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/TestUsersModel.php';

use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class LoginControllerStub extends LoginController
{
	public function __construct(private ?PDO $db = null)
	{
		parent::__construct();
	}
	public array $views = [];

	public function model($model)
	{
		echo "model connect to model {$model}.\n";
		return match ($model) {
			'Users' => new TestUsersModel($this->db),
		};
	}

	public function view($view, $data = [])
	{
		echo "connecting to views.\n";
		$this->views[] = ['view' => $view, 'data' => $data];
	}

	public function registerUser(array $postData, array $fileData): string
	{
		require_once __DIR__ . '/../../app/request/Validator.php';
		require_once __DIR__ . '/../../app/request/UploadImage.php';

		$validator = new Validator();
		$uploader = new UploadImage();

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

		if ($validator->validate($data, $rules)) {
			if (!empty($data['photo_path'])) {
				$checkUrl = $validator->validateSignUrl($data['photo_path']);

				if ($checkUrl !== true) {
					echo $checkUrl;
					return "photo URL validation failed";
				}
			}

			if ($data['photo_profile']['error'] !== UPLOAD_ERR_NO_FILE) {
				$data['photo_profile'] = $uploader->store($data['photo_profile'], "profiles");
			} else {
				$data['photo_profile'] = null;
			}

			$data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

			if ($this->model('Users')->create($data) > 0) {
				echo "user {$data['username']} created.\n";
				return "user created";
			} else {
				echo "create user {$data['username']} failed.\n";
				var_dump($validator->errors());
				return "create user failed";
			}
		} else {
			echo "validation failed.\n";
			var_dump($validator->errors());
			return "form validation failed";
		}
	}

	public function loginUser(array $postData): string
	{
		require_once __DIR__ . '/../../app/request/Validator.php';

		$validator = new Validator();
		$_SESSION['errors'] = [];
		$_SESSION['old_input'] = [];

		$data = [
			'email' => $validator->clearData($postData['email'] ?? ''),
			'password' => $validator->clearData($postData['password'] ?? '')
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

		if ($validator->checkData($data)) {
			return "form input empty";
		}

		if ($validator->validate($data, $rules)) {
			$user = $this->model('Users')->findEmail(filter_var($data['email'], FILTER_SANITIZE_EMAIL));

			if ($user && password_verify($data['password'], $user['password'])) {
				session_regenerate_id(true);
				$_SESSION['user_info'] = [
					'user_id' => $user['id'],
					'user_role' => $user['role']
				];
				echo "user {$user['username']} login success.\n";
				return "login success";
			} else {
				echo "no record user with email {$data['email']} or wrong password.\n";
				return "user not found";
			}
		} else {
			echo "validation failed.\n";
			var_dump($validator->errors());
			return "form validation failed";
		}
	}
}

#[TestDox("Login Controller")]
class LoginTest extends TestCase
{

	private $db;

	use DatabaseUp;

	#[TestDox("Set client IP address")]
	protected function setUp(): void
	{
		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

		echo "starting sessions.\n";
		$sessionHandler = new TestSessions($this->db);
		session_set_save_handler($sessionHandler, true);
		if (!session_id()) session_start();
		echo "session started.\n";
	}

	protected function userRegister(array $formData, array $formFile)
	{
		echo "filling form...\n";
		$_POST = $formData;
		$_FILES = $formFile;
		echo "form is filled.\n";
	}

	protected function userLogin(array $formData)
	{
		echo "filling form...\n";
		$_POST = $formData;
		echo "form is filled.\n";
	}

	#[Test] #[TestDox("Login page is accessible")]
	public function login_page_is_accessible(): void
	{
		$controller = new LoginControllerStub();

		$controller->index();

		$this->assertCount(3, $controller->views);
		$this->assertSame('templates/header', $controller->views[0]['view']);
		$this->assertSame('login/index', $controller->views[1]['view']);
		$this->assertSame('templates/footer', $controller->views[2]['view']);
	}

	#[Test] #[TestDox("Register page is accessible")]
	public function register_page_is_accessible(): void
	{
		$controller = new LoginControllerStub($this->db);

		$controller->register();

		$this->assertCount(3, $controller->views);
		$this->assertSame('templates/header', $controller->views[0]['view']);
		$this->assertSame('login/register', $controller->views[1]['view']);
		$this->assertSame('templates/footer', $controller->views[2]['view']);
	}

	#[Before]
	protected function UrlGenerator(): string
	{
		$secretKey = getenv('APP_KEY') ?: 'change-me';
		$expired = time() + 300; // 5 mins
		$signature = hash_hmac('sha256', (string) $expired, $secretKey);
		return ABSOLUTURL . 'files/store?expires=' . $expired . '&sig=' . $signature;
	}

	#[Test] #[TestDox("Success register new user with photo profile")]
	public function success_register_new_user_with_photo_profile(): void
	{
		$controller = new LoginControllerStub($this->db);

		$new_user = [
			'email' => 'tanika76@example.com',
			'username' => 'tanika76',
			'photo_path' => $this->UrlGenerator(),
			'password' => 'qweasd1234',
			'password_confirm' => 'qweasd1234'
		];
		$new_user_img = [
			'photo_profile' => [
				'name' => 'user.jpg',
				'type' => 'image/jpg',
				'tmp_name' => __DIR__ . '/../../storage/tests/user.jpg',
				'error' => UPLOAD_ERR_OK,
				'size' => 4000,
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->userRegister($new_user, $new_user_img);

		echo "prepare storing...\n";

		$action = $controller->registerUser($_POST, $_FILES);

		$this->assertTrue($action === "user created");

		$stmt = $this->db->query("SELECT email, username FROM users");
		$user = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$this->assertCount(1, $user);
		$this->assertEquals($new_user['username'], $user[0]['username']);
	}

	#[Test] #[TestDox("Success register new user without photo profile")]
	public function success_register_new_user_without_photo_profile(): void
	{
		$controller = new LoginControllerStub($this->db);

		$new_user = [
			'email' => 'tanika76@example.com',
			'username' => 'tanika76',
			'photo_path' => '',
			'password' => 'qweasd1234',
			'password_confirm' => 'qweasd1234'
		];
		$new_user_img = [
			'photo_profile' => [
				'name' => '',
				'type' => '',
				'tmp_name' => '',
				'error' => UPLOAD_ERR_NO_FILE,
				'size' => 0,
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->userRegister($new_user, $new_user_img);

		echo "prepare storing...\n";

		$action = $controller->registerUser($_POST, $_FILES);

		$this->assertTrue($action === "user created");

		$stmt = $this->db->query("SELECT email, username FROM users");
		$user = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$this->assertCount(1, $user);
		$this->assertEquals($new_user['username'], $user[0]['username']);
	}

	#[Test] #[TestDox("Success register new user with photo profile empty and img path exist")]
	public function success_register_new_user_with_photo_profile_empty_and_img_path_exist(): void
	{
		$controller = new LoginControllerStub($this->db);

		$new_user = [
			'email' => 'tanika76@example.com',
			'username' => 'tanika76',
			'photo_path' => $this->UrlGenerator(),
			'password' => 'qweasd1234',
			'password_confirm' => 'qweasd1234'
		];
		$new_user_img = [
			'photo_profile' => [
				'name' => '',
				'type' => '',
				'tmp_name' => '',
				'error' => UPLOAD_ERR_NO_FILE,
				'size' => 0,
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->userRegister($new_user, $new_user_img);

		echo "prepare storing...\n";

		$action = $controller->registerUser($_POST, $_FILES);

		$this->assertTrue($action === "user created");

		$stmt = $this->db->query("SELECT email, username FROM users");
		$user = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$this->assertCount(1, $user);
		$this->assertEquals($new_user['username'], $user[0]['username']);
	}

	#[Test] #[TestDox("Failed register new user because photo profile file too large")]
	public function failed_register_new_user_because_photo_profile_file_too_large(): void
	{
		$controller = new LoginControllerStub($this->db);

		$new_user = [
			'email' => 'tanika76@example.com',
			'username' => 'tanika76',
			'photo_path' => $this->UrlGenerator(),
			'password' => 'qweasd1234',
			'password_confirm' => 'qweasd1234'
		];
		$new_user_img = [
			'photo_profile' => [
				'name' => 'user.jpg',
				'type' => 'image/jpg',
				'tmp_name' => __DIR__ . '/../../storage/tests/user.jpg',
				'error' => UPLOAD_ERR_OK,
				'size' => 4000000,
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->userRegister($new_user, $new_user_img);

		echo "prepare storing...\n";

		$action = $controller->registerUser($_POST, $_FILES);

		$this->assertTrue($action === "form validation failed");

		$stmt = $this->db->query("SELECT email, username FROM users");
		$user = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$this->assertNotEquals($new_user['username'], $user);
	}

	#[Test] #[TestDox("Failed register new user because photo profile file is not image")]
	public function failed_register_new_user_because_photo_profile_file_is_not_image(): void
	{
		$controller = new LoginControllerStub($this->db);

		$new_user = [
			'email' => 'tanika76@example.com',
			'username' => 'tanika76',
			'photo_path' => $this->UrlGenerator(),
			'password' => 'qweasd1234',
			'password_confirm' => 'qweasd1234'
		];
		$new_user_img = [
			'photo_profile' => [
				'name' => 'user.jpg',
				'type' => 'text/plain',
				'tmp_name' => __DIR__ . '/../../storage/tests/ini.txt',
				'error' => UPLOAD_ERR_OK,
				'size' => 4000,
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->userRegister($new_user, $new_user_img);

		echo "prepare storing...\n";

		$action = $controller->registerUser($_POST, $_FILES);

		$this->assertTrue($action === "form validation failed");

		$stmt = $this->db->query("SELECT email, username FROM users");
		$user = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$this->assertNotEquals($new_user['username'], $user);
	}

	#[Test] #[TestDox("Failed register new user because invalid email address")]
	public function failed_register_new_user_because_invalid_email_address(): void
	{
		$controller = new LoginControllerStub($this->db);

		$new_user = [
			'email' => 'tanika@76@example.com',
			'username' => 'tanika76',
			'photo_path' => $this->UrlGenerator(),
			'password' => 'qweasd1234',
			'password_confirm' => 'qweasd1234'
		];
		$new_user_img = [
			'photo_profile' => [
				'name' => 'user.jpg',
				'type' => 'image/jpg',
				'tmp_name' => __DIR__ . '/../../storage/tests/user.jpg',
				'error' => UPLOAD_ERR_OK,
				'size' => 4000,
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->userRegister($new_user, $new_user_img);

		echo "prepare storing...\n";

		$action = $controller->registerUser($_POST, $_FILES);

		$this->assertTrue($action === "form validation failed");

		$stmt = $this->db->query("SELECT email, username FROM users");
		$user = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$this->assertNotEquals($new_user['username'], $user);
	}

	#[Test] #[TestDox("Failed register new user because invalid username")]
	public function failed_register_new_user_because_invalid_username(): void
	{
		$controller = new LoginControllerStub($this->db);

		$new_user = [
			'email' => 'tanika76@example.com',
			'username' => 'tanika@76',
			'photo_path' => '',
			'password' => 'qweasd1234',
			'password_confirm' => 'qweasd1234'
		];
		$new_user_img = [
			'photo_profile' => [
				'name' => '',
				'type' => '',
				'tmp_name' => '',
				'error' => UPLOAD_ERR_NO_FILE,
				'size' => 0,
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->userRegister($new_user, $new_user_img);

		echo "prepare storing...\n";

		$action = $controller->registerUser($_POST, $_FILES);

		$this->assertTrue($action === "form validation failed");

		$stmt = $this->db->query("SELECT email, username FROM users");
		$user = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$this->assertNotEquals($new_user['username'], $user);
	}

	#[Test] #[TestDox("Failed register new user because password not match")]
	public function failed_register_new_user_because_password_not_match(): void
	{
		$controller = new LoginControllerStub($this->db);

		$new_user = [
			'email' => 'tanika76@example.com',
			'username' => 'tanika76',
			'photo_path' => '',
			'password' => 'qweasd1236',
			'password_confirm' => 'qweasd1235'
		];
		$new_user_img = [
			'photo_profile' => [
				'name' => '',
				'type' => '',
				'tmp_name' => '',
				'error' => UPLOAD_ERR_NO_FILE,
				'size' => 0,
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->userRegister($new_user, $new_user_img);

		echo "prepare storing...\n";

		$action = $controller->registerUser($_POST, $_FILES);

		$this->assertTrue($action === "form validation failed");

		$stmt = $this->db->query("SELECT email, username FROM users");
		$user = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$this->assertNotEquals($new_user['username'], $user);
	}

	#[Test] #[TestDox("Failed register new user because password too short")]
	public function failed_register_new_user_because_password_too_short(): void
	{
		$controller = new LoginControllerStub($this->db);

		$new_user = [
			'email' => 'tanika76@example.com',
			'username' => 'tanika76',
			'photo_path' => '',
			'password' => 'password',
			'password_confirm' => 'password'
		];
		$new_user_img = [
			'photo_profile' => [
				'name' => '',
				'type' => '',
				'tmp_name' => '',
				'error' => UPLOAD_ERR_NO_FILE,
				'size' => 0,
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->userRegister($new_user, $new_user_img);

		echo "prepare storing...\n";

		$action = $controller->registerUser($_POST, $_FILES);

		$this->assertTrue($action === "form validation failed");

		$stmt = $this->db->query("SELECT email, username FROM users");
		$user = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$this->assertNotEquals($new_user['username'], $user);
	}

	#[Test] #[TestDox("Failed register new user because img path URL invalid")]
	public function failed_register_new_user_because_image_path_URL_invalid(): void
	{
		$controller = new LoginControllerStub($this->db);

		$new_user = [
			'email' => 'tanika76@example.com',
			'username' => 'tanika76',
			'photo_path' => 'files/store?expires=1787723741&signature=2e9d87ce138526777f3e31d6d6e45196b355b0cedd1c5f20651ed7933db735ce',
			'password' => 'qweasd1234',
			'password_confirm' => 'qweasd1234'
		];
		$new_user_img = [
			'photo_profile' => [
				'name' => 'user.jpg',
				'type' => 'image/jpg',
				'tmp_name' => __DIR__ . '/../../storage/tests/user.jpg',
				'error' => UPLOAD_ERR_OK,
				'size' => 40000,
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->userRegister($new_user, $new_user_img);

		echo "prepare storing...\n";

		$action = $controller->registerUser($_POST, $_FILES);

		$this->assertTrue($action === "form validation failed");

		$stmt = $this->db->query("SELECT email, username FROM users");
		$user = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$this->assertNotEquals($new_user['username'], $user);
	}

	#[Test] #[TestDox("Failed register new user because img path empty but photo profile exist")]
	public function failed_register_new_user_because_img_path_empty_but_photo_profile_exist(): void
	{
		$controller = new LoginControllerStub($this->db);

		$new_user = [
			'email' => 'tanika76@example.com',
			'username' => 'tanika@76',
			'photo_path' => '',
			'password' => 'qweasd1234',
			'password_confirm' => 'qweasd1234'
		];
		$new_user_img = [
			'photo_profile' => [
				'name' => 'user.jpg',
				'type' => 'image/jpg',
				'tmp_name' => __DIR__ . '/../../storage/tests/user.jpg',
				'error' => UPLOAD_ERR_OK,
				'size' => 40000,
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->userRegister($new_user, $new_user_img);

		echo "prepare storing...\n";

		$action = $controller->registerUser($_POST, $_FILES);

		$this->assertTrue($action === "form validation failed");

		$stmt = $this->db->query("SELECT email, username FROM users");
		$user = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$this->assertNotEquals($new_user['username'], $user);
	}

	#[Test] #[TestDox("Success login user")]
	public function success_login_user(): void
	{
		$controller = new LoginControllerStub($this->db);

		$new_user = [
			'email' => 'tanika76@example.com',
			'username' => 'tanika76',
			'photo_path' => '',
			'password' => 'qweasd1234',
			'password_confirm' => 'qweasd1234'
		];
		$new_user_img = [
			'photo_profile' => [
				'name' => '',
				'type' => '',
				'tmp_name' => '',
				'error' => UPLOAD_ERR_NO_FILE,
				'size' => 0,
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->userRegister($new_user, $new_user_img);
		echo "prepare storing...\n";

		$controller->registerUser($_POST, $_FILES);

		$login_user = [
			'email' => $new_user['email'],
			'password' => $new_user['password']
		];

		$this->userLogin($login_user);
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_SESSION = [];
		echo "logging user...\n";

		$action = $controller->loginUser($_POST);

		$this->assertTrue($action === "login success", $action);
		$this->assertTrue(isset($_SESSION['user_info']));
		$this->assertNotEmpty($_SESSION['user_info']);
	}

	#[Test] #[TestDox("Failed login user because account not found")]
	public function failed_login_user_because_account_not_found(): void {
		$controller = new LoginControllerStub($this->db);

		$new_user = [
			'email' => 'tanika76@example.com',
			'username' => 'tanika76',
			'photo_path' => '',
			'password' => 'qweasd1234',
			'password_confirm' => 'qweasd1234'
		];
		$new_user_img = [
			'photo_profile' => [
				'name' => '',
				'type' => '',
				'tmp_name' => '',
				'error' => UPLOAD_ERR_NO_FILE,
				'size' => 0,
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->userRegister($new_user, $new_user_img);
		echo "prepare storing...\n";

		$controller->registerUser($_POST, $_FILES);

		$login_user = [
			'email' => 'tanaka7@gmaol.com',
			'password' => 'password212'
		];

		$this->userLogin($login_user);
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_SESSION = [];
		echo "logging user...\n";

		$action = $controller->loginUser($_POST);

		$this->assertTrue($action === 'user not found');
		$this->assertTrue(!isset($_SESSION['user_info']));
	}
	
	#[Test] #[TestDox("Failed login user because wrong password")]
	public function failed_login_user_because_wrong_password(): void {
		$controller = new LoginControllerStub($this->db);

		$new_user = [
			'email' => 'tanika76@example.com',
			'username' => 'tanika76',
			'photo_path' => '',
			'password' => 'qweasd1234',
			'password_confirm' => 'qweasd1234'
		];
		$new_user_img = [
			'photo_profile' => [
				'name' => '',
				'type' => '',
				'tmp_name' => '',
				'error' => UPLOAD_ERR_NO_FILE,
				'size' => 0,
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->userRegister($new_user, $new_user_img);
		echo "prepare storing...\n";

		$controller->registerUser($_POST, $_FILES);

		$login_user = [
			'email' => $new_user['email'],
			'password' => 'password212'
		];

		$this->userLogin($login_user);
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_SESSION = [];
		echo "logging user...\n";

		$action = $controller->loginUser($_POST);

		$this->assertTrue($action === 'user not found');
		$this->assertTrue(!isset($_SESSION['user_info']));
	}
	
	#[Test] #[TestDox("Failed login user because wrong email")]
	public function failed_login_user_because_wrong_email(): void {
		$controller = new LoginControllerStub($this->db);

		$new_user = [
			'email' => 'tanika76@example.com',
			'username' => 'tanika76',
			'photo_path' => '',
			'password' => 'qweasd1234',
			'password_confirm' => 'qweasd1234'
		];
		$new_user_img = [
			'photo_profile' => [
				'name' => '',
				'type' => '',
				'tmp_name' => '',
				'error' => UPLOAD_ERR_NO_FILE,
				'size' => 0,
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->userRegister($new_user, $new_user_img);
		echo "prepare storing...\n";

		$controller->registerUser($_POST, $_FILES);

		$login_user = [
			'email' => 'tanaka7@gmaol.com',
			'password' => $new_user['password']
		];

		$this->userLogin($login_user);
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_SESSION = [];
		echo "logging user...\n";

		$action = $controller->loginUser($_POST);

		$this->assertTrue($action === 'user not found');
		$this->assertTrue(!isset($_SESSION['user_info']));
	}

	#[Override]
	protected function tearDown(): void
	{
		$this->db = null;
		$_SERVER = [];
		$_POST = [];
		$_FILES = [];
		$_SESSION = [];
		session_destroy();
		parent::tearDown();
	}
}
