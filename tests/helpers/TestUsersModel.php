<?php 

class TestUsersModel
{
	public function __construct(private PDO $db)
	{
		//
	}

	protected $table = 'users';

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

		echo "executing...\n";

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

	public function createAdmin(array $data) {
		$query = "INSERT INTO " . $this->table . " (email, username, photo_profile, role,  password) VALUES (:email, :username, :photo_profile, :role, :password)";

		echo "executing...\n";

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

		echo "executing...\n";

		$this->query($query);

		$this->bind('email', $data);

		$this->stmt->execute();

		return $this->stmt->fetch(PDO::FETCH_ASSOC);
	}
}