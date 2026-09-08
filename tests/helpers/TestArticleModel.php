<?php

class TestArticleModel
{
	public function __construct(private PDO $db) {}

	protected $articleSeederPost = [
		'data_1' => [
			'title' => 'Title article 1',
			'slug' => '',
			'photo_path' => '',
			'body' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Esse commodo ipsa minim in reprehenderit odio eos incididunt laboris. Nostrud est atque minim nesciunt occaecat. Voluptatum eos quos explicabo id ipsum laboris mollit eos proident est aut. Vitae tempora laboris aut quae vero sed fugiat sed excepteur sunt fugiat. Nemo fugiat mollitia proident deserunt voluptate ipsa nulla.

Enim eos cillum irure proident consequat in irure nesciunt lorem fugit. Praesentium non ut consequuntur illo inventore cupidatat blanditiis veritatis. Et ipsum voluptatum excepteur vitae amet iusto vero aliquip voluptate corrupti occaecat velit. Ullamco amet cillum praesentium excepturi in atque.

Esse esse deserunt nemo nemo. Consequuntur mollit magnam quas magna elit atque dignissimos. Corrupti dicta odio commodo quis veritatis.'
		],
		'data_2' => [
			'title' => 'Title number 2',
			'slug' => '',
			'photo_path' => '',
			'body' => 'Ex nostrud inventore nemo architecto ratione neque est ducimus eius animi. Cillum voluptatum atque ratione animi deserunt quos. Laborum labore accusamus quaerat qui obcaecati. Nesciunt beatae sequi cupiditate veritatis porro tempora consequuntur tempor quis eos veniam nulla. Id fugit amet tempora consequuntur odio.

Dolor quia dolores beatae vitae occaecat accusamus officia. Beatae quaerat porro officia sed adipiscing occaecat blanditiis sint blanditiis labore aut. Voluptatum enim illo inventore eos quasi occaecat deserunt exercitation veritatis veniam iusto irure. Qui ab quisquam deserunt minim amet beatae modi adipiscing do.

At elit non praesentium atque sunt cupiditate consectetur quas magna. Dignissimos exercitation tempor similique velit voluptatum vitae enim ratione. Dolore ullamco similique minim reprehenderit cupiditate et enim eius quis laborum veniam.'
		],
		'data_3' => [
			'title' => 'Title three',
			'slug' => '',
			'photo_path' => '',
			'body' => 'Consequuntur adipisci ut aliquip officia officia ex deserunt similique praesentium consequat. Elit nulla veritatis ut explicabo eos nisi dicta aut obcaecati dolore fugit. Ipsum dicta praesentium vero sed quae quos vitae reprehenderit lorem.

Magni tempor excepteur incididunt dolor anim fugiat consectetur. Dicta tempora dolores porro officia enim blanditiis dolorem aliquip excepturi quaerat tempor mollitia nulla. Consectetur tempora lorem exercitation ab voluptas. Aute do porro culpa officia illo pariatur qui consequuntur excepteur sequi sequi.

Ducimus velit esse odit quis ipsa eos quasi excepteur sit. Mollitia nulla vitae odit incididunt adipisci sit provident adipisci esse. Ex excepturi ratione veritatis iusto aute. Sit ipsa odit ab elit qui ipsam quos est non numquam vitae. Dicta est enim nisi ipsa quia eos magni. Quos ab excepturi ex enim pariatur aut ipsam porro fugit modi commodo quae.'
		],
		'data_4' => [
			'title' => 'The Four articles',
			'slug' => '',
			'photo_path' => '',
			'body' => 'Fugit commodo numquam anim officia do mollitia quos ut consectetur mollitia tempor exercitation. At enim magna adipisci consectetur consectetur lorem ipsam vitae dolore et. Proident dolores dignissimos magnam elit quae veritatis cillum eos vero aute nulla. Do labore molestias fugit sint in incididunt provident. Dolor duis eos odio tempor aut qui ipsa nisi deserunt non vero. Aliqua dolor ea duis deserunt enim ipsam numquam id architecto. Nemo quas praesentium occaecat architecto laboris ratione consequat odio deleniti.

Ad provident fugit in sit molestias ea quas cupidatat dolore dolore amet provident animi excepteur. Et qui irure praesentium velit pariatur aute veritatis nostrud quos dignissimos laboris quas quaerat voluptas. Ipsa amet officia ratione nesciunt nesciunt duis laboris architecto dolorem mollit illo obcaecati neque cupiditate. Sit vero ut architecto explicabo magna quos labore ullamco vitae nisi architecto. Tempor occaecat explicabo lorem ut deleniti laboris ipsa dignissimos aliqua aliquip inventore aut dolores.'
		],
		'data_5' => [
			'title' => 'Article 5 in here',
			'slug' => '',
			'photo_path' => '',
			'body' => 'Praesentium dolore nisi odio laborum illo. Consequat tempor ea obcaecati quasi iusto odit cillum enim deleniti inventore quos excepturi incididunt laborum. Adipisci odio ducimus nemo adipiscing quae dolore quisquam excepturi deserunt dignissimos dolores at nesciunt. Commodo esse numquam ratione tempora.

Anim nulla esse pariatur quis sint magna minim tempor anim dignissimos sint fugit fugiat animi. Vero dolor occaecat consectetur sit aute ab magni nulla incididunt qui. Aspernatur sunt proident incididunt commodo corrupti aute laborum porro. Magnam officia non incididunt cupiditate similique. Quas modi qui reprehenderit aliquip enim quas. Inventore ipsam inventore nisi accusamus ratione ipsam architecto aspernatur ut corrupti enim.'
		],
		'data_6' => [
			'title' => 'articles nomber 6',
			'slug' => '',
			'photo_path' => '',
			'body' => 'Voluptatum adipiscing ad aut nisi vero cupidatat accusamus magni nesciunt odit neque. Nesciunt quas voluptatum quis praesentium in lorem reprehenderit quos et at adipiscing ducimus. Consequat dolorem eos enim dicta aute.

Nesciunt in similique ad dolore dolores quis nulla sit veritatis. Irure blanditiis duis consequuntur tempor quas ipsum. Sunt cillum explicabo dignissimos est mollitia commodo ratione tempora beatae at voluptate. Adipiscing quia molestias explicabo nemo commodo commodo quis eos.'
		]
	];

	protected $articleSeederFiles = [
		'data_1' => [
			'photo_cover' => [
				'name' => 'cover.jpg',
				'type' => 'image/jpg',
				'tmp_name' => __DIR__ . '/../../storage/tests/cover_1.jpg',
				'error' => UPLOAD_ERR_OK,
				'size' => 10000
			]
		],
		'data_2' => [
			'photo_cover' => [
				'name' => '',
				'type' => '',
				'tmp_name' => '',
				'error' => UPLOAD_ERR_NO_FILE,
				'size' => 0
			]
		],
		'data_3' => [
			'photo_cover' => [
				'name' => '',
				'type' => '',
				'tmp_name' => '',
				'error' => UPLOAD_ERR_NO_FILE,
				'size' => 0
			]
		],
		'data_4' => [
			'photo_cover' => [
				'name' => 'cover.jpg',
				'type' => 'image/jpg',
				'tmp_name' => __DIR__ . '/../../storage/tests/cover_2.jpg',
				'error' => UPLOAD_ERR_OK,
				'size' => 10000
			]
		],
		'data_5' => [
			'photo_cover' => [
				'name' => 'cover.jpg',
				'type' => 'image/jpg',
				'tmp_name' => __DIR__ . '/../../storage/tests/cover_1.jpg',
				'error' => UPLOAD_ERR_OK,
				'size' => 10000
			]
		],
		'data_6' => [
			'photo_cover' => [
				'name' => '',
				'type' => '',
				'tmp_name' => '',
				'error' => UPLOAD_ERR_NO_FILE,
				'size' => 0
			]
		]
	];

	protected $table = 'articles';
	protected $tableRelations = 'users';

	/** @var \PDOStatement Current prepared statement */
	private $stmt;

	private function pathGenerator()
	{
		$secretKey = getenv('APP_KEY') ?: 'change-me';
		$expired = time() + 300; // 5 mins
		$signature = hash_hmac('sha256', (string) $expired, $secretKey);
		return ABSOLUTURL . 'files/store?expires=' . $expired . '&sig=' . $signature;
	}

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

	/**
	 * Same as bind function but can accept multiple params and values simultaneously.
	 * Example [['name', $name]].
	 * Example [['name', $name, PDO::PARAM_*]]
	 * @param array $bindings The data will be bind.
	 * @return void
	 */
	public function multiBind(array $bindings)
	{
		foreach ($bindings as $binding) {
			$param = $binding[0];
			$value = $binding[1];
			$type = $binding[2] ?? null;
			$this->bind($param, $value, $type);
		}
	}

	/**
	 * Execute the prepared statement.
	 *
	 * @return bool
	 */
	public function execute()
	{
		return $this->stmt->execute();
	}

	/**
	 * Execute the prepared statement and return the result set when available.
	 *
	 * @return mixed
	 */
	public function getResult()
	{
		$this->execute();

		if (method_exists($this->stmt, 'get_result')) {
			return $this->stmt->get_result();
		}

		return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function findSlug(string $slug)
	{
		$query = "SELECT 1 FROM {$this->table} WHERE slug = ? LIMIT 1";

		echo "searching...\n";

		$this->query($query);

		$this->bind(1, $slug);

		$this->stmt->execute();

		$result = $this->getResult();

		if (is_object($result) && method_exists($result, 'num_rows')) {
			return $result->num_rows > 0;
		}

		return !empty($result);
	}

	public function create(array $data, int $user_id)
	{
		$query = "INSERT INTO {$this->table} (title, slug, photo_cover, user_id, body) VALUES (:title, :slug, :photo_cover, :user_id, :body)";

		echo "creating...\n";

		$this->query($query);

		$this->multiBind([
			['title', $data['title']],
			['slug', $data['slug']],
			['photo_cover', $data['photo_cover']],
			['user_id', $user_id, PDO::PARAM_INT],
			['body', $data['body']]
		]);

		$this->stmt->execute();

		return $this->stmt->rowCount();
	}

	public function articleGenerator(int $user_id): string
	{
		require_once __DIR__ . '/../../app/request/UploadImage.php';

		$_POST = $this->articleSeederPost;
		$_FILES = $this->articleSeederFiles;
		$uploader = new UploadImage();

		(int) $success = 0;
		(int) $failed = 0;

		foreach ($_POST as $index => $data) {
			$formFile = $_FILES[$index]['photo_cover'] ?? null;

			if ($formFile['error'] === UPLOAD_ERR_OK) {
				$data['photo_profile'] = $uploader->store($formFile, 'covers');
			} elseif ($formFile['error'] === UPLOAD_ERR_NO_FILE) {
				$data['photo_profile'] = null;
			}

			$slug = strtolower(preg_replace("~[‘’`']+~", '', $data['title']));
			$slug = preg_replace("~[^a-z0-9]+~", ' ', $slug);
			$baseSlug = preg_replace('~[ ]+~', '-', trim($slug));
			$slug = $baseSlug;
			$slugCount = 1;

			while ($this->findSlug($slug)) {
				$slug = $baseSlug . '-' . $slugCount;
				$slugCount++;
			}

			$data['slug'] = $slug;

			$result = $this->create($data, $user_id);

			if ($result > 0) {
				$success++;
				echo "{$success} created.\n";
			} else {
				$failed++;
				echo "{$failed} create failed.\n";
			}
		}

		(int) $total = $success + $failed;
		if ($success === (int) count($this->articleSeederPost)) {
			return "articles created";
		} else {
			echo "only {$success} from {$total} created.";
			return "some article failed be created";
		}
	}

	public function articlesRadomizer()
	{
		$articleTarget = array_rand($this->articleSeederPost, 1);
		$articles = $this->articleSeederPost[$articleTarget]['title'];
		$article = $this->findByTitle($articles);
		return $article;
	}

	public function paginator(int $limit, int $offset)
	{
		$query = "SELECT articles.title, articles.slug, articles.photo_cover, articles.body, users.username AS author FROM {$this->table} JOIN {$this->tableRelations} ON articles.user_id = users.id WHERE articles.is_deleted = 0 ORDER BY articles.id DESC LIMIT :limit OFFSET :offset";

		echo "retrieving data from database...\n";
		$this->query($query);

		$this->multiBind([
			['limit', $limit, PDO::PARAM_INT],
			['offset', $offset, PDO::PARAM_INT]
		]);

		$this->stmt->execute();

		return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function getByUsers(int $user_id, int $limit, int $offset)
	{
		$query = "SELECT articles.title, articles.photo_cover, articles.slug, articles.body FROM `{$this->table}` JOIN `{$this->tableRelations}` ON articles.user_id = users.id WHERE articles.is_deleted = 0 AND users.id = :user_id LIMIT :limit OFFSET :offset";

		echo "retrieving data from database...\n";
		$this->query($query);

		$this->multiBind([
			['user_id', $user_id],
			['limit', $limit, PDO::PARAM_INT],
			['offset', $offset, PDO::PARAM_INT]
		]);

		$this->execute();

		return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function count()
	{
		$query = "SELECT COUNT (*) AS total FROM {$this->table} WHERE is_deleted = 0";

		echo "counting data in database...\n";
		$this->query($query);

		$this->stmt->execute();

		return (int) $this->stmt->fetchColumn();
	}

	public function findByTitle(string $title)
	{
		$query = "SELECT title, slug, photo_cover, body FROM {$this->table} WHERE title = :title AND is_deleted = 0";

		echo "retrieving data from database...\n";
		$this->query($query);

		$this->bind('title', $title);

		$this->execute();

		return $this->stmt->fetch(PDO::FETCH_ASSOC);
	}

	public function findArticleUser(string $slug, int $user_id)
	{
		$query = "SELECT title, slug, photo_cover, body FROM {$this->table} WHERE slug = :slug AND user_id = :user_id AND is_deleted = 0";

		echo "retrieving data from database...\n";
		$this->query($query);

		$this->multiBind([
			['slug', $slug],
			['user_id', $user_id, PDO::PARAM_INT]
		]);

		$this->execute();

		return $this->stmt->fetch(PDO::FETCH_ASSOC);
	}

	public function isArticleDeleted(string $slug, int $user_id): bool {
		$query = "SELECT 1 FROM {$this->table} WHERE is_deleted = 1 AND slug = :slug AND user_id = :user_id";

		echo "checking data status...\n";
		$this->query($query);

		$this->multiBind([
			['slug', $slug],
			['user_id', $user_id, PDO::PARAM_INT]
		]);

		return $this->execute() ? true : false;
	}
	

	public function update(array $data, array $columns, int $user_id, string $slug)
	{
		if (empty($columns)) {
			return 1;
		}

		$field = implode(', ', $columns);
		$query = "UPDATE {$this->table} SET {$field} WHERE user_id = :user_id AND slug = :current_slug";

		echo "updateting data...\n";
		$this->query($query);

		foreach ($data as $key => $value) {
			$this->bind($key, $value);
		}

		$this->multiBind([
			['user_id', $user_id, PDO::PARAM_INT],
			['current_slug', $slug]
		]);

		return $this->execute();
	}

	public function delete(string $slug, int $user_id) {
		$query = "UPDATE {$this->table} SET is_deleted = 1, photo_cover = NULL WHERE user_id = :user_id AND slug = :slug";

		echo "setting data as deleted...\n";
		$this->query($query);

		$this->multiBind([
			['user_id', $user_id, PDO::PARAM_INT],
			['slug', $slug]
		]);

		$this->execute();

		return $this->stmt->rowCount();
	}
}
