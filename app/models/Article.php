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

	// cari data berdasarkan id
	public function find($id) {
		$this->db->query('SELECT * FROM ' . $this->table . ' WHERE id=:id');
		$this->db->bind('id', $id);
		return $this->db->single();
	}

	// tambah data
	public function create($data) {	
		// validasi data
		
		
		// insert data
		$query = "INSERT INTO " . $this->table . " (title, slug, author, body) VALUES (:title, :slug, :author, :body)";
		$this->db->query($query);
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
	public function update() {

	}
}