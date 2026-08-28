<?php 

class TestSessions extends Sessions {
	public function __construct(private ?PDO $db = null)
	{
		
	}

	private string $table = 'sessions';

	/** @var \PDOStatement Current prepared statement */
	private $stmt;

	/**
	 * Prepare an SQL query for execution.
	 *
	 * @param string $query SQL query with placeholders
	 * @return void
	 */
	public function query($query)
	{
		$this->stmt = $this->db->prepare($query);
	}

	/**
	 * Bind a value to a parameter in the current statement.
	 *
	 * @param string|int $param Parameter identifier
	 * @param mixed $value Value to bind
	 * @param int|null $type PDO::PARAM_* type constant or null to infer
	 * @return void
	 */
	public function bind($param, $value, $type = null)
	{
		if (is_null($type)) {
			switch (true) {
				case is_int($value):
					$type = PDO::PARAM_INT;
					break;
				case is_bool($value):
					$type = PDO::PARAM_BOOL;
					break;
				case is_null($value):
					$type = PDO::PARAM_NULL;
					break;
				default:
					$type = PDO::PARAM_STR;
					break;
			}
		}

		$this->stmt->bindValue($param, $value, $type);
	}

	#[Override]
	public function write(string $id, string $data): bool
	{
		$expiresAt = date('Y-m-d H:i:s', time() + (int) ini_get('session.gc_maxlifetime'));
		$updated_at = date('Y-m-d H:i:s', time());

		$query = "INSERT OR REPLACE INTO {$this->table} (id, data, expires_at, updated_at) VALUES (:id, :data, :expires_at, :updated_at)";

		$this->query($query);

		$this->bind('id', $id);
		$this->bind('data', $data);
		$this->bind('expires_at', $expiresAt);
		$this->bind('updated_at', $updated_at);

		$this->stmt->execute();

		return true;
	}

	#[Override]
	public function read(string $id): string|false
	{
		$query = "SELECT data FROM {$this->table} WHERE id = :id AND expires_at > datetime('now')";

		$this->query($query);

		$this->bind('id', $id);

		$this->stmt->execute();

		$data = $this->stmt->fetch(PDO::FETCH_ASSOC);

		return $data !== false ? ($data['data'] ?? '') : '';
	}

	#[Override]
	public function destroy(string $id): bool
	{
		$query = "DELETE FROM {$this->table} WHERE id = :id";

		$this->query($query);

		$this->bind('id', $id);

		$this->stmt->execute();

		return true;
	}

	#[Override]
	public function gc(int $max_lifetime): int|false
	{
		$query = "DELETE FROM {$this->table} WHERE expires_at <= datetime{'now')";

		$this->query($query);

		$this->stmt->execute();

		return $this->stmt->rowCount();
	}
}
