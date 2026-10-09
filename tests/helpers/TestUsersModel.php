<?php

class TestUsersModel
{
	public function __construct(private PDO $db)
	{
		//
	}

	private function pathGenerator()
	{
		$secretKey = getenv('APP_KEY') ?: 'change-me';
		$expired = time() + 300; // 5 mins
		$signature = hash_hmac('sha256', (string) $expired, $secretKey);
		return ABSOLUTURL . 'files/store?expires=' . $expired . '&sig=' . $signature;
	}

	protected $table = 'users';

	protected $usersSeeder = [
		'data_1' => [
			'email' => 'abc@gmail.com',
			'username' => 'abc43',
			'photo_path' => '',
			'password' => 'abc43abc34',
			'password_confirm' => 'abc43abc34'
		],
		'data_2' => [
			'email' => 'kurnia@example.com',
			'username' => 'kutnia5543',
			'photo_path' => '',
			'password' => 'kurnia99kurnia',
			'password_confirm' => 'kurnia99kurnia'
		],
		'data_3' => [
			'email' => 'tempes69@yahoo.com',
			'username' => 'tempes69',
			'photo_path' => '',
			'password' => 'tempestempa69',
			'password_confirm' => 'tempestempa69'
		],
		'data_4' => [
			'email' => 'kaliandra@gmail.com',
			'username' => 'kaliandra5571',
			'photo_path',
			'password' => 'kaliandraakiandra',
			'password_confirm' => 'kaliandrakaliandra'
		],
		'data_5' => [
			'email' => 'barbariandri@example.com',
			'username' => 'barbariandri',
			'photo_path' => '',
			'password' => 'barbariang',
			'password_confirm' => 'barbariang'
		]
	];

	protected $usersImgSeeder = [
		'data_1' => [
			'photo_profile' => [
				'name' => '',
				'type' => '',
				'tmp_name' => '',
				'error' => UPLOAD_ERR_NO_FILE,
				'size' => 0
			]
		],
		'data_2' => [
			'photo_profile' => [
				'name' => 'users.jpg',
				'type' => '',
				'tmp_name' => '',
				'error' => UPLOAD_ERR_OK,
				'size' => 0
			]
		],
		'data_3' => [
			'photo_profile' => [
				'name' => '',
				'type' => '',
				'tmp_name' => '',
				'error' => UPLOAD_ERR_NO_FILE,
				'size' => 0
			]
		],
		'data_4' => [
			'photo_profile' => [
				'name' => 'users.jpg',
				'type' => '',
				'tmp_name' => '',
				'error' => UPLOAD_ERR_OK,
				'size' => 0
			]
		],
		'data_5' => [
			'photo_profile' => [
				'name' => '',
				'type' => '',
				'tmp_name' => '',
				'error' => UPLOAD_ERR_NO_FILE,
				'size' => 0
			]
		]
	];

	private ?FilesGenerator $fileGenerator = null;

	/** @var \PDOStatement Current prepared statement */
	private $stmt;

	/**
	 * Prepare an SQL query for execution.
	 *
	 * @param string $query SQL query with placeholders
	 * @return void
	 */
	public function query($query)
	{
		$this->stmt = $this->db->prepare($query);
	}

	/**
	 * Bind a value to a parameter in the current statement.
	 *
	 * @param string|int $param Parameter identifier
	 * @param mixed $value Value to bind
	 * @param int|null $type PDO::PARAM_* type constant or null to infer
	 * @return void
	 */
	public function bind($param, $value, $type = null)
	{
		if (is_null($type)) {
			switch (true) {
				case is_int($value):
					$type = PDO::PARAM_INT;
					break;
				case is_bool($value):
					$type = PDO::PARAM_BOOL;
					break;
				case is_null($value):
					$type = PDO::PARAM_NULL;
					break;
				default:
					$type = PDO::PARAM_STR;
					break;
			}
		}

		$this->stmt->bindValue($param, $value, $type);
	}

	/**
	 * Same as bind function but can accept multiple params and values simultaneously.
	 * Example [['name', $name]].
	 * Example [['name', $name, PDO::PARAM_*]]
	 * @param array $bindings The data will be bind.
	 * @return void
	 */
	public function multiBind(array $bindings)
	{
		foreach ($bindings as $binding) {
			$param = $binding[0];
			$value = $binding[1];
			$type = $binding[2] ?? null;
			$this->bind($param, $value, $type);
		}
	}

	public function create(array $data)
	{
		$query = "INSERT INTO " . $this->table . " (email, username, photo_profile, role,  password) VALUES (:email, :username, :photo_profile, :role, :password)";

		echo "creating user...\n";

		$this->query($query);

		// clear email
		$data['email'] = filter_var($data['email'], FILTER_SANITIZE_EMAIL);

		if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) return 0;

		$this->multiBind([
			['email', $data['email']],
			['username', $data['username']],
			['photo_profile', $data['photo_profile']],
			['password', $data['password']],
			['role', 'user']
		]);

		$this->stmt->execute();

		return $this->stmt->rowCount();
	}

	public function createAdmin(array $data)
	{
		$query = "INSERT INTO " . $this->table . " (email, username, photo_profile, role,  password) VALUES (:email, :username, :photo_profile, :role, :password)";

		echo "creating user admin...\n";

		$this->query($query);

		// clear email
		$data['email'] = filter_var($data['email'], FILTER_SANITIZE_EMAIL);

		if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) return 0;

		$this->multiBind([
			['email', $data['email']],
			['username', $data['username']],
			['photo_profile', $data['photo_profile']],
			['password', $data['password']],
			['role', 'admin']
		]);

		$this->stmt->execute();

		return $this->stmt->rowCount();
	}

	public function usersGenerator(): string
	{
		require_once __DIR__ . '/../../app/request/UploadImage.php';
		require_once __DIR__ . '/../helpers/filesGenerator.php';

		$_POST = $this->usersSeeder;
		$_FILES = $this->usersImgSeeder;
		$uploader = new UploadImage();
		$this->fileGenerator = new FilesGenerator();

		(int) $success = 0;
		(int) $failed = 0;

		foreach ($_POST as $index => $data) {
			$formFile = $_FILES[$index]['photo_cover'] ?? null;
			$binaryData = $this->fileGenerator->dataRandom();

			$tmpFile = $this->fileGenerator->createTempFile($binaryData['type'], $binaryData['data']);

			try {
				if ($formFile['error'] === UPLOAD_ERR_OK) {
					$formFile['type'] = $binaryData['type'] === 'jpg' ? 'image/jpg' : 'image/png';
					$formFile['tmp_name'] = $tmpFile;
					$formFile['size'] = filesize($tmpFile);
					$data['photo_profile'] = $uploader->store($formFile, 'profiles');
				} elseif ($formFile['error'] === UPLOAD_ERR_NO_FILE) {
					$data['photo_profile'] = null;
				}
			} finally {
				$this->fileGenerator->cleaningFiles();
			}

			$data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

			$result = $this->create($data);

			if ($result > 0) {
				$success++;
				echo "{$success} created.\n";
			} else {
				$failed++;
				echo "{$failed} create failed.\n";
			}
		}

		(int) $total = $success + $failed;
		if ($success === (int) count($this->usersSeeder)) {
			return "users created";
		} else {
			echo "only {$success} from {$total} created.";
			return "some article failed be created";
		}
	}

	public function createAdminUser(array $formData, array $fileData): string
	{
		require_once __DIR__ . '/../../app/request/UploadImage.php';

		$_POST = $formData;
		$_FILES = $fileData;
		$uploader = new UploadImage();

		if ($_FILES['photo_profile']['error'] === UPLOAD_ERR_OK) {
			$_POST['photo_profile'] = $uploader->store($_FILES['photo_profile'], 'profiles');
		} elseif ($_FILES['photo_profile']['error'] === UPLOAD_ERR_NO_FILE) {
			$_POST['photo_profile'] = null;
		}

		$_POST['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);

		$result = $this->createAdmin($_POST);

		if ($result > 0) {
			echo "user role admin created.\n";
			return "user admin created";
		} else {
			echo "failed create admin user.\n";
			return "failed create admin user";
		}
	}

	public function userRandomizer()
	{
		$target = array_rand($this->usersSeeder, 1);
		$user =  $this->usersSeeder[$target];
		return $user;
	}

	public function showAll(int $limit, int $offset)
	{
		$query = "SELECT email, username, role, COUNT(*) OVER() AS total FROM {$this->table} ORDER BY id DESC LIMIT :limit OFFSET :offset";

		echo "show all data from database...\n";
		$this->query($query);

		$this->multiBind([
			['limit', $limit, PDO::PARAM_INT],
			['offset', $offset, PDO::PARAM_INT]
		]);

		$this->stmt->execute();

		return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function findEmail(string $data)
	{
		$query = 'SELECT id, email, username, password, role FROM ' . $this->table . ' WHERE email = :email AND is_deleted = 0';

		echo "finding user data from database...\n";

		$this->query($query);

		$this->bind('email', $data);

		$this->stmt->execute();

		return $this->stmt->fetch(PDO::FETCH_ASSOC);
	}

	public function findId(int $id)
	{
		$query = "SELECT username FROM {$this->table} WHERE id = :id AND is_deleted = 0";

		echo "finding user data from database...\n";
		$this->query($query);

		$this->bind('id', $id, PDO::PARAM_INT);

		$this->stmt->execute();

		return $this->stmt->fetch(PDO::FETCH_ASSOC);
	}

	public function findUsername(string $username, array $columns = ['*'])
	{
		$fields = implode(', ', $columns);
		$query = "SELECT {$fields} FROM {$this->table} WHERE username = :username AND is_deleted = 0";

		echo "retrieving data {$username} from database...\n";
		$this->query($query);

		$this->bind('username', $username);

		$this->stmt->execute();

		return $this->stmt->fetch(PDO::FETCH_ASSOC);
	}

	public function findUsernameAdmin(string $username, array $columns = ['*'])
	{
		$fields = implode(', ', $columns);
		$query = "SELECT {$fields} FROM {$this->table} WHERE username = :username";

		echo "retrieving data {$username} from database...\n";
		$this->query($query);

		$this->bind('username', $username);

		$this->stmt->execute();

		return $this->stmt->fetch(PDO::FETCH_ASSOC);
	}

	public function findUser(int $user_id)
	{
		$query = "SELECT id, photo_profile FROM {$this->table} WHERE id = :user_id AND is_deleted = 0";

		echo "finding user data from database...\n";
		$this->query($query);

		$this->bind('user_id', $user_id, PDO::PARAM_INT);

		$this->stmt->execute();

		return $this->stmt->fetch(PDO::FETCH_ASSOC);
	}

	public function findUserAdmin(string $username)
	{
		$query = "SELECT email, username, photo_profile, role, is_deleted FROM {$this->table} WHERE username = :username";

		echo "retrieving data {$username} from database...\n";
		$this->query($query);

		$this->bind('username', $username);

		$this->stmt->execute();

		return $this->stmt->fetch(PDO::FETCH_ASSOC);
	}

	public function isEmailExist(string $email)
	{
		$query = "SELECT 1 FROM {$this->table} WHERE email = :email LIMIT 1";

		echo "searching email data...\n";
		$this->query($query);

		// Bind data
		$this->bind('email', $email);

		// Execute
		$this->stmt->execute();
		return $this->stmt->fetchColumn() !== false;
	}

	public function isEmailExistExceptId(string $email, int $user_id)
	{
		$query = "SELECT 1 FROM {$this->table} WHERE email = :email AND id != :id LIMIT 1";

		// Prep query
		echo "searching email data but ignore id...\n";
		$this->query($query);

		// Bind data
		$this->bind('email', $email);
		$this->bind('id', $user_id);

		// Execute and check whether a matching row was returned.
		$this->stmt->execute();
		return $this->stmt->fetchColumn() !== false;
	}

	public function isUsernameExist(string $username)
	{
		$query = "SELECT 1 FROM {$this->table} WHERE username = :username LIMIT 1";

		// Prep query
		echo "search username data...\n";
		$this->query($query);

		// Bind data
		$this->bind('username', $username);

		$this->stmt->execute();
		return $this->stmt->fetchColumn() !== false;
	}

	public function isUsernameExistExceptId(string $username, int $user_id)
	{
		$query = "SELECT 1 FROM {$this->table} WHERE username = :username AND id != :id LIMIT 1";

		// Prep query
		echo "search username data but ignore id...\n";
		$this->query($query);

		// Bind data
		$this->bind('username', $username);
		$this->bind('id', $user_id);

		$this->stmt->execute();

		return $this->stmt->fetchColumn() !== false;
	}

	public function isDeleted(int $user_id)
	{
		$query = "SELECT is_deleted FROM {$this->table} WHERE id = :user_id";

		echo "checking status data from database...\n";
		$this->query($query);

		$this->bind('user_id', $user_id, PDO::PARAM_INT);

		$this->stmt->execute();
		$is_deleted = $this->stmt->fetchColumn();

		return $is_deleted !== false && (int) $is_deleted === 1;
	}

	public function show(int $user_id)
	{
		$query = "SELECT email, username, photo_profile FROM {$this->table} WHERE id = :user_id AND is_deleted = 0";

		echo "retrieving data user from database...\n";
		$this->query($query);

		$this->bind('user_id', $user_id, PDO::PARAM_INT);

		$this->stmt->execute();

		return $this->stmt->fetch(PDO::FETCH_ASSOC);
	}

	public function update(array $data, array $columns, string $username)
	{
		$fields = implode(', ', $columns);
		$query = "UPDATE {$this->table} SET {$fields} WHERE username = :current_username";

		echo "updating data user..\n";
		$this->query($query);

		foreach ($data as $key => $value) {
			$this->bind($key, $value);
		}
		$this->bind('current_username', $username);

		return $this->stmt->execute() ? 1 : 0;
	}

	public function delete(int $user_id)
	{
		$query = "UPDATE {$this->table} SET is_deleted = 1, photo_profile = NULL WHERE id = :user_id";

		echo "setting data user as deleted...\n";
		$this->query($query);

		$this->bind('user_id', $user_id, PDO::PARAM_INT);

		$this->stmt->execute();

		return $this->stmt->rowCount();
	}
}
