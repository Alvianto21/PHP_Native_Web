<?php

class UploadController extends Controller {
	public function generate() {
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			http_response_code(405);
			echo json_encode(['error' => 'Method Not Allowed']);
			return;
		}

		$secretKey = getenv('APP_KEY') ?: 'change-me';
		$expired = time() + 300;
		$signature = hash_hmac('sha256', (string) $expired, $secretKey);
		$sigUrl = ABSOLUTURL . 'login/store/?expires=' . $expired . '&sig=' . $signature;

		header('Content-Type: application/json');
		echo json_encode(['url' => $sigUrl]);
		exit;
	}
}
