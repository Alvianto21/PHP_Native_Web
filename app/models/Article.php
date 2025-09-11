<?php

class Article {	
	
	private $table = 'articles';
	private $db; 

	// koneksi ke database
	public function __construct() {
		$this->db = new Database();
	}

	// semua data
	public function all() {
		$this->db->query('SELECT * FROM ' . $this->table);
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

	// cari data berdasarkan id
	public function find($id) {
		$this->db->query('SELECT * FROM ' . $this->table . ' WHERE id=:id');
		$this->db->bind('id', $id);
		return $this->db->single();
	}

	// tambah data
	public function create($data) {		
		// set query
		$query = "INSERT INTO " . $this->table . " (title, slug, author, body) VALUES (:title, :slug, :author, :body)";

		// insert data
		$this->db->query($query);

		// bind data
		$this->db->bind("title", $data['title']);
		$this->db->bind("slug", $data['slug']);
		$this->db->bind("author", $data['author']);
		$this->db->bind("body", $data['body']);

		// eksekusi
		$this->db->execute();

		// cek apakah ada data yang berubah
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