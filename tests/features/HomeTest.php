<?php

require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/controllers/HomeController.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/Sessions.php';
require_once __DIR__ . '/../helpers/UserLogin.php';
require_once __DIR__ . '/../helpers/TestArticleModel.php';
require_once __DIR__ . '/../helpers/TestUsersModel.php';

use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

// class TestArticleModel {
// 	private array $articles = [[
// 		'title' => 'Welcome to the blog',
// 		'slug' => 'welcome-to-the-blog',
// 		'body' => 'This is the first article body.',
// 		'author' => 'admin',
// 		'photo_cover' => '',
// 	]];

// 	public function count(): int {
// 		return count($this->articles);
// 	}

// 	public function paginator(int $limit, int $offset): array {
// 		return array_slice($this->articles, $offset, $limit);
// 	}

// 	public function findArticle(string $slug): ?array {
// 		foreach ($this->articles as $article) {
// 			if ($article['slug'] === $slug) {
// 				return $article;
// 			}
// 		}

// 		return null;
// 	}
// }

class HomePageControllerStub extends HomeController {
	public function __construct(private ?PDO $db = null)
	{
		//
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

	public function view($view, $data = []) {
		$this->views[] = ['view' => $view, 'data' => $data];
	}
}

#[TestDox("Home Controller")]
class HomeTest extends TestCase {
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

	#[Override]
	protected function setUp(): void
	{
		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

		echo "starting sessions.\n";
		$sessionHandler = new TestSessions($this->db);
		session_set_save_handler($sessionHandler, true);
		if (!session_id()) session_start();
		echo "session started.\n";

		$user = $this->generateDataUsers();
		$this->generateArticle($user['user_id']);
	}

	public function generateDataUsers() {
		$controller = new HelperLogin($this->db);
		$generator = new TestUsersModel($this->db);

		echo "generate data user.\n";
		$generator->usersGenerator();
		$user = $generator->userRandomizer();
		$userData = [
			'email' => $user['email'],
			'password' => $user['password']
		];

		echo "login to {$user['username']}.\n";
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$statusLogin = $controller->userLogin($userData);
		
		if ($statusLogin !== 'login success' && !isset($_SESSION['user_info'])) {
			echo $statusLogin;
			die($statusLogin);
		}

		unset($_SERVER['REQUEST_METHOD']);
		return $_SESSION['user_info'];
	}

	public function generateArticle(int $user_id) {
		echo "Creating data dummy for articles...\n";

		$articleGenerator = new TestArticleModel($this->db);
		$statusArticles = $articleGenerator->articleGenerator($user_id);

		if ($statusArticles !== "articles created") {
			echo $statusArticles;
			die($statusArticles);
		}

		echo "articles data generated.\n";
	}

	#[Test] #[TestDox("Home page is accessible")]
	public function home_page_is_accessible(): void {
		$controller = new HomePageControllerStub($this->db);

		$controller->index();

		$this->assertCount(3, $controller->views);
		$this->assertSame('templates/header', $controller->views[0]['view']);
		$this->assertSame('homes/home', $controller->views[1]['view']);
		$this->assertSame('templates/footer', $controller->views[2]['view']);
		$this->assertArrayHasKey('articles', $controller->views[1]['data']);
		$this->assertCount(5, $controller->views[1]['data']['articles']);
	}

	#[Test] #[TestDox('Detail page is accessible')]
	public function detail_page_is_accessible(): void {
		$controller = new HomePageControllerStub($this->db);
		$generator = new TestArticleModel($this->db);

		$target = $generator->articlesRadomizer();

		$controller->detail($target['slug']);

		$this->assertCount(3, $controller->views);
		$this->assertSame('templates/header', $controller->views[0]['view']);
		$this->assertSame('homes/detail', $controller->views[1]['view']);
		$this->assertSame('templates/footer', $controller->views[2]['view']);
		$this->assertArrayHasKey('article', $controller->views[1]['data']);
		$this->assertSame($target['slug'], $controller->views[1]['data']['article']['slug']);
		$this->assertSame($target['title'], $controller->views[1]['data']['article']['title']);
	}

	#[Test] #[TestDox("Detail page is unaccessible")]
	public function detail_page_is_unaccessible(): void {
		$controller = new HomePageControllerStub($this->db);

		$target = 'This-is-the-first-article-body';

		$article = $controller->model('Article')->findArticle($target);

		$this->assertFalse($article);
	}
}