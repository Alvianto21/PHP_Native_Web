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
		$sigUrl = ABSOLUTURL . 'files/store?expires=' . $expired . '&sig=' . $signature;

		header('Content-Type: application/json');
		echo json_encode(['url' => $sigUrl]);
		exit;
	}

	public function show() {
		require_once __DIR__ . '/../request/Validator.php';

		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			http_response_code(405);
			echo "Method not allow.";
			exit;
		}

		$validator = new Validator();

		// Determine the protocol (HTTP or HTTPS), Add the protocol separator, Get the host name, Get the requested path
		$requestUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
        . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
        . ($_SERVER['REQUEST_URI'] ?? '/');

		// Get file path
		$path = $_GET['path'] ?? '';
		// error_log("URL: ". $path);

		$validateUrl = $validator->validateSignUrl($requestUrl);

		if ($validateUrl !== true) {
			http_response_code(403);
			echo json_encode(['errors' => $validateUrl]);
			exit($validateUrl);
		}

		$file = dirname(__DIR__, 2) . '/storage/uploads/' . $path;

		if (!file_exists($file)) {
			http_response_code(404);
			echo json_encode(['errors' => "File not found."]);
			exit;
		}

		$file_info = finfo_open(FILEINFO_MIME_TYPE);

		header('Content-Type: ' . finfo_file($file_info, $file));
		header('Content-Length: ' . filesize($file));
		header('X-Accel-Redirect: /protected-files/' . $path);
		exit;
	}
}
