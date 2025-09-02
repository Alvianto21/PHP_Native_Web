<?php

class Article {	
	
	private $dbh; // database handles
	private $stmt; // statement

	// koneksi ke database
	public function __construct() {
		$config = require __DIR__ . '/../../config.php';
		$serverName = $config['server_name'];
		$username = $config['username'];
		$password = $config['password'];
		$dbName = $config['database'];

		try {
			$this->dbh = new PDO("mysql:host=$serverName;dbname=$dbName", $username, $password);
			$this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch(PDOException $e) {
			die($e->getMessage());
		}
	}

	// semua data
	public function all() {
		$this->stmt = $this->dbh->prepare("SELECT * FROM articles");
		$this->stmt->execute();
		return $this->stmt->fetchAll();
	}

	// cari data berdasarkan id
	public function find($id) {

	}

	// tambah data
	public function create() {

	}

	// update data
	public function update() {

	}
}