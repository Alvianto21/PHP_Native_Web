<?php

class Users {
	private $table = "users";
	private $db;

	// koneksi ke database
	public function __construct() {
		$this->db = new Database();
	}

	/**
	 * Create user data
	 * @param array $data - input data
	 * @return int
	 */
	public function create(array $data) {
		// set query
		$query = "INSERT INTO " . $this->table . " (email, username, photo_profile, role,  password) VALUES (:email, :username, :photo_profile, :role, :password)";

		// insert user
		$this->db->query($query);

		// clear email
		$data['email'] = filter_var($data['email'], FILTER_SANITIZE_EMAIL);

		if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) return 0;

		// bind data
		$this->db->bind('email', $data['email']);
		$this->db->bind('username', $data['username']);
		$this->db->bind('photo_profile', $data['photo_profile']);
		$this->db->bind('role', "user");
		$this->db->bind('password', $data['password']);

		// execute
		$this->db->execute();

		return $this->db->rowCount();	
	}	
	
	/**
	 * Find user by email
	 * @param string $data - user email
	 * @return array|bool - user data
	 */
	public function findEmail(string $data) {
		// set query
		$query = 'SELECT * FROM ' . $this->table . ' WHERE email = :email';

		// find user
		$this->db->query($query);

		$data = filter_var($data, FILTER_SANITIZE_EMAIL);

		// bind data
		$this->db->bind('email', $data);

		// execute
		$this->db->execute();

		return $this->db->single();
	}

	/**
	 * Show user profile.
	 * @param int $user_id User id from session.
	 * @return array|bool User data.
	 */
	public function show(int $user_id) {
		$query = "SELECT email, username, photo_profile FROM " . $this->table . " WHERE id = :user_id";

		// Find user
		$this->db->query($query);

		// Bind data
		$this->db->bind('user_id', $user_id, PDO::PARAM_INT);

		// Execute
		$this->db->execute();

		return $this->db->single();
	}
}