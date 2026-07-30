<?php

/**
 * Simple PDO-based database helper.
 *
 * Provides basic query preparation, binding and fetch utilities used by the
 * application's models.
 */
class Database {
	/** @var string */
	private $host;

	/** @var string */
	private $user;

	/** @var string */
	private $pass;

	/** @var string */
	private $db_name;

	/** @var \PDO Database handle instance */
	private $dbh; // database handles

	/** @var \PDOStatement Current prepared statement */
	private $stmt; // statement

	/**
	 * Create a new PDO connection using constants defined in config.
	 *
	 * @throws \PDOException On connection failure
	 */
	public function __construct() {
		// Load database config
		$config = require __DIR__ . '/../config/database.php';

		$this->host = $config['DB_HOST'];
		$this->user = $config['DB_USER'];
		$this->pass = $config['DB_PASSWORD'];
		$this->db_name = $config['DB_DATABASE'];
		
		$dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->db_name;
		$options = [
			PDO::ATTR_PERSISTENT => false,
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
		];
		try {
			$this->dbh = new PDO($dsn, $this->user, $this->pass, $options);
		} catch(PDOException $e) {
			error_log($e->getTraceAsString());
			die($e->getMessage());
		}
	}

	/**
	 * Prepare an SQL query for execution.
	 *
	 * @param string $query SQL query with placeholders
	 * @return void
	 */
	public function query($query) {
		$this->stmt = $this->dbh->prepare($query);
	}

	/**
	 * Bind a value to a parameter in the current statement.
	 *
	 * @param string|int $param Parameter identifier
	 * @param mixed $value Value to bind
	 * @param int|null $type PDO::PARAM_* type constant or null to infer
	 * @return void
	 */
	public function bind($param, $value, $type = null) {
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
	public function multiBind(array $bindings) {
		foreach($bindings as $binding) {
			$param = $binding[0];
			$value = $binding[1];
			$type = $binding[3] ?? null;
			$this->bind($param, $value, $type);
		}
	}

	/**
	 * Execute the prepared statement.
	 *
	 * @return bool
	 */
	public function execute() {
		return $this->stmt->execute();
	}

	/**
	 * Execute the prepared statement and return the result set when available.
	 *
	 * @return mixed
	 */
	public function getResult() {
		$this->execute();

		if (method_exists($this->stmt, 'get_result')) {
			return $this->stmt->get_result();
		}

		return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * Fetch all rows as an associative array.
	 *
	 * @return array<int, array<string,mixed>>
	 */
	public function resultSet() {
		$this->execute();
		return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * Fetch a single row as an associative array.
	 *
	 * @return array<string,mixed>|false
	 */
	public function single() {
		$this->execute();
		return $this->stmt->fetch(PDO::FETCH_ASSOC);
	}

	/**
	 * Get the number of affected rows from the last statement.
	 *
	 * @return int
	 */
	public function rowCount() {
		return $this->stmt->rowCount();
	}

	/**
	 * Fetch a single column from the next row of the result set and cast to int.
	 *
	 * @return int
	 */
	public function coloms() {
		$this->execute();
		return (int) $this->stmt->fetchColumn();
	}
}