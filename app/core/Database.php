<?php

class Database {
	private $host;
	private $user;
	private $pass;
	private $db_name;

	private $dbh; // database handles
	private $stmt; // statement

	// koneksi ke database
	public function __construct() {
		$config = require_once __DIR__ . '/../config/config.php';
		$this->host = $config['server_name'];
		$this->user = $config['username'];
		$this->pass = $config['password'];
		$this->db_name = $config['database'];
		try {
			$this->dbh = new PDO("mysql:host=$this->host;dbname=$this->db_name", $this->user, $this->pass);
			$this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->dbh->setAttribute(PDO::ATTR_PERSISTENT, true);
		} catch(PDOException $e) {
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

	// banyak data
	public function resultSet() {
		$this->execute();
		return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	// satu data
	public function single() {
		$this->execute();
		return $this->stmt->fetch(PDO::FETCH_ASSOC);
	}
}