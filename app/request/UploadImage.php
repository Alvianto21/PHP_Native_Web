<?php

class UploadImage
{
	private string $storage;

	/**
	 * Target Upload folder
	 */
	public function __construct()
	{
		$this->storage = dirname(__DIR__, 2) . '/storage/uploads/';
	}

	/**
	 * Store file.
	 * @param array $file File from form.
	 * @param string $folder Location where file is store.
	 * @return string Relative file path.
	 */
	public function store(array $file, string $folder): string
	{
		$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
		$newFilename = bin2hex(random_bytes(16)) . '.' . $extension;
		$relativePath = $folder . '/' . $newFilename;
		$destination = $this->storage . $relativePath;

		// If the folder not exist, create the folder
		if (!is_dir(dirname($destination))) {
			mkdir(dirname($destination), 0755, true);
		}

		move_uploaded_file($file['tmp_name'], $destination);

		return $relativePath;
	}

	/**
	 * Show file.
	 * @param string $path File path.
	 * @param int $expire Expired time.
	 * @return string Temp URL file path.
	 */
	public function show(string $path, int $expire = 600): string
	{
		$expires = time() + $expire;

		$signature = hash_hmac('sha256', $path . (string) $expires, getenv('APP_KEY'));

		return ABSOLUTURL .  "files/show?path=" . urldecode($path) . "&expires=$expires" . "&signature=$signature";
	}

	/**
	 * Update file.
	 * @param array $file File from form.
	 * @param string $path Relative path old file.
	 * @param string $folder Location where file store.
	 * @return string Relative file path.
	 */
	public function update(array $file, string $path, string $folder): string
	{
		$oldFile = $this->storage . $path;
		
		if (file_exists($oldFile) || !empty($path)) {
			unlink($oldFile);
			
			$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
			$newFilename = bin2hex(random_bytes(16)) . '.' . $extension;
			$relativePath = $folder . '/' . $newFilename;
			$destination = $this->storage . $relativePath;

			// If the folder not exist, create the folder
			if (!is_dir(dirname($destination))) {
				mkdir(dirname($destination), 0755, true);
			}

			move_uploaded_file($file['tmp_name'], $destination);

			return $relativePath;
		} else {
			return $path;
		}
	}

	
	public function delete(string $path):void {
		$file = $this->storage . $path;

		if (file_exists($file)) {
			unlink($file);
		} else {
			error_log("File with path " . $path . " not found.");
		}
	}
}
