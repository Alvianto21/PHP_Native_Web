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
	 * Get all articles frm a user except is deleted.
	 * @param int $user_id - User id from sessions.
	 * @return array[] - Article data.
	 */
	public function getByUsers(int $user_id) {
		$query = "SELECT articles.title, articles.photo_cover, articles.slug, articles.body FROM `{$this->table}` JOIN `{$this->tableRelations}` ON articles.user_id = users.id WHERE users.id = :user_id AND is_deleted = 0";

		$this->db->query($query);

		$this->db->bind('user_id', $user_id);
		
		return $this->db->resultSet();
	}

	/**
	 * SHow all data with paginator.
	 * @param int $limit Max data output.
	 * @param int $offset Start data position.
	 * @return array[] array data.
	 */
	public function paginator(int $limit, int $offset) {
		// set query
		$query = "SELECT articles.title, articles.photo_cover, articles.slug, articles.body, users.username AS author FROM " . $this->table . " JOIN " . $this->tableRelations . " ON articles.user_id = users.id WHERE is_deleted = 0 ORDER BY articles.id DESC LIMIT :limit OFFSET :offset";

		// get data
		$this->db->query($query);

		// bind data
		$this->db->bind('limit', $limit, PDO::PARAM_INT);
		$this->db->bind('offset', $offset, PDO::PARAM_INT);

		return $this->db->resultSet();
	}

	/**
	 * Count article data.
	 * @return int total articles.
	 */
	public function count() {
		$query = "SELECT COUNT(*) AS total FROM " . $this->table .  " WHERE is_deleted = 0";
		$this->db->query($query);
		
		return $this->db->coloms();
	}

	/**
	 * Check whether an article slug already exists.
	 *
	 * @param string $slug Slug to search for.
	 * @return bool True when the slug exists, otherwise false.
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
	 * Find article by slug.
	 * @param string $slug slug Title.
	 * @return array|bool article data.
	 */
	public function findArticle(string $slug) {
		$query = "SELECT articles.title, articles.photo_cover, articles.slug, articles.body, users.username AS author FROM " . $this->table . " JOIN " . $this->tableRelations . " ON articles.user_id = users.id WHERE slug = :slug AND is_deleted = 0";

		// Set query
		$this->db->query($query);

		// Bind data
		$this->db->bind('slug', $slug);

		// Return data
		return $this->db->single();
	}

	/**
	 * Find article by user id and slug.
	 * @param string $slug Slug title.
	 * @param int $user_id user id from session.
	 * @return array|bool article data.
	 */
	public function findArticleUser(string $slug, int $user_id) {
		$query = "SELECT title, slug, photo_cover, body FROM " . $this->table . " WHERE slug = :slug AND user_id = :user_id AND is_deleted = 0";

		// Set query
		$this->db->query($query);

		// Bind data
		$this->db->bind('slug', $slug);
		$this->db->bind('user_id', $user_id);

		// Return data
		return $this->db->single();
	}

	/**
	 * Create new article.
	 * @param array $data - Form data.
	 * @param int  $user_id - User id from session.
	 * @return int
	 */
	public function create(array $data, int $user_id) {		
		// set query
		$query = "INSERT INTO " . $this->table . " (title, slug, photo_cover, user_id, body) VALUES (:title, :slug, :photo_cover, :user_id, :body)";

		// insert data
		$this->db->query($query);

		// bind data
		$this->db->bind("title", $data['title']);
		$this->db->bind("slug", $data['slug']);
		$this->db->bind('photo_cover', $data['photo_cover']);
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