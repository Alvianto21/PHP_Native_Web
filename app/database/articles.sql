CREATE TABLE `articles` (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(250) UNIQUE NOT NULL,
    user_id BIGINT,
    body TEXT NOT NULL,
    is_deleted BOOLEAN DEFAULT FALSE,
    Foreign Key (user_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_title (title),
    INDEX idx_is_deleted (is_deleted)
)