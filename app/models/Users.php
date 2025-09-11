<?php

class Users {
	private $table = "users";
	private $db;

	// koneksi ke database
	public function __construct() {
		$this->db = new Database();
	}

	// user baru
	public function create($data) {
		// set query
		$query = "INSERT INTO " . $this->table . " (email, password) VALUES (:email, :password)";

		// insert user
		$this->db->query($query);

		// bind data
		$this->db->bind('email', $data['email']);
		$this->db->bind('password', $data['password']);

		// eksekusi
		$this->db->execute();

		return $this->db->rowCount();
		
	}	
	
	// cari user
	public function find(array $data) {
		$email = $data['email'];
		$password = $data['password'];

		// set query
		$query = 'SELECT * FROM ' . $this->table . 'WHERE email = :email AND password = :password';

		// find user
		$this->db->query($query);

		// bind data
		$this->db->bind('email', $email, PDO::PARAM_STR);
		$this->db->bind('password', $password, PDO::PARAM_STR);

		// eksekusi
		$this->db->execute();

		return $this->db->single();
	}
}