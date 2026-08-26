<?php

require_once __DIR__ . '/../../app/init.php';
require_once __DIR__ . '/../../app/controllers/HomeController.php';

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class TestArticleModel {
	private array $articles = [[
		'title' => 'Welcome to the blog',
		'slug' => 'welcome-to-the-blog',
		'body' => 'This is the first article body.',
		'author' => 'admin',
		'photo_cover' => '',
	]];

	public function count(): int {
		return count($this->articles);
	}

	public function paginator(int $limit, int $offset): array {
		return array_slice($this->articles, $offset, $limit);
	}

	public function findArticle(string $slug): ?array {
		foreach ($this->articles as $article) {
			if ($article['slug'] === $slug) {
				return $article;
			}
		}

		return null;
	}
}

class HomePageControllerStub extends HomeController {
	public array $views = [];

	public function model($model) {
		return new TestArticleModel();
	}

	public function view($view, $data = []) {
		$this->views[] = ['view' => $view, 'data' => $data];
	}
}

#[TestDox("Home Controller")]
class HomeTest extends TestCase {
	#[Test] #[TestDox("Home page is accessible")]
	public function home_page_is_accessible(): void {
		$controller = new HomePageControllerStub();

		$controller->index();

		$this->assertCount(3, $controller->views);
		$this->assertSame('templates/header', $controller->views[0]['view']);
		$this->assertSame('homes/home', $controller->views[1]['view']);
		$this->assertSame('templates/footer', $controller->views[2]['view']);
		$this->assertArrayHasKey('articles', $controller->views[1]['data']);
		$this->assertCount(1, $controller->views[1]['data']['articles']);
		$this->assertSame('Welcome to the blog', $controller->views[1]['data']['articles'][0]['title']);
	}

	#[Test] #[TestDox('Detail page is accessible')]
	public function detail_page_is_accessible(): void {
		$controller = new HomePageControllerStub();

		$controller->detail('welcome-to-the-blog');

		$this->assertCount(3, $controller->views);
		$this->assertSame('templates/header', $controller->views[0]['view']);
		$this->assertSame('homes/detail', $controller->views[1]['view']);
		$this->assertSame('templates/footer', $controller->views[2]['view']);
		$this->assertArrayHasKey('article', $controller->views[1]['data']);
		$this->assertSame('welcome-to-the-blog', $controller->views[1]['data']['article']['slug']);
		$this->assertSame('This is the first article body.', $controller->views[1]['data']['article']['body']);
	}

	#[Test] #[TestDox("Detail page is unaccessible")]
	public function detail_page_is_unaccessible(): void {
		$controller = new HomePageControllerStub();

		$article = $controller->model('Article')->findArticle('article-does-not-exist');

		$this->assertNull($article);
	}
}