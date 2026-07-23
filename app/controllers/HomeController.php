<?php

class HomeController extends Controller{
	// home page
	public function index() {
		require_once __DIR__ . '/../request/UploadImage.php';

		$uploader = new UploadImage();
		
		// Set pagination
		$perPage = 5;
		$totalPage = $this->model('Article')->count();
		$maxPage = ceil($totalPage / $perPage);
		$data['total'] = $maxPage;

		// // cari hakaman saat ini
		$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
		$page = max(1, min($page, $maxPage));
		$startPage = ($page - 1) * $perPage;
		$data['pages'] = $page;

		// ambil data berserta offset
		$data['articles'] = $this->model('Article')->paginator($perPage, $startPage);
		
		if ($totalPage !== 0) {
			foreach($data['articles'] as &$article) {
				if (!empty($article['photo_cover'])) {
					$article['photo_cover'] = $uploader->show($article['photo_cover']);
				}
			}
		}

		$data['judul'] = 'Halaman Home';
		$data['style'] = "blog.css";

		$this->view('templates/header', $data);
		$this->view('homes/home', $data);
		$this->view('templates/footer');
	}

	// detail article page
	public function detail(string $slug) {
		require_once __DIR__ . '/../request/UploadImage.php';

		$uploader = new UploadImage();

		$data['judul'] = 'Detail article';
		$data['style'] = "blog.css";
		$article = $this->model('Article')->findArticle($slug);

		if ($article) {
			if (!empty($article['photo_cover'])) {
				$article['photo_cover'] = $uploader->show($article['photo_cover'], 300);
			}
			
			$data['article'] = $article;
		} else {
			header('LOCATION: ' . BASEURL);
			exit;
		}

		$this->view('templates/header', $data);
		$this->view('homes/detail', $data);
		$this->view('templates/footer');
	}
}