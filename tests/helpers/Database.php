<?php 

use PHPUnit\Framework\Attributes\Before;

trait DatabaseUp
{
	#[Before]
	public function prepareDatabase(): void
	{
		echo "setup temp database.\n";
		$this->db = new \PDO('sqlite::memory:');
		$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->db->exec("
		CREATE TABLE `sessions` (
			id VARCHAR(128) PRIMARY KEY,
			data MEDIUMTEXT NOT NULL,
			updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			expires_at TIMESTAMP NOT NULL
		)");
		$this->db->exec("CREATE TABLE `users` (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			email VARCHAR(180) UNIQUE NOT NULL,
			username VARCHAR(100) UNIQUE NOT NULL,
			photo_profile VARCHAR(255) NULL,
			role VARCHAR(100) NOT NULL DEFAULT 'user',
			password VARCHAR(255) NOT NULL,
			is_deleted BOOLEAN DEFAULT FALSE
		)");
		$this->db->exec("CREATE TABLE `articles` (
			id INTEGER PRIMARY KEY AUTOINCREMENT,
			title VARCHAR(200) NOT NULL,
			slug VARCHAR(250) UNIQUE NOT NULL,
			user_id BIGINT,
			body TEXT NOT NULL,
			photo_cover VARCHAR(255) NULL,
			is_deleted BOOLEAN DEFAULT FALSE,
			Foreign Key (user_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE RESTRICT)");
		$this->db->exec('PRAGMA foreign_keys = ON');
		$this->db->exec("CREATE INDEX idx_email ON users(email)");
		$this->db->exec("CREATE INDEX idx_username ON users(username)");
		$this->db->exec("CREATE INDEX idx_users_is_deleted ON users(is_deleted)");
		$this->db->exec("CREATE INDEX idx_slug ON articles(slug)");
		$this->db->exec("CREATE INDEX idx_article_is_deleted ON articles(is_deleted)");
		echo "setup database finished.\n";
	}
}
