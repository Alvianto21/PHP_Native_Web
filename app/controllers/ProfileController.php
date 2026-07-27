<?php 

class ProfileController extends Controller {
	// View profile
	public function index() {
		require_once __DIR__ . '/../request/UploadImage.php';

		// cek login
		if (!isset($_SESSION['user_info'])) {
			header('LOCATION: ' . ABSOLUTURL . 'login');
			exit;
		}

		$uploader = new UploadImage();

		$data['judul'] = 'Halaman Profile';
		$data['style'] = "profile.css";
		$data['user'] = $this->model('Users')->show($_SESSION['user_info']['user_id']);

		if (!empty($data['user']['photo_profile'])) {
			$data['user']['photo_profile'] = $uploader->show($data['user']['photo_profile']);
		}

		$this->view('templates/header', $data);
		$this->view('profile/index', $data);
		$this->view('templates/footer');
	}
}