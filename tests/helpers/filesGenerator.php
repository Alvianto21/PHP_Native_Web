<?php 

class FilesGenerator {
	private array $tempFiles = [];

	private array $binaryData = [
		0 => [
			'type' => 'png',
			'data' => 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/F6sAAAAASUVORK5CYII='
		],
		1 => [
			'type' => 'jpg',
			'data' => '/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAABAAEDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwDi6KKK+ZP3E//Z'
		]
	];

	public function createTempFile(string $ext, string $content): string {
		echo "creating fake image file...\n";
		$path = tempnam(sys_get_temp_dir(), "phpunit_test_");
		$file = "{$path}.{$ext}";

		$status_rename = rename($path, $file);

		if ($status_rename === false) {
			throw new RuntimeException("Failed to rename a file.");
		}

		file_put_contents($file, $content);

		$this->tempFiles[] = $file;
		echo "success creating file in {$file}.\n";
		return $file;
	}
	
	public function cleaningFiles(): void {
		$tempFiles = $this->tempFiles;
		$this->tempFiles = [];

		foreach ($tempFiles as $file) {
			if(is_file($file)) {
				unlink($file);
			}
		}
	}

	public function dataRandom(): array {
		$target = array_rand($this->binaryData, 1);
		$data = $this->binaryData[$target];
		$data['data'] = base64_decode($data['data'], true);

		if ($data['data'] === false) {
			throw new RuntimeException('Failed to decode the test image.');
		}
		
		return $data;
	}
}