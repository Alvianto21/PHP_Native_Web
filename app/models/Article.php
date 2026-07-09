<?php

class Article {	
	
	private $table = 'articles';
	private $tableRelations = 'users';
	private $db; 

	// koneksi ke database
	public function __construct() {
		$this->db = new Database();
	}

	/**
	 * Get all articles frm a user except is deleted
	 * @param int $user_id - User id from sessions
	 * @return array[] - Article data
	 */
	public function getByUsers(int $user_id) {
		$query = "SELECT articles.title, articles.photo_cover, articles.slug, articles.body FROM `{$this->table}` JOIN `{$this->tableRelations}` ON articles.user_id = users.id WHERE users.id = :user_id AND is_deleted = 0";

		$this->db->query($query);

		$this->db->bind('user_id', $user_id);
		
		return $this->db->resultSet();
	}

	// semua data dengan paginator
	public function paginator($limit, $offset) {
		// set query
		$query = "SELECT * FROM " . $this->table . " ORDER BY id DESC LIMIT :offset, :limit";

		// get data
		$this->db->query($query);

		// bind data
		$limit = (int)$limit;
		$offset = (int)$offset;
		$this->db->bind('limit', $limit, PDO::PARAM_INT);
		$this->db->bind('offset', $offset, PDO::PARAM_INT);

		return $this->db->resultSet();
	}

	// hitung data
	public function count() {
		$this->db->query('SELECT COUNT(*) AS total FROM articles');
		return $this->db->coloms();
	}

	/**
	 * Check whether an article slug already exists.
	 *
	 * @param string $slug Slug to search for
	 * @return bool True when the slug exists, otherwise false
	 */
	public function findSlug(string $slug) {
		$this->db->query('SELECT 1 FROM ' . $this->table . ' WHERE slug = ? LIMIT 1');
		$this->db->bind(1, $slug);

		$result = $this->db->getResult();

		if (is_object($result) && method_exists($result, 'num_rows')) {
			return $result->num_rows > 0;
		}

		return !empty($result);
	}

	/**
	 * Create new article
	 * @param array $data - Form data
	 * @param int  $user_id - User id from session
	 * @return int
	 */
	public function create(array $data, int $user_id) {		
		// set query
		$query = "INSERT INTO " . $this->table . " (title, slug, user_id, body) VALUES (:title, :slug, :user_id, :body)";

		// insert data
		$this->db->query($query);

		// bind data
		$this->db->bind("title", $data['title']);
		$this->db->bind("slug", $data['slug']);
		$this->db->bind("user_id", $user_id);
		$this->db->bind("body", $data['body']);

		// execute
		$this->db->execute();

		// check if have added data
		return $this->db->rowCount();
	}

	// update data
	public function update($data) {
		// set query
		$query = "UPDATE " . $this->table . " SET title=:title, slug=:slug, author=:author, body=:body WHERE id=:id";

		// update data
		$this->db->query($query);

		// bind data
		$this->db->bind("title", $data['title']);
		$this->db->bind("slug", $data['slug']);
		$this->db->bind("author", $data['author']);
		$this->db->bind("body", $data['body']);
		$this->db->bind("id", $data['id']);

		// eksekusi
		$this->db->execute();

		// cek apakah ada data yang berubah
		return $this->db->rowCount();
	}

	// delete data
	public function delete($id) {
		// set query
		$query = "DELETE FROM " . $this->table . " WHERE id=:id";

		// delete data
		$this->db->query($query);

		// bind data
		$this->db->bind("id", $id);

		// eksekusi
		$this->db->execute();

		// cek apakah ada data yang berubah
		return $this->db->rowCount();
	}
}