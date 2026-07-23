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
	 * Get all articles frm a user except is deleted with pagination.
	 * @param int $user_id - User id from sessions.
	 * @param int $limit Max data output.
	 * @param int $offset Start data position.
	 * @return array[] - Article data.
	 */
	public function getByUsers(int $user_id, int $limit, int $offset) {
		$query = "SELECT articles.title, articles.photo_cover, articles.slug, articles.body FROM `{$this->table}` JOIN `{$this->tableRelations}` ON articles.user_id = users.id WHERE users.id = :user_id AND is_deleted = 0 LIMIT :limit OFFSET :offset";

		$this->db->query($query);

		$this->db->bind('user_id', $user_id);
		$this->db->bind('limit', $limit, PDO::PARAM_INT);
		$this->db->bind('offset', $offset, PDO::PARAM_INT);
		
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

	/**
	 * Update article.
	 * @param array $data Form data.
	 * @param int $user_id User id from session.
	 * @return int
	 */
	public function update(array $data, int $user_id) {
		if (!$this->existsForUser($data['slug'], $user_id)) {
			return 0;
		}

		// set query
		$query = "UPDATE " . $this->table . " SET title=:title, photo_cover=:photo_cover, body=:body WHERE user_id=:user_id AND is_deleted = 0 AND slug=:slug";

		// update data
		$this->db->query($query);

		// bind data
		$this->db->bind("title", $data['title']);
		$this->db->bind('photo_cover', $data['photo_cover']);
		$this->db->bind("body", $data['body']);
		$this->db->bind("user_id", $user_id);
		$this->db->bind("slug", $data['slug']);

		// Execute
		return $this->db->execute() ? 1 : 0;
	}

	/**
	 * Update article with new slug.
	 * @param array $data Form data.
	 * @param string $newSlug New created slug.
	 * @param int $user_id User id from sessions.
	 * @return int
	 */
	public function updateTitle(array $data, string $newSlug, int $user_id) {
		if (!$this->existsForUser($data['slug'], $user_id)) {
			return 0;
		}

		$query = "UPDATE " . $this->table . " SET title=:title, slug=:newSlug, photo_cover=:photo_cover, body=:body WHERE user_id=:user_id AND is_deleted = 0 AND slug=:slug";

		$this->db->query($query);

		// bind data
		$this->db->bind("title", $data['title']);
		$this->db->bind("slug", $data['slug']);
		$this->db->bind("newSlug", $newSlug);
		$this->db->bind('photo_cover', $data['photo_cover']);
		$this->db->bind("body", $data['body']);
		$this->db->bind("user_id", $user_id);

		// Execute
		return $this->db->execute() ? 1 : 0;
	}

	public function existsForUser(string $slug, int $user_id) {
		$query = "SELECT 1 FROM " . $this->table . " WHERE slug = :slug AND user_id = :user_id AND is_deleted = 0 LIMIT 1";

		$this->db->query($query);
		$this->db->bind('slug', $slug);
		$this->db->bind('user_id', $user_id);

		$result = $this->db->getResult();

		if (is_object($result) && method_exists($result, 'num_rows')) {
			return $result->num_rows > 0;
		}

		return !empty($result);
	}

	/**
	 * Soft delete article
	 * @param string $slug Slug title.
	 * @param int $user_id User id from session.
	 * @return int
	 */
	public function delete(string $slug, int $user_id) {
		// set query
		$query = "UPDATE " . $this->table . " SET is_deleted = 1, photo_cover = NULL WHERE user_id= :user_id AND slug = :slug";

		// delete data
		$this->db->query($query);

		// bind data
		$this->db->bind("user_id", $user_id);
		$this->db->bind("slug", $slug);

		// Execute
		$this->db->execute();

		// Count row table
		return $this->db->rowCount();
	}
}