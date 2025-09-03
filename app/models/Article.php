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
	public function create() {

	}

	// update data
	public function update() {

	}
}