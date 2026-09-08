<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/controllers/DashboardController.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/Sessions.php';
require_once __DIR__ . '/../helpers/UserLogin.php';
require_once __DIR__ . '/../helpers/TestArticleModel.php';

use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DashboardControllerStub extends DashboardController
{
	protected $userIdentifier;

	public function __construct(private ?PDO $db = null)
	{
		if (!isset($_SESSION['user_info'])) {
			Flasher::setFlash('Mohon maaf, ', 'aksess halaman ini ditolak!', 'danger');
			throw new RuntimeException('User is not logged in');
		}

		$clientIP = !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
		$this->userIdentifier = $clientIP;
	}

	public array $views = [];

	#[Override]
	public function model($model)
	{
		echo "model connect to model {$model}.\n";
		return match ($model) {
			'Article' => new TestArticleModel($this->db)
		};
	}

	public function view($view, $data = [])
	{
		echo "connecting to views.\n";
		$this->views[] = ['view' => $view, 'data' => $data];
	}

	public function createArticle(array $postData, array $fileData, int $user_id): string
	{
		require_once __DIR__ . '/../../app/request/Validator.php';
		require_once __DIR__ . '/../../app/request/UploadImage.php';

		$validator = new Validator();
		$uploader = new UploadImage();

		if ($_SERVER['REQUEST_METHOD'] !== "POST") {
			return "method not allowed";
		}

		$data = [
			'title' => $validator->clearData($postData['title'] ?? ''),
			'slug' => '',
			'photo_cover' => $fileData['photo_cover'] ?? '',
			'photo_path' => $postData['photo_path'] ?? '',
			'body' => $validator->clearData($postData['body'] ?? '')
		];

		$rules = [
			'title' => [
				'required' => true,
				'min' => 10,
				'max' => 200,
			],
			'photo_cover' => [
				"size" => 500000, // 500 Kb
				"img" => true
			],
			'photo_path' => [
				'required_if' => 'photo_cover',
				'signature' => true,
			],
			'body' => [
				'required' => true,
				'min' => 200
			]
		];

		if ($validator->validate($data, $rules)) {
			if (!empty($data['photo_path'])) {
				$checkUrl = $validator->validateSignUrl($data['photo_path']);

				if ($checkUrl !== true) {
					http_response_code(403);
					echo $checkUrl;
					return "photo URL validation failed";
				}
			}

			if ($data['photo_cover']['error'] !== UPLOAD_ERR_NO_FILE) {
				$data['photo_cover'] = $uploader->store($data['photo_cover'], 'covers');
			} else {
				$data['photo_cover'] = null;
			}

			$slug = strtolower(preg_replace("~[‘’`']+~", '', $data['title']));
			$slug = preg_replace("~[^a-z0-9]+~", ' ', $slug);
			$baseSlug = preg_replace('~[ ]+~', '-', trim($slug));
			$slug = $baseSlug;
			$slugCount = 1;

			while ($this->model('Article')->findSlug($slug)) {
				$slug = $baseSlug . '-' . $slugCount;
				$slugCount++;
			}

			$data['slug'] = $slug;

			if ($this->model('Article')->create($data, $user_id) > 0) {
				echo "article " . substr($data['title'], 0, 50) . " created.\n";
				return "article created";
			} else {
				var_dump($validator->errors());
				return "failed create article";
			}
		} else {
			echo "validation failed.\n";
			var_dump($validator->errors());
			return "validation failed";
		}
	}

	public function showArticle(string $slug)
	{
		require_once __DIR__ . '/../../app/request/UploadImage.php';

		$uploader = new UploadImage();
		$user = $_SESSION['user_info']['user_id'];

		$data['judul'] = 'Detail article';
		$data['style'] = "blog.css";

		$article = $this->model('Article')->findArticleUser($slug, $user);

		if ($article) {
			if (!empty($article['photo_cover'])) {
				$article['photo_cover'] = $uploader->show($article['photo_cover'], 300);
			}

			$data['article'] = $article;
		} else {
			echo " article with {$slug} not found.\n";
			return "article not found";
		}

		$this->view('templates/header', $data);
		$this->view('dashboard/show', $data);
		$this->view('templates/footer');
	}

	public function editArticle(string $slug)
	{
		$data['judul'] = 'Edit Artikel';
		$data['style'] = "article.css";

		$user = $_SESSION['user_info']['user_id'];
		$article = $this->model('Article')->findArticleUser($slug, $user);

		if ($article) {
			$data['article'] = $article;
		} else {
			echo " article with {$slug} not found.\n";
			return "article not found";
		}

		$this->view('templates/header', $data);
		$this->view('dashboard/edit', $data);
		$this->view('templates/footer');
	}

	public function updateArticle(string $slug): string
	{
		require_once __DIR__ . '/../../app/request/Validator.php';
		require_once __DIR__ . '/../../app/request/UploadImage.php';

		if ($_SERVER['REQUEST_METHOD'] !== "POST") {
			return "method not allowed";
		}

		$validator = new Validator();
		$uploader = new UploadImage();

		$postData = $_POST;
		$fileData = $_FILES;
		$user = $_SESSION['user_info']['user_id'];
		$newSlug = '';
		$article = $this->model('Article')->findArticleUser($slug, $user);
		$dataKey = [];
		$dataUpdate = [];

		if (!$article) {
			echo " article with {$slug} not found.\n";
			return "article not found";
		}

		$data = [
			'title' => $validator->clearData($postData['title'] ?? ''),
			'slug' => '',
			'photo_cover' => $fileData['photo_cover'] ?? '',
			'photo_path' => $postData['photo_path'] ?? '',
			'body' => $validator->clearData($_POST['body'] ?? ''),
		];

		$rules = [
			'title' => [
				'required' => true,
				'min' => 10,
				'max' => 200,
			],
			'photo_cover' => [
				"size" => 500000, // 500 Kb
				"img" => true
			],
			'photo_path' => [
				'required_if' => 'photo_cover',
				'signature' => true,
			],
			'body' => [
				'required' => true,
				'min' => 200
			]
		];

		if ($validator->validate($data, $rules)) {
			if ($article['title'] !== $data['title']) {
				$newSlug = strtolower(preg_replace("~[‘’`']+~", '', $data['title']));
				$newSlug = preg_replace("~[^a-z0-9]+~", ' ', $newSlug);
				$baseSlug = preg_replace('~[ ]+~', '-', trim($newSlug));
				$newSlug = $baseSlug;
				$slugCount = 1;

				while ($this->model('Article')->findSlug($newSlug)) {
					$newSlug = $baseSlug . '-' . $slugCount;
					$slugCount++;
				}

				$data['slug'] = $newSlug;
				echo "title & slug updated\n";
			} else {
				$data['slug'] = $article['slug'];
				echo "using old title & slug\n";
			}

			if (!empty($data['photo_path'])) {
				$checkUrl = $validator->validateSignUrl($data['photo_path']);

				if ($checkUrl !== true) {
					http_response_code(403);
					echo $checkUrl;
					return "photo URL validation failed";
				}
			}

			if ($data['photo_cover']['error'] === UPLOAD_ERR_OK) {
				$data['photo_cover'] = $uploader->update($data['photo_cover'], (string) $postData['old_photo_cover'], 'covers');
			} elseif ($data['photo_cover']['error'] === UPLOAD_ERR_NO_FILE) {
				$data['photo_cover'] = $postData['old_photo_cover'];
			}

			foreach ($data as $updateData => $updateValue) {
				if ($updateData === 'photo_cover' && is_array($updateValue) && ($updateValue['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
					$updateValue = $postData['old_photo_cover'] ?? $article['photo_cover'];
				} elseif (array_key_exists($updateData, $article) && $article[$updateData] != $updateValue) {
					$dataKey[] = "{$updateData} = :{$updateData}";
					$dataUpdate[$updateData] = $updateValue;
				}
			}

			if (empty($dataKey)) {
				echo "Article with title " . substr($data['title'], 0, 50) . " updated.\n";
				return "update success";
			}

			if ($this->model('Article')->update($dataUpdate, $dataKey, $user, $slug) > 0) {
				echo "Article with title " . substr($data['title'], 0, 50) . " updated.\n";
				return "update success";
			} else {
				var_dump($validator->errors());
				return "update failed";
			}
		} else {
			echo "validation failed.\n";
			var_dump($validator->errors());
			return "validation failed";
		}
	}

	public function deleteArticle(string $slug): string
	{
		require_once __DIR__ . '/../../app/request/UploadImage.php';

		$uploader = new UploadImage();
		$user = $_SESSION['user_info']['user_id'];

		$articleTarget = $this->model('Article')->findArticleUser($slug, $user);

		if ($articleTarget) {
			$article = $this->model('Article')->delete($slug, $user);

			if ($article) {
				$uploader->delete((string) $articleTarget['photo_cover']);
				echo "article with title {$articleTarget['title']} deleted.\n";
				return "article deleted";
			} else {
				echo "delete article with title {$articleTarget['title']} failed.\n";
				return "failed to delete article";
			}
		} else {
			echo "article with title {$articleTarget['title']} not found.\n";
			return "article not found";
		}
	}
}

#[TestDox("Dashboard controller")]
class DashboardTest extends TestCase
{
	private $db;

	private array $new_user = [
		'email' => 'tanika76@example.com',
		'username' => 'tanika76',
		'photo_path' => '',
		'password' => 'qweasd1234',
		'password_confirm' => 'qweasd1234'
	];

	private array $new_user_img = [
		'photo_profile' => [
			'name' => '',
			'type' => '',
			'tmp_name' => '',
			'error' => UPLOAD_ERR_NO_FILE,
			'size' => 0,
		]
	];

	use DatabaseUp;

	protected function fillForm(array $formData, array $formFile)
	{
		echo "filling form...\n";
		$_POST = $formData;
		$_FILES = $formFile;
		echo "form is filled.\n";
	}

	#[Before]
	protected function UrlGenerator(): string
	{
		$secretKey = getenv('APP_KEY') ?: 'change-me';
		$expired = time() + 300; // 5 mins
		$signature = hash_hmac('sha256', (string) $expired, $secretKey);
		return ABSOLUTURL . 'files/store?expires=' . $expired . '&sig=' . $signature;
	}

	#[Override]
	protected function setUp(): void
	{
		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

		echo "starting sessions.\n";
		$sessionHandler = new TestSessions($this->db);
		session_set_save_handler($sessionHandler, true);
		if (!session_id()) session_start();
		echo "session started.\n";
	}

	protected function login(array $postData, array $fileData)
	{
		echo "generate new user..\n";
		$userGene = new HelperLogin($this->db);
		$statusCreate = $userGene->createUser($postData, $fileData);

		if ($statusCreate !== 'user created') {
			echo $statusCreate;
			die($statusCreate);
		}

		echo "user generated.\nlogin new user..\n";

		$statusLogin = $userGene->userLogin([
			'email' => $this->new_user['email'],
			'password' => $this->new_user['password']
		]);

		if ($statusLogin !== 'login success' && !isset($_SESSION['user_info'])) {
			echo $statusLogin;
			die($statusLogin);
		}

		return $statusLogin;
	}

	protected function articlesDummy(int $user_id)
	{
		echo "Creating data dummy for articles...\n";

		$articleGenerator = new TestArticleModel($this->db);
		$statusArticles = $articleGenerator->articleGenerator($user_id);

		if ($statusArticles !== "articles created") {
			echo $statusArticles;
			die($statusArticles);
		}

		echo "articles data generated.\n";
	}

	#[Test] #[TestDox("Dashboard page is accessible")]
	public function dashboard_is_accessible(): void
	{
		$this->login($this->new_user, $this->new_user_img);

		$user = $_SESSION['user_info'];

		$this->articlesDummy($user['user_id']);

		$controller = new DashboardControllerStub($this->db);

		$controller->index();

		$this->assertCount(3, $controller->views);
		$this->assertSame('templates/header', $controller->views[0]['view']);
		$this->assertSame('dashboard/list', $controller->views[1]['view']);
		$this->assertSame('templates/footer', $controller->views[2]['view']);
		$this->assertArrayHasKey('articles', $controller->views[1]['data']);
		$this->assertCount(5, $controller->views[1]['data']['articles']);
	}

	#[Test] #[TestDox("Dashboard is accessible but user not have articles")]
	public function dashboard_is_accessible_but_user_not_have_articles(): void
	{
		$this->login($this->new_user, $this->new_user_img);

		$controller = new DashboardControllerStub($this->db);

		$controller->index();

		$this->assertCount(3, $controller->views);
		$this->assertSame('templates/header', $controller->views[0]['view']);
		$this->assertSame('dashboard/list', $controller->views[1]['view']);
		$this->assertSame('templates/footer', $controller->views[2]['view']);
		$this->assertArrayHasKey('articles', $controller->views[1]['data']);
		$this->assertCount(0, $controller->views[1]['data']['articles']);
	}

	#[Test] #[TestDox("Dashboard is unaccessible because user not login")]
	public function dashboard_is_unaccessible_because_user_not_login(): void
	{
		try {
			new DashboardControllerStub($this->db);
			$this->fail('Expected RuntimeException');
		} catch (RuntimeException $e) {
			$this->assertSame('User is not logged in', $e->getMessage());
			$this->assertTrue(isset($_SESSION['flash']));
		}
	}

	#[Test] #[TestDox("Create article page is accessible")]
	public function create_article_page_is_accessible(): void
	{
		$this->login($this->new_user, $this->new_user_img);

		$user = $_SESSION['user_info'];

		$controller = new DashboardControllerStub($this->db);

		$controller->create();

		$this->assertCount(3, $controller->views);
		$this->assertSame('templates/header', $controller->views[0]['view']);
		$this->assertSame('dashboard/create', $controller->views[1]['view']);
		$this->assertSame('templates/footer', $controller->views[2]['view']);
	}

	#[Test] #[TestDox("success create new article with photo cover")]
	public function success_create_new_article_with_photo_cover(): void
	{
		$this->login($this->new_user, $this->new_user_img);

		$user = $_SESSION['user_info'];

		$controller = new DashboardControllerStub($this->db);

		$new_article = [
			'title' => 'Anim commodo proident ratione nemo deleniti ipsum vero quia do nesciunt odit quasi at ea. Vitae magna provident quisquam numquam aute tempora id quis deserunt magna tempor qui amet.',
			'photo_path' => $this->UrlGenerator(),
			'body' => 'Anim commodo proident ratione nemo deleniti ipsum vero quia do nesciunt odit quasi at ea. Vitae magna provident quisquam numquam aute tempora id quis deserunt magna tempor qui amet. Quia cillum quis proident dolore vero ipsam aspernatur quisquam ab laborum ipsum ipsam.

Reprehenderit obcaecati porro atque modi quisquam aut. Dolorem incididunt laborum sed quisquam ab ex anim. Magni ea irure atque exercitation dolor similique vitae sequi nemo incididunt tempora veniam aliquip nemo. Neque praesentium atque ipsa ad do quaerat nemo aliqua voluptate minim. Ratione inventore lorem sint mollit ipsam ab.

Non illo laborum tempora irure dicta sed magni sit ullamco quasi sed voluptate aspernatur sequi. Cupiditate ad sed duis magna dolor dolorem voluptatum enim ipsa. Adipiscing veniam ut beatae quia. Accusamus quas magni pariatur inventore dolore aspernatur adipiscing fugit laboris quia. Cupiditate quaerat sequi aut nisi ab. Accusamus veniam ex voluptatum dicta minim. Consequat quis reprehenderit officia consequat aspernatur duis numquam provident enim consectetur quae explicabo consequat.'
		];

		$new_article_img = [
			'photo_cover' => [
				'name' => 'cover.jpg',
				'type' => 'image/jpg',
				'tmp_name' => __DIR__ . '/../../storage/tests/cover_1.jpg',
				'error' => UPLOAD_ERR_OK,
				'size' => 40000
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->fillForm($new_article, $new_article_img);
		echo "prepare storing...\n";

		$action = $controller->createArticle($_POST, $_FILES, $user['user_id']);

		$this->assertTrue('article created' === $action);

		$stmt = $this->db->query("SELECT title, slug FROM articles ORDER BY id DESC LIMIT 1");
		$article = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$this->assertCount(1, $article);
		$this->assertSame($new_article['title'], $article[0]['title']);
	}

	#[Test] #[TestDox("Success create new article without photo cover")]
	public function success_create_new_article_without_photo_cover(): void
	{
		$this->login($this->new_user, $this->new_user_img);

		$user = $_SESSION['user_info'];

		$controller = new DashboardControllerStub($this->db);

		$new_article = [
			'title' => 'Fugit dolores dicta magna dolores blanditiis excepturi ad incididunt at. Exercitation obcaecati commodo provident in similique nemo sint ullamco vero quis.',
			'photo_path' => '',
			'body' => 'Fugit dolores dicta magna dolores blanditiis excepturi ad incididunt at. Exercitation obcaecati commodo provident in similique nemo sint ullamco vero quis. Quae illo excepturi duis ab nulla culpa cillum duis explicabo nulla magni.

Consequuntur velit veniam quos quasi elit. Dolore proident quas obcaecati anim magni laboris vitae reprehenderit anim ratione veritatis obcaecati. Et vero dignissimos eos cupiditate ducimus illo consequuntur mollitia dignissimos est ipsa.

Aliqua magnam culpa eiusmod aliquip vero magni atque sed at enim. Similique quaerat illo sed reprehenderit obcaecati. Veniam quos esse porro dolores aliqua officia pariatur. Tempor consequuntur ex esse consequat eiusmod do. Reprehenderit porro excepteur non minim ex quia quisquam ad ullamco tempora tempora excepturi. Accusamus aut dolor quia quisquam ex laboris ipsam veniam.'
		];

		$new_article_img = [
			'photo_cover' => [
				'name' => '',
				'type' => '',
				'tmp_name' => '',
				'error' => UPLOAD_ERR_NO_FILE,
				'size' => 0
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->fillForm($new_article, $new_article_img);
		echo "prepare storing...\n";

		$action = $controller->createArticle($_POST, $_FILES, $user['user_id']);

		$this->assertTrue('article created' === $action);

		$stmt = $this->db->query("SELECT title, slug FROM articles ORDER BY id DESC LIMIT 1");
		$article = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$this->assertCount(1, $article);
		$this->assertSame($new_article['title'], $article[0]['title']);
	}

	#[Test] #[TestDox("Success create new article with photo cover empty but photo path exist")]
	public function success_create_new_article_with_photo_cover_empty_but_photo_path_exist(): void
	{
		$this->login($this->new_user, $this->new_user_img);

		$user = $_SESSION['user_info'];

		$controller = new DashboardControllerStub($this->db);

		$new_article = [
			'title' => 'Ipsam deserunt corrupti occaecat voluptatum veniam ratione aliquip voluptas fugit. Et nulla laboris praesentium ipsum commodo aut ipsa quaerat nisi.',
			'photo_path' => $this->UrlGenerator(),
			'body' => 'Ipsam deserunt corrupti occaecat voluptatum veniam ratione aliquip voluptas fugit. Et nulla laboris praesentium ipsum commodo aut ipsa quaerat nisi. Sunt sed similique corrupti neque atque beatae aspernatur officia voluptate corrupti.

Ex aspernatur quis quia voluptatum lorem consequat dolores cupidatat ipsa numquam voluptas. Nesciunt velit quos pariatur quae ratione quasi nemo dolor quae incididunt sit aute odio. Porro ipsum quasi incididunt dolor ea officia ducimus dignissimos reprehenderit blanditiis aliqua. Quasi quasi molestias accusamus elit aliqua culpa. Quaerat quae obcaecati sed enim. Reprehenderit fugiat sed ullamco do fugit quaerat ipsum aute esse elit ut mollitia deserunt est. Adipisci ratione irure ex reprehenderit cupidatat aliquip obcaecati ut obcaecati id inventore cupidatat inventore.

Praesentium voluptatum sequi ex sunt dolorem consectetur accusamus velit commodo. Culpa porro aute ipsam commodo sunt reprehenderit non veritatis anim tempor qui. Illo inventore ut ea quaerat odit eiusmod non qui officia magni. Provident fugit proident vitae commodo voluptate elit veniam laboris fugit dignissimos labore sint obcaecati. Ut vitae consequuntur adipisci quisquam similique inventore aliquip commodo consequuntur.'
		];

		$new_article_img = [
			'photo_cover' => [
				'name' => '',
				'type' => '',
				'tmp_name' => '',
				'error' => UPLOAD_ERR_NO_FILE,
				'size' => 0
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->fillForm($new_article, $new_article_img);
		echo "prepare storing...\n";

		$action = $controller->createArticle($_POST, $_FILES, $user['user_id']);

		$this->assertTrue('article created' === $action);

		$stmt = $this->db->query("SELECT title, slug FROM articles ORDER BY id DESC LIMIT 1");
		$article = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$this->assertCount(1, $article);
		$this->assertSame($new_article['title'], $article[0]['title']);
	}

	#[Test] #[TestDox("Failed create new article because title too short")]
	public function failed_create_new_article_because_title_too_short(): void
	{
		$this->login($this->new_user, $this->new_user_img);

		$user = $_SESSION['user_info'];

		$controller = new DashboardControllerStub($this->db);

		$new_article = [
			'title' => 'hello',
			'photo_path' => '',
			'body' => 'Ex aspernatur quis quia voluptatum lorem consequat dolores cupidatat ipsa numquam voluptas. Nesciunt velit quos pariatur quae ratione quasi nemo dolor quae incididunt sit aute odio. Porro ipsum quasi incididunt dolor ea officia ducimus dignissimos reprehenderit blanditiis aliqua. Quasi quasi molestias accusamus elit aliqua culpa. Quaerat quae obcaecati sed enim. Reprehenderit fugiat sed ullamco do fugit quaerat ipsum aute esse elit ut mollitia deserunt est. Adipisci ratione irure ex reprehenderit cupidatat aliquip obcaecati ut obcaecati id inventore cupidatat inventore.'
		];

		$new_article_img = [
			'photo_cover' => [
				'name' => '',
				'type' => '',
				'tmp_name' => '',
				'error' => UPLOAD_ERR_NO_FILE,
				'size' => 0
			]
		];

		$action = $controller->createArticle($new_article, $new_article_img, $user['user_id']);

		$this->assertTrue($action === 'validation failed');

		$stmt = $this->db->query("SELECT title, slug FROM articles ORDER BY id DESC LIMIT 1");
		$article = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$this->assertNotEquals($new_article['title'], $article);
	}

	#[Test] #[TestDox("Failed create new article because title too long")]
	public function failed_create_new_article_because_title_too_long(): void
	{
		$this->login($this->new_user, $this->new_user_img);

		$user = $_SESSION['user_info'];

		$controller = new DashboardControllerStub($this->db);

		$new_article = [
			'title' => 'Ipsam deserunt corrupti occaecat voluptatum veniam ratione aliquip voluptas fugit. Et nulla laboris praesentium ipsum commodo aut ipsa quaerat nisi. Sunt sed similique corrupti neque atque beatae aspernatur officia voluptate corrupti.',
			'photo_path' => '',
			'body' => 'Ex aspernatur quis quia voluptatum lorem consequat dolores cupidatat ipsa numquam voluptas. Nesciunt velit quos pariatur quae ratione quasi nemo dolor quae incididunt sit aute odio. Porro ipsum quasi incididunt dolor ea officia ducimus dignissimos reprehenderit blanditiis aliqua. Quasi quasi molestias accusamus elit aliqua culpa. Quaerat quae obcaecati sed enim. Reprehenderit fugiat sed ullamco do fugit quaerat ipsum aute esse elit ut mollitia deserunt est. Adipisci ratione irure ex reprehenderit cupidatat aliquip obcaecati ut obcaecati id inventore cupidatat inventore.'
		];

		$new_article_img = [
			'photo_cover' => [
				'name' => '',
				'type' => '',
				'tmp_name' => '',
				'error' => UPLOAD_ERR_NO_FILE,
				'size' => 0
			]
		];

		$action = $controller->createArticle($new_article, $new_article_img, $user['user_id']);

		$this->assertTrue($action === 'validation failed');

		$stmt = $this->db->query("SELECT title, slug FROM articles ORDER BY id DESC LIMIT 1");
		$article = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$this->assertNotEquals($new_article['title'], $article);
	}

	#[Test] #[TestDox("Failed create new article because photo cover too large")]
	public function failed_create_new_article_because_photo_cover_too_large(): void
	{
		$this->login($this->new_user, $this->new_user_img);

		$user = $_SESSION['user_info'];

		$controller = new DashboardControllerStub($this->db);

		$new_article = [
			'title' => 'Anim commodo proident ratione nemo deleniti ipsum vero quia do nesciunt odit quasi at ea. Vitae magna provident quisquam numquam aute tempora id quis deserunt magna tempor qui amet.',
			'photo_path' => $this->UrlGenerator(),
			'body' => 'Anim commodo proident ratione nemo deleniti ipsum vero quia do nesciunt odit quasi at ea. Vitae magna provident quisquam numquam aute tempora id quis deserunt magna tempor qui amet. Quia cillum quis proident dolore vero ipsam aspernatur quisquam ab laborum ipsum ipsam.

Reprehenderit obcaecati porro atque modi quisquam aut. Dolorem incididunt laborum sed quisquam ab ex anim. Magni ea irure atque exercitation dolor similique vitae sequi nemo incididunt tempora veniam aliquip nemo. Neque praesentium atque ipsa ad do quaerat nemo aliqua voluptate minim. Ratione inventore lorem sint mollit ipsam ab.

Non illo laborum tempora irure dicta sed magni sit ullamco quasi sed voluptate aspernatur sequi. Cupiditate ad sed duis magna dolor dolorem voluptatum enim ipsa. Adipiscing veniam ut beatae quia. Accusamus quas magni pariatur inventore dolore aspernatur adipiscing fugit laboris quia. Cupiditate quaerat sequi aut nisi ab. Accusamus veniam ex voluptatum dicta minim. Consequat quis reprehenderit officia consequat aspernatur duis numquam provident enim consectetur quae explicabo consequat.'
		];

		$new_article_img = [
			'photo_cover' => [
				'name' => 'cover.jpg',
				'type' => 'image/jpg',
				'tmp_name' => __DIR__ . '/../../storage/tests/cover_1.jpg',
				'error' => UPLOAD_ERR_OK,
				'size' => 600000
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->fillForm($new_article, $new_article_img);
		echo "prepare storing...\n";

		$action = $controller->createArticle($_POST, $_FILES, $user['user_id']);

		$this->assertTrue('validation failed' === $action);

		$stmt = $this->db->query("SELECT title, slug FROM articles ORDER BY id DESC LIMIT 1");
		$article = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$this->assertNotEquals($new_article['title'], $article);
	}

	#[Test] #[TestDox("Failed create new article because photo cover not image")]
	public function failed_create_new_article_because_photo_cover_not_image(): void
	{
		$this->login($this->new_user, $this->new_user_img);

		$user = $_SESSION['user_info'];

		$controller = new DashboardControllerStub($this->db);

		$new_article = [
			'title' => 'Anim commodo proident ratione nemo deleniti ipsum vero quia do nesciunt odit quasi at ea. Vitae magna provident quisquam numquam aute tempora id quis deserunt magna tempor qui amet.',
			'photo_path' => $this->UrlGenerator(),
			'body' => 'Anim commodo proident ratione nemo deleniti ipsum vero quia do nesciunt odit quasi at ea. Vitae magna provident quisquam numquam aute tempora id quis deserunt magna tempor qui amet. Quia cillum quis proident dolore vero ipsam aspernatur quisquam ab laborum ipsum ipsam.

Reprehenderit obcaecati porro atque modi quisquam aut. Dolorem incididunt laborum sed quisquam ab ex anim. Magni ea irure atque exercitation dolor similique vitae sequi nemo incididunt tempora veniam aliquip nemo. Neque praesentium atque ipsa ad do quaerat nemo aliqua voluptate minim. Ratione inventore lorem sint mollit ipsam ab.

Non illo laborum tempora irure dicta sed magni sit ullamco quasi sed voluptate aspernatur sequi. Cupiditate ad sed duis magna dolor dolorem voluptatum enim ipsa. Adipiscing veniam ut beatae quia. Accusamus quas magni pariatur inventore dolore aspernatur adipiscing fugit laboris quia. Cupiditate quaerat sequi aut nisi ab. Accusamus veniam ex voluptatum dicta minim. Consequat quis reprehenderit officia consequat aspernatur duis numquam provident enim consectetur quae explicabo consequat.'
		];

		$new_article_img = [
			'photo_cover' => [
				'name' => 'cover.jpg',
				'type' => 'text/plain',
				'tmp_name' => __DIR__ . '/../../storage/tests/ini.txt',
				'error' => UPLOAD_ERR_OK,
				'size' => 40000
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->fillForm($new_article, $new_article_img);
		echo "prepare storing...\n";

		$action = $controller->createArticle($_POST, $_FILES, $user['user_id']);

		$this->assertTrue('validation failed' === $action);

		$stmt = $this->db->query("SELECT title, slug FROM articles ORDER BY id DESC LIMIT 1");
		$article = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$this->assertNotEquals($new_article['title'], $article);
	}

	#[Test] #[TestDox("failed create new article because photo path empty but photo cover exist")]
	public function failed_create_new_article_because_photo_path_empty_but_photo_cover_exist(): void
	{
		$this->login($this->new_user, $this->new_user_img);

		$user = $_SESSION['user_info'];

		$controller = new DashboardControllerStub($this->db);

		$new_article = [
			'title' => 'Anim commodo proident ratione nemo deleniti ipsum vero quia do nesciunt odit quasi at ea.',
			'photo_path' => '',
			'body' => 'Anim commodo proident ratione nemo deleniti ipsum vero quia do nesciunt odit quasi at ea. Vitae magna provident quisquam numquam aute tempora id quis deserunt magna tempor qui amet. Quia cillum quis proident dolore vero ipsam aspernatur quisquam ab laborum ipsum ipsam.

Reprehenderit obcaecati porro atque modi quisquam aut. Dolorem incididunt laborum sed quisquam ab ex anim. Magni ea irure atque exercitation dolor similique vitae sequi nemo incididunt tempora veniam aliquip nemo. Neque praesentium atque ipsa ad do quaerat nemo aliqua voluptate minim. Ratione inventore lorem sint mollit ipsam ab.

Non illo laborum tempora irure dicta sed magni sit ullamco quasi sed voluptate aspernatur sequi. Cupiditate ad sed duis magna dolor dolorem voluptatum enim ipsa. Adipiscing veniam ut beatae quia. Accusamus quas magni pariatur inventore dolore aspernatur adipiscing fugit laboris quia. Cupiditate quaerat sequi aut nisi ab. Accusamus veniam ex voluptatum dicta minim. Consequat quis reprehenderit officia consequat aspernatur duis numquam provident enim consectetur quae explicabo consequat.'
		];

		$new_article_img = [
			'photo_cover' => [
				'name' => 'cover.jpg',
				'type' => 'image/jpg',
				'tmp_name' => __DIR__ . '/../../storage/tests/cover_1.jpg',
				'error' => UPLOAD_ERR_OK,
				'size' => 600000
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->fillForm($new_article, $new_article_img);
		echo "prepare storing...\n";

		$action = $controller->createArticle($_POST, $_FILES, $user['user_id']);

		$this->assertTrue('validation failed' === $action);

		$stmt = $this->db->query("SELECT title, slug FROM articles ORDER BY id DESC LIMIT 1");
		$article = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$this->assertNotEquals($new_article['title'], $article);
	}

	#[Test] #[TestDox("Failed create new article because photo path invalid")]
	public function failed_create_new_article_because_photo_path_invalid(): void
	{
		$this->login($this->new_user, $this->new_user_img);

		$user = $_SESSION['user_info'];

		$controller = new DashboardControllerStub($this->db);

		$new_article = [
			'title' => 'Anim commodo proident ratione nemo deleniti ipsum vero quia do nesciunt odit quasi at ea.',
			'photo_path' => 'files/store?expires=1788494943&signature=db9ca3742910c6a926f165c5c2a5fadacfb021b568313bb50695a09892048931',
			'body' => 'Anim commodo proident ratione nemo deleniti ipsum vero quia do nesciunt odit quasi at ea. Vitae magna provident quisquam numquam aute tempora id quis deserunt magna tempor qui amet. Quia cillum quis proident dolore vero ipsam aspernatur quisquam ab laborum ipsum ipsam.

Reprehenderit obcaecati porro atque modi quisquam aut. Dolorem incididunt laborum sed quisquam ab ex anim. Magni ea irure atque exercitation dolor similique vitae sequi nemo incididunt tempora veniam aliquip nemo. Neque praesentium atque ipsa ad do quaerat nemo aliqua voluptate minim. Ratione inventore lorem sint mollit ipsam ab.

Non illo laborum tempora irure dicta sed magni sit ullamco quasi sed voluptate aspernatur sequi. Cupiditate ad sed duis magna dolor dolorem voluptatum enim ipsa. Adipiscing veniam ut beatae quia. Accusamus quas magni pariatur inventore dolore aspernatur adipiscing fugit laboris quia. Cupiditate quaerat sequi aut nisi ab. Accusamus veniam ex voluptatum dicta minim. Consequat quis reprehenderit officia consequat aspernatur duis numquam provident enim consectetur quae explicabo consequat.'
		];

		$new_article_img = [
			'photo_cover' => [
				'name' => 'cover.jpg',
				'type' => 'image/jpg',
				'tmp_name' => __DIR__ . '/../../storage/tests/cover_1.jpg',
				'error' => UPLOAD_ERR_OK,
				'size' => 600000
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->fillForm($new_article, $new_article_img);
		echo "prepare storing...\n";

		$action = $controller->createArticle($_POST, $_FILES, $user['user_id']);

		$this->assertTrue('validation failed' === $action);

		$stmt = $this->db->query("SELECT title, slug FROM articles ORDER BY id DESC LIMIT 1");
		$article = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$this->assertNotEquals($new_article['title'], $article);
	}


	#[Test] #[TestDox("Failed create new article because body too short")]
	public function failed_create_new_article_because_body_too_short(): void
	{
		$this->login($this->new_user, $this->new_user_img);

		$user = $_SESSION['user_info'];

		$controller = new DashboardControllerStub($this->db);

		$new_article = [
			'title' => 'Fugit dolores dicta magna dolores blanditiis excepturi ad incididunt at. Exercitation obcaecati commodo provident in similique nemo sint ullamco vero quis.',
			'photo_path' => '',
			'body' => 'Fugit dolores dicta magna dolores blanditiis excepturi ad incididunt at.'
		];

		$new_article_img = [
			'photo_cover' => [
				'name' => '',
				'type' => '',
				'tmp_name' => '',
				'error' => UPLOAD_ERR_NO_FILE,
				'size' => 0
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->fillForm($new_article, $new_article_img);
		echo "prepare storing...\n";

		$action = $controller->createArticle($_POST, $_FILES, $user['user_id']);

		$this->assertTrue('validation failed' === $action);

		$stmt = $this->db->query("SELECT title, slug FROM articles ORDER BY id DESC LIMIT 1");
		$article = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$this->assertNotEquals($new_article['title'], $article);
	}

	#[Test] #[TestDox("Show article page is accessible")]
	public function show_article_page_is_acessible(): void
	{
		$this->login($this->new_user, $this->new_user_img);

		$user = $_SESSION['user_info'];

		$this->articlesDummy($user['user_id']);

		$controller = new DashboardControllerStub($this->db);
		$generator = new TestArticleModel($this->db);

		$target = $generator->articlesRadomizer();

		$controller->showArticle($target['slug']);

		$this->assertCount(3, $controller->views);
		$this->assertSame('templates/header', $controller->views[0]['view']);
		$this->assertSame('dashboard/show', $controller->views[1]['view']);
		$this->assertSame('templates/footer', $controller->views[2]['view']);
		$this->assertArrayHasKey('article', $controller->views[1]['data']);
		$this->assertCount(4, $controller->views[1]['data']['article']);
		$this->assertSame($target['slug'], $controller->views[1]['data']['article']['slug']);
	}

	#[Test] #[TestDox("Show article page is unaccessible because article not found")]
	public function show_article_is_unaccessible_because_article_not_found(): void
	{
		$this->login($this->new_user, $this->new_user_img);

		$user = $_SESSION['user_info'];

		$this->articlesDummy($user['user_id']);

		$controller = new DashboardControllerStub($this->db);

		$target = 'This is the first article body.';

		$action = $controller->showArticle($target);

		$this->assertTrue($action === 'article not found');
	}


	#[Test] #[TestDox("Edit article page is accessible")]
	public function edit_article_page_is_accessible(): void
	{
		$this->login($this->new_user, $this->new_user_img);

		$user = $_SESSION['user_info'];

		$this->articlesDummy($user['user_id']);

		$controller = new DashboardControllerStub($this->db);
		$generator = new TestArticleModel($this->db);

		$target = $generator->articlesRadomizer();

		$controller->editArticle($target['slug']);
		$this->assertCount(3, $controller->views);
		$this->assertSame('templates/header', $controller->views[0]['view']);
		$this->assertSame('dashboard/edit', $controller->views[1]['view']);
		$this->assertSame('templates/footer', $controller->views[2]['view']);
		$this->assertArrayHasKey('article', $controller->views[1]['data']);
		$this->assertCount(4, $controller->views[1]['data']['article']);
		$this->assertSame($target['slug'], $controller->views[1]['data']['article']['slug']);
	}

	#[Test] #[TestDox("Edit article page is unaccessible because article not found")]
	public function edit_article_page_is_unaccessible_because_article_not_found(): void
	{
		$this->login($this->new_user, $this->new_user_img);

		$user = $_SESSION['user_info'];

		$this->articlesDummy($user['user_id']);

		$controller = new DashboardControllerStub($this->db);

		$target = 'This is the first article body.';

		$action = $controller->editArticle($target);

		$this->assertTrue($action === 'article not found');
	}

	// TODO: simulate file upload
	#[Test] #[TestDox("Sucess update article photo profile")]
	public function success_update_article_photo_cover(): void
	{
		$this->login($this->new_user, $this->new_user_img);

		$user = $_SESSION['user_info'];

		$this->articlesDummy($user['user_id']);

		$controller = new DashboardControllerStub($this->db);
		$generator = new TestArticleModel($this->db);

		$target = $generator->articlesRadomizer();

		$new_article_img = [
			'photo_cover' => [
				'name' => 'cover_3.jpg',
				'type' => 'image/jpg',
				'tmp_name' => __DIR__ . '/../../storage/tests/cover_3.jpg',
				'error' => UPLOAD_ERR_OK,
				'size' => 4000
			]
		];

		$target['photo_path'] = $this->UrlGenerator();
		$target['old_photo_cover'] = $target['photo_cover'];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->fillForm($target, $new_article_img);
		echo "prepare storing...\n";

		$action = $controller->updateArticle($target['slug']);

		$this->assertTrue($action === 'update success');

		$updated_article = $generator->findArticleUser($target['slug'], $user['user_id']);

		$this->assertArrayIsIdenticalToArrayIgnoringListOfKeys($target, $updated_article, ['old_photo_cover', 'photo_cover', 'photo_path']);
		$this->assertTrue($target['old_photo_cover'] != $updated_article['photo_cover']);
	}

	#[Test] #[TestDox("Sucess update article title and slug")]
	public function success_update_article_title_and_slug(): void
	{
		$this->login($this->new_user, $this->new_user_img);

		$user = $_SESSION['user_info'];

		$this->articlesDummy($user['user_id']);

		$controller = new DashboardControllerStub($this->db);
		$generator = new TestArticleModel($this->db);

		$target = $generator->articlesRadomizer();

		$new_article = [
			'title' => 'Sint exercitation nostrud cupiditate dolor praesentium do laborum animi.',
			'photo_path' => '',
			'old_photo_cover' => $target['photo_cover'],
			'body' => $target
		];

		$new_article_img = [
			'photo_cover' => [
				'name' => '',
				'type' => '',
				'tmp_name' => '',
				'error' => UPLOAD_ERR_NO_FILE,
				'size' => 0
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->fillForm($new_article, $new_article_img);
		echo "prepare storing...\n";

		$action = $controller->updateArticle($target['slug']);

		$this->assertTrue($action === 'update success');

		$updated_article = $generator->findByTitle($new_article['title']);

		$this->assertTrue($target['title'] !== $updated_article['title']);
		$this->assertTrue($updated_article['title'] === $new_article['title']);
		$this->assertTrue($target['slug'] !== $updated_article['slug']);
	}

	#[Test] #[TestDox("Success update article body")]
	public function success_update_article_body(): void
	{
		$this->login($this->new_user, $this->new_user_img);

		$user = $_SESSION['user_info'];

		$this->articlesDummy($user['user_id']);

		$controller = new DashboardControllerStub($this->db);
		$generator = new TestArticleModel($this->db);

		$target = $generator->articlesRadomizer();

		$new_article = [
			'title' => $target['title'],
			'photo_path' => '',
			'old_photo_cover' => $target['photo_cover'],
			'body' => 'Duis sit fugit aliqua nulla porro non fugiat sint laboris modi beatae ipsam quae. Voluptatum eius voluptatum ad proident quaerat iusto animi vitae dolores odit corrupti ducimus. Nostrud mollit sequi labore magna sunt magni quae labore cupidatat. Ipsam velit do esse architecto at magni pariatur.'
		];

		$new_article_img = [
			'photo_cover' => [
				'name' => '',
				'type' => '',
				'tmp_name' => '',
				'error' => UPLOAD_ERR_NO_FILE,
				'size' => 0
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->fillForm($new_article, $new_article_img);
		echo "prepare storing...\n";

		$action = $controller->updateArticle($target['slug']);

		$this->assertTrue($action === 'update success');

		$updated_article = $generator->findByTitle($new_article['title']);

		$this->assertTrue($target['body'] !== $updated_article['body']);
		$this->assertTrue($new_article['body'] === $updated_article['body']);
	}

	#[Test] #[TestDox("Success update article but noting has changes")]
	public function success_update_article_but_nothing_has_changes(): Void
	{
		$this->login($this->new_user, $this->new_user_img);

		$user = $_SESSION['user_info'];

		$this->articlesDummy($user['user_id']);

		$controller = new DashboardControllerStub($this->db);
		$generator = new TestArticleModel($this->db);

		$target = $generator->articlesRadomizer();

		$target['old_photo_cover'] = $target['photo_cover'];
		$target['photo_path'] = '';

		$new_article_img = [
			'photo_cover' => [
				'name' => '',
				'type' => '',
				'tmp_name' => '',
				'error' => UPLOAD_ERR_NO_FILE,
				'size' => 0
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->fillForm($target, $new_article_img);
		echo "prepare storing...\n";

		$action = $controller->updateArticle($target['slug']);

		$this->assertTrue($action === 'update success');

		$updated_article = $generator->findByTitle($target['title']);

		$this->assertArrayIsIdenticalToArrayIgnoringListOfKeys($target, $updated_article, ['photo_path', 'old_photo_cover']);
	}

	#[Test] #[TestDox("Failed update article because new title too short")]
	public function failed_update_article_because_new_title_too_short(): void
	{
		$this->login($this->new_user, $this->new_user_img);

		$user = $_SESSION['user_info'];

		$this->articlesDummy($user['user_id']);

		$controller = new DashboardControllerStub($this->db);
		$generator = new TestArticleModel($this->db);

		$target = $generator->articlesRadomizer();

		$new_article = [
			'title' => 'helo',
			'photo_path' => '',
			'body' => $target['body'],
			'old_photo_cover' => $target['photo_cover']
		];

		$new_article_img = [
			'photo_cover' => [
				'name' => '',
				'type' => '',
				'tmp_name' => '',
				'error' => UPLOAD_ERR_NO_FILE,
				'size' => 0
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->fillForm($new_article, $new_article_img);
		echo "prepare storing...\n";

		$action = $controller->updateArticle($target['slug']);

		$this->assertTrue($action === 'validation failed');

		$updated_article = $generator->findByTitle($target['title']);

		$this->assertArrayIsIdenticalToArrayIgnoringListOfKeys($target, $updated_article, ['photo_path', 'old_photo_cover']);
	}

	#[Test] #[TestDox("Failed update article because new title too long")]
	public function failed_update_article_because_new_title_too_long(): void
	{
		$this->login($this->new_user, $this->new_user_img);

		$user = $_SESSION['user_info'];

		$this->articlesDummy($user['user_id']);

		$controller = new DashboardControllerStub($this->db);
		$generator = new TestArticleModel($this->db);

		$target = $generator->articlesRadomizer();

		$new_article = [
			'title' => 'Duis sit fugit aliqua nulla porro non fugiat sint laboris modi beatae ipsam quae. Voluptatum eius voluptatum ad proident quaerat iusto animi vitae dolores odit corrupti ducimus. Nostrud mollit sequi labore magna sunt magni quae labore cupidatat. Ipsam velit do esse architecto at magni pariatur.',
			'photo_path' => '',
			'body' => $target['body'],
			'old_photo_cover' => $target['photo_cover']
		];

		$new_article_img = [
			'photo_cover' => [
				'name' => '',
				'type' => '',
				'tmp_name' => '',
				'error' => UPLOAD_ERR_NO_FILE,
				'size' => 0
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->fillForm($new_article, $new_article_img);
		echo "prepare storing...\n";

		$action = $controller->updateArticle($target['slug']);

		$this->assertTrue($action === 'validation failed');

		$updated_article = $generator->findByTitle($target['title']);

		$this->assertArrayIsIdenticalToArrayIgnoringListOfKeys($target, $updated_article, ['photo_path', 'old_photo_cover']);
	}

	#[Test] #[TestDox("Failed update article because new photo cover too large")]
	public function failed_update_article_because_new_photo_cover_too_large(): void
	{
		$this->login($this->new_user, $this->new_user_img);

		$user = $_SESSION['user_info'];

		$this->articlesDummy($user['user_id']);

		$controller = new DashboardControllerStub($this->db);
		$generator = new TestArticleModel($this->db);

		$target = $generator->articlesRadomizer();

		$new_article = [
			'title' => $target['title'],
			'photo_path' => $this->UrlGenerator(),
			'body' => $target['body'],
			'old_photo_cover' => $target['photo_cover']
		];

		$new_article_img = [
			'photo_cover' => [
				'name' => 'cover_3.jpg',
				'type' => 'image/jpg',
				'tmp_name' => __DIR__ . '/../../storage/tests/cover_3.jpg',
				'error' => UPLOAD_ERR_OK,
				'size' => 550000
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->fillForm($new_article, $new_article_img);
		echo "prepare storing...\n";

		$action = $controller->updateArticle($target['slug']);

		$this->assertTrue($action === 'validation failed');

		$updated_article = $generator->findByTitle($target['title']);

		$this->assertArrayIsIdenticalToArrayIgnoringListOfKeys($target, $updated_article, ['photo_path', 'old_photo_cover']);
	}

	#[Test] #[TestDox("Failed update article because new photo cover not image")]
	public function failed_update_article_because_new_photo_cover_not_image(): void
	{
		$this->login($this->new_user, $this->new_user_img);

		$user = $_SESSION['user_info'];

		$this->articlesDummy($user['user_id']);

		$controller = new DashboardControllerStub($this->db);
		$generator = new TestArticleModel($this->db);

		$target = $generator->articlesRadomizer();

		$new_article = [
			'title' => $target['title'],
			'photo_path' => 'files/store?expires=1787723741&signature=2e9d87ce138526777f3e31d6d6e45196b355b0cedd1c5f20651ed7933db735ce',
			'body' => $target['body'],
			'old_photo_cover' => $target['photo_cover']
		];

		$new_article_img = [
			'photo_cover' => [
				'name' => 'cover_3.jpg',
				'type' => 'image/jpg',
				'tmp_name' => __DIR__ . '/../../storage/tests/cover_3.jpg',
				'error' => UPLOAD_ERR_OK,
				'size' => 5000
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->fillForm($new_article, $new_article_img);
		echo "prepare storing...\n";

		$action = $controller->updateArticle($target['slug']);

		$this->assertTrue($action === 'validation failed');

		$updated_article = $generator->findByTitle($target['title']);

		$this->assertArrayIsIdenticalToArrayIgnoringListOfKeys($target, $updated_article, ['photo_path', 'old_photo_cover']);
	}

	#[Test] #[TestDox("Failed update article because photo path invalid")]
	public function failed_update_article_because_photo_path_invalid(): void
	{
		$this->login($this->new_user, $this->new_user_img);

		$user = $_SESSION['user_info'];

		$this->articlesDummy($user['user_id']);

		$controller = new DashboardControllerStub($this->db);
		$generator = new TestArticleModel($this->db);

		$target = $generator->articlesRadomizer();

		$new_article = [
			'title' => 'helo',
			'photo_path' => '',
			'body' => $target['body'],
			'old_photo_cover' => $target['photo_cover']
		];

		$new_article_img = [
			'photo_cover' => [
				'name' => '',
				'type' => '',
				'tmp_name' => '',
				'error' => UPLOAD_ERR_NO_FILE,
				'size' => 0
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->fillForm($new_article, $new_article_img);
		echo "prepare storing...\n";

		$action = $controller->updateArticle($target['slug']);

		$this->assertTrue($action === 'validation failed');

		$updated_article = $generator->findByTitle($target['title']);

		$this->assertArrayIsIdenticalToArrayIgnoringListOfKeys($target, $updated_article, ['photo_path', 'old_photo_cover']);
	}

	#[Test] #[TestDox("Failed update article because new body too short")]
	public function failed_update_article_because_new_body_too_short(): void
	{
		$this->login($this->new_user, $this->new_user_img);

		$user = $_SESSION['user_info'];

		$this->articlesDummy($user['user_id']);

		$controller = new DashboardControllerStub($this->db);
		$generator = new TestArticleModel($this->db);

		$target = $generator->articlesRadomizer();

		$new_article = [
			'title' => $target['title'],
			'photo_path' => '',
			'body' => "hello world",
			'old_photo_cover' => $target['photo_cover']
		];

		$new_article_img = [
			'photo_cover' => [
				'name' => '',
				'type' => '',
				'tmp_name' => '',
				'error' => UPLOAD_ERR_NO_FILE,
				'size' => 0
			]
		];

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->fillForm($new_article, $new_article_img);
		echo "prepare storing...\n";

		$action = $controller->updateArticle($target['slug']);

		$this->assertTrue($action === 'validation failed');

		$updated_article = $generator->findByTitle($target['title']);

		$this->assertArrayIsIdenticalToArrayIgnoringListOfKeys($target, $updated_article, ['photo_path', 'old_photo_cover']);
	}

	#[Test] #[TestDox("Success delete article")]
	public function success_delete_article(): void
	{
		$this->login($this->new_user, $this->new_user_img);

		$user = $_SESSION['user_info'];

		$this->articlesDummy($user['user_id']);

		$controller = new DashboardControllerStub($this->db);
		$generator = new TestArticleModel($this->db);

		$target = $generator->articlesRadomizer();

		$action = $controller->deleteArticle($target['slug']);

		$this->assertTrue($action === 'article deleted');

		$search = $controller->showArticle($target['slug']);

		$this->assertTrue($search === 'article not found');

		$status_article = $generator->isArticleDeleted($target['slug'], $user['user_id']);

		$this->assertTrue($status_article);
	}

	#[Test] #[TestDox("Failed delete article because not found")]
	public function failed_delete_article_because_not_found(): void
	{
		$this->login($this->new_user, $this->new_user_img);

		$user = $_SESSION['user_info'];

		$this->articlesDummy($user['user_id']);

		$controller = new DashboardControllerStub($this->db);

		$target = [
			'title' => 'Welcome to the blog',
			'slug' => 'welcome-to-the-blog',
			'body' => 'This is the first article body.',
			'photo_cover' => ''
		];

		$action = $controller->deleteArticle($target['slug']);

		$this->assertTrue($action === 'article not found');
	}

	#[Override]
	protected function tearDown(): void
	{
		$this->db = null;
		$_SERVER = [];
		$_POST = [];
		$_FILES = [];
		session_unset();
		session_destroy();
		parent::tearDown();
	}
}
