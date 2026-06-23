<?php

/**
 * Sessions database driver
 */
class Sessions implements SessionHandlerInterface {
	/**
	 * Database connection
	 * @var Database
	 */
	private Database $db;
	/**
	 * Database table
	 * @var string
	 */
	private string $table = 'sessions';

	/**
	 * Create database connection
	 * @param mixed $db
	 */
	public function __construct(?Database $db = null) {
		$this->db = $db ?? new Database();
	}

	public function open(string $path, string $name): bool {
		return true;
	}

	public function close(): bool {
		return true;
	}

	public function read(string $id): string|false {
		$this->db->query("SELECT data FROM {$this->table} WHERE id = :id AND expires_at > NOW()");
		$this->db->bind('id', $id);
		$data = $this->db->single();

		return $data !== false ? ($data['data'] ?? '') : '';
	}

	public function write(string $id, string $data): bool {
		$expiresAt = date('Y-m-d H:i:s', time() + (int) ini_get('session.gc_maxlifetime'));

		$query = "INSERT INTO {$this->table} (id, data, expires_at)
			VALUES (:id, :data, :expires_at)
			ON DUPLICATE KEY UPDATE
				data = VALUES(data),
				expires_at = VALUES(expires_at),
				updated_at = CURRENT_TIMESTAMP";

		$this->db->query($query);
		$this->db->bind('id', $id);
		$this->db->bind('data', $data);
		$this->db->bind('expires_at', $expiresAt);
		$this->db->execute();

		return true;
	}

	public function destroy(string $id): bool {
		$this->db->query("DELETE FROM {$this->table} WHERE id = :id");
		$this->db->bind('id', $id);
		$this->db->execute();

		return true;
	}

	public function gc(int $max_lifetime): int|false {
		$this->db->query("DELETE FROM {$this->table} WHERE expires_at <= NOW()");
		$this->db->execute();

		return $this->db->rowCount();
	}
}
