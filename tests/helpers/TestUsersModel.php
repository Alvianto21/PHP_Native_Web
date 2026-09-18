<?php

class TestUsersModel
{
	public function __construct(private PDO $db)
	{
		//
	}

	protected $table = 'users';

	protected $usersSeeder = [
		'data_1' => [
			'email' => 'abc@gmail.com',
			'username' => 'abc43'
		]
	];

	protected $usersImgSeeder = [];

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

		echo "creating...\n";

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

		echo "creating...\n";

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

	public function findEmail(string $data)
	{
		$query = 'SELECT id, email, username, password, role FROM ' . $this->table . ' WHERE email = :email AND is_deleted = 0';

		echo "retrieving data from database...\n";

		$this->query($query);

		$this->bind('email', $data);

		$this->stmt->execute();

		return $this->stmt->fetch(PDO::FETCH_ASSOC);
	}

	public function findId(int $id)
	{
		$query = "SELECT username FROM {$this->table} WHERE id = :id AND is_deleted = 0";

		echo "retrieving data from database...\n";
		$this->query($query);

		$this->bind('id', $id, PDO::PARAM_INT);

		$this->stmt->execute();

		return $this->stmt->fetch(PDO::FETCH_ASSOC);
	}

	public function findUsername(string $username, array $columns = ['*'])
	{
		$fields = implode(', ', $columns);
		$query = "SELECT {$fields} FROM {$this->table} WHERE username = :username AND is_deleted = 0";

		echo "retrieving data from database...\n";
		$this->query($query);

		$this->bind('username', $username);

		$this->stmt->execute();

		return $this->stmt->fetch(PDO::FETCH_ASSOC);
	}

	public function findUser(int $user_id) {
		$query = "SELECT id, photo_profile FROM {$this->table} WHERE id = :user_id AND is_deleted = 0";

		echo "retrieving data from database...\n";
		$this->query($query);

		$this->bind('user_id', $user_id, PDO::PARAM_INT);

		$this->stmt->execute();

		return $this->stmt->fetch(PDO::FETCH_ASSOC);
	}

	public function isEmailExist(string $email)
	{
		$query = "SELECT 1 FROM {$this->table} WHERE email = :email LIMIT 1";

		echo "searching data...\n";
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
		$this->query($query);

		// Bind data
		$this->bind('username', $username);
		$this->bind('id', $user_id);

		$this->stmt->execute();

		return $this->stmt->fetchColumn() !== false;
	}

	public function isDeleted(int $user_id) {
		$query = "SELECT id, is_deleted FROM {$this->table} WHERE id = :user_id AND is_deleted = 1";

		echo "retrieving data from database...\n";
		$this->query($query);

		$this->bind('user_id', $user_id, PDO::PARAM_INT);

		return $this->stmt->execute() ? true : false;
	}

	public function show(int $user_id)
	{
		$query = "SELECT email, username, photo_profile FROM {$this->table} WHERE id = :user_id AND is_deleted = 0";

		echo "retrieving data from database...\n";
		$this->query($query);

		$this->bind('user_id', $user_id, PDO::PARAM_INT);

		$this->stmt->execute();

		return $this->stmt->fetch(PDO::FETCH_ASSOC);
	}

	public function update(array $data, array $columns, string $username)
	{
		$fields = implode(', ', $columns);
		$query = "UPDATE {$this->table} SET {$fields} WHERE username = :current_username";

		echo "updating data..\n";
		$this->query($query);

		foreach ($data as $key => $value) {
			$this->bind($key, $value);
		}
		$this->bind('current_username', $username);

		return $this->stmt->execute() ? 1 : 0;
	}

	public function delete(int $user_id) {
		$query = "UPDATE {$this->table} SET is_deleted = 1, photo_profile = NULL WHERE id = :user_id";

		echo "setting data as deleted...\n";
		$this->query($query);

		$this->bind('user_id', $user_id, PDO::PARAM_INT);

		$this->stmt->execute();

		return $this->stmt->rowCount();
	}
}
