<?php 

require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/controllers/LoginController.php';
require_once __DIR__ . '/../../app/request/Validator.php';
require_once __DIR__ . '/../../app/request/UploadImage.php';
require_once __DIR__ . '/TestUsersModel.php';

class HelperLogin extends LoginController {
	public function __construct(private ?PDO $db = null)
	{
		parent::__construct();
	}
	
	#[Override]
	public function model($model)
	{
		return new TestUsersModel($this->db);
	}

	public function createUser(array $formData, array $fileData): string {
		$_POST = $formData;
		$_FILES = $fileData;

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$validator = new Validator();
		$uploader = new UploadImage();
		
		$data = [
			'email' => $validator->clearData($_POST['email'] ?? ''),
			'username' => $validator->clearData($_POST['username'] ?? ''),
			'photo_profile' => $_FILES['photo_profile'] ?? '',
			'photo_path' => $_POST['photo_path'] ?? '',
			'password' => $validator->clearData($_POST['password'] ?? ''),
			'password_confirm' => $_POST['password_confirm'] ?? ''
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
	
	public function createAdminUser(array $formData, array $fileData): string {
		$_POST = $formData;
		$_FILES = $fileData;

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$validator = new Validator();
		$uploader = new UploadImage();
		
		$data = [
			'email' => $validator->clearData($_POST['email'] ?? ''),
			'username' => $validator->clearData($_POST['username'] ?? ''),
			'photo_profile' => $_FILES['photo_profile'] ?? '',
			'photo_path' => $_POST['photo_path'] ?? '',
			'password' => $validator->clearData($_POST['password'] ?? ''),
			'password_confirm' => $_POST['password_confirm'] ?? ''
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

			if ($this->model('Users')->createAdmin($data) > 0) {
				echo "user admin {$data['username']} created.\n";
				return "user admin created";
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
	

	public function userLogin(array $formData): string {
		$validator = new Validator();

		if ($_SERVER['REQUEST_METHOD'] !== "POST") {
			return "method not allowed";
		}
		
		$data = [
			'email' => $validator->clearData($formData['email'] ?? ''),
			'password' => $validator->clearData($formData['password'] ?? '')
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
	
	public function logoutUser(): string|bool {
		if (!isset($_SESSION['user_info'])) {
			return "Unauthorized";
		}

		session_unset();
		session_destroy();

		return true;
	}
	
}