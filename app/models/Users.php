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
		$this->db->multiBind([
			['email', $data['email']],
			['username', $data['username']],
			['photo_profile', $data['photo_profile']],
			['password', $data['password']],
			['role', 'user']
		]);

		// execute
		$this->db->execute();

		return $this->db->rowCount();	
	}	
	
	/**
	 * Find user by email for login.
	 * @param string $data User email.
	 * @return array|bool User data.
	 */
	public function findEmail(string $data) {
		// set query
		$query = 'SELECT id, email, username, password FROM ' . $this->table . ' WHERE email = :email AND is_deleted = 0';

		// find user
		$this->db->query($query);

		// bind data
		$this->db->bind('email', $data);

		// execute
		$this->db->execute();

		return $this->db->single();
	}

	/**
	 * Find user by username.
	 * Return user data if any.
	 * @param string $username User's username
	 * @param array $columns Columns to be selected.
	 * All columns is default if not filled.
	 * Example: $columns = ['email', 'username'].
	 * @return array|bool User data
	 */
	public function findUsername(string $username, array $columns = ['*']) {
		// Set columns
		$fields = implode(', ', $columns);
		$query = "SELECT {$fields} FROM " . $this->table . " WHERE username = :username AND is_deleted = 0";

		// Find user
		$this->db->query($query);

		// Bind data
		$this->db->bind('username', $username);

		// Execute
		$this->db->execute();

		return $this->db->single();
	}

	/**
	 * Find user by id and username.
	 * @param int $user_id User id from sessions.
	 * @return bool Return true if exist.
	 */
	public function findUser(int $user_id) {
		$query = "SELECT id, photo_profile FROM " . $this->table . " WHERE id = :user_id AND is_deleted = 0";

		// Find user
		$this->db->query($query);

		// Bind params
		$this->db->bind('user_id', $user_id);

		// Execute
		$this->db->execute();

		return $this->db->single();
	}

	/**
	 * Show user profile.
	 * @param int $user_id User id from session.
	 * @return array|bool User data.
	 */
	public function show(int $user_id) {
		$query = "SELECT email, username, photo_profile FROM " . $this->table . " WHERE id = :user_id AND is_deleted = 0";

		// Find user
		$this->db->query($query);

		// Bind data
		$this->db->bind('user_id', $user_id, PDO::PARAM_INT);

		// Execute
		$this->db->execute();

		return $this->db->single();
	}

	/**
	 * Update user.
	 * @param array $data Form data
	 * @param array $columns Columns to be selected.
	 * @param string $username Username param from URL.
	 * @return int
	 */
	public function update(array $data, array $columns, string $username) {
		$fields = implode(', ', $columns);
		$query = "UPDATE " . $this->table . " SET " . $fields . " WHERE username = :username";

		// Prepare query
		$this->db->query($query);

		// Bind data
		foreach($data as $key => $value) {
			$this->db->bind($key, $value);
		}

		$this->db->bind('username', $username);

		// Execute and return
		return $this->db->execute() ? 1 : 0;
	}

	/**
	 * Delete user.
	 * @param int $user_id User id from sessions.
	 * @return int
	 */
	public function delete(int $user_id) {
		$query = "UPDATE " . $this->table . " SET is_deleted = 1, photo_profile = NULL WHERE id = :user_id";

		// Delete user
		$this->db->query($query);

		// Bind data
		$this->db->bind('user_id', $user_id, PDO::PARAM_INT);

		// Execute
		$this->db->execute();

		return $this->db->rowCount();
	}
}