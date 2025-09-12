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

		// clear email
		$data['email'] = filter_var($data['email'], FILTER_SANITIZE_EMAIL);

		if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) return 0;

		// bind data
		$this->db->bind('email', $data['email']);
		$this->db->bind('password', $data['password']);

		// eksekusi
		$this->db->execute();

		return $this->db->rowCount();
		
	}	
	
	// cari user
	public function find($data) {
		// set query
		$query = 'SELECT * FROM ' . $this->table . ' WHERE email = :email';

		// find user
		$this->db->query($query);

		$data = filter_var($data, FILTER_SANITIZE_EMAIL);

		// bind data
		$this->db->bind('email', $data);

		// eksekusi
		$this->db->execute();

		return $this->db->single();
	}
}