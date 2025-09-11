<?php

class Database {
	private $host = DB_HOST;
	private $user = DB_USER;
	private $pass = DB_PASS;
	private $db_name = DB_NAME;

	private $dbh; // database handles
	private $stmt; // statement

	// koneksi ke database
	public function __construct() {
		$dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->db_name;
		$options = [
			PDO::ATTR_PERSISTENT => true,
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
		];
		try {
			$this->dbh = new PDO($dsn, $this->user, $this->pass, $options);
		} catch(PDOException $e) {
			error_log($e->getTraceAsString());
			die($e->getMessage());
		}
	}

	// menyiapkan query
	public function query($query) {
		$this->stmt = $this->dbh->prepare($query);
	}

	// bind values
	public function bind($param, $value, $type = null) {
		if (is_null($type)) {
			switch (true) {
				case is_int($value):
					$type = PDO::PARAM_INT;
					break;
				case is_bool($value);
					$type = PDO::PARAM_BOOL;
					break;
				case is_null($value);
					$type = PDO::PARAM_NULL;
					break;
				default:
					$type = PDO::PARAM_STR;
					break;
			}
		}

		$this->stmt->bindValue($param, $value, $type);
	}

	// eksekusi statement
	public function execute() {
		$this->stmt->execute();
	}

	// keluar banyak data
	public function resultSet() {
		$this->execute();
		return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	// keluar satu data
	public function single() {
		$this->execute();
		return $this->stmt->fetch(PDO::FETCH_ASSOC);
	}

	// perubahan data
	public function rowCount() {
		return $this->stmt->rowCount();
	}

	// fetch colomn
	public function coloms() {
		$this->execute();
		return (int) $this->stmt->fetchColumn();
	}
}