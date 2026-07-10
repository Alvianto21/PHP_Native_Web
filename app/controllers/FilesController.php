<?php

class FilesController extends Controller {
	/**
	 * Generate temp sign URL for form
	 * @return void - return temp sign URL
	 */
	public function signUrl() {
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			http_response_code(405);
			echo json_encode(['error' => 'Method Not Allowed']);
			return;
		} 

		$secretKey = getenv('APP_KEY') ?: 'change-me';
		$expired = time() + 300; // 5 mins
		$signature = hash_hmac('sha256', (string) $expired, $secretKey);
		$sigUrl = ABSOLUTURL . 'file/store?expires=' . $expired . '&sig=' . $signature;

		header('Content-Type: application/json');
		echo json_encode(['url' => $sigUrl]);
		exit;
	}
}
