CREATE TABLE `users` (
	id BIGINT PRIMARY KEY AUTO_INCREMENT,
	email VARCHAR(180) UNIQUE NOT NULL,
	username VARCHAR(100) UNIQUE NOT NULL,
	photo_profile VARCHAR(255) NULL,
	role VARCHAR(100) NOT NULL DEFAULT 'user',
	password VARCHAR(255) NOT NULL,
	is_deleted BOOLEAN DEFAULT FALSE,
	INDEX idx_email (email),
	INDEX idx_username (username),
	INDEX idx_is_deleted (is_deleted)
);