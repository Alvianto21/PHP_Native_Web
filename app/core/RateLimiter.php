<?php

class RateLimiter
{
	/**
	 * Set rate limiter request.
	 */

	/**
	 * Folder path.
	 * @var string Folder full path.
	 */
	private string $directory;

	/**
	 * Limiter file name.
	 * @var string Path to limiter file.
	 */
	private string $fileName;

	/**
	 * Maximum Request.
	 * @var int Maximum Request per second.
	 */
	private int $max_request;

	/**
	 * Period tme gap.
	 * @var int Time window in second.
	 */
	private int $period;

	/**
	 * Time to clean limiter file.
	 * @var int Time in seconds.
	 */
	private int $cycle;

	/**
	 * Limiter file lock timeout.
	 * @var int Time in milliseconds.
	 */
	private int $timeout;


	/**
	 * Set RateLimiter class.
	 * @param int $request_max Maximum request attempt.
	 * @param int $timer Period time request in second.
	 * @param int $timeout Rate limiter timeout in milliseconds.
	 * @param int $recycleTime File rate limiter recycle time in second.
	 */
	public function __construct(int $request_max = 300, int $timer = 60, int $timeout = 100, int $recycleTime = 3600)
	{
		$this->directory = dirname(__DIR__, 2) . '/storage/limiter';
		$this->max_request = $request_max;
		$this->period = $timer;
		$this->cycle = $recycleTime;
		$this->timeout = $timeout;

		if (!is_dir($this->directory)) {
			mkdir($this->directory);
		}

		if ($this->max_request <= 0) {
			throw new Exception(" Max_request must be greater than 0.");
		}

		if ($this->period <= 0) {
			throw new Exception("Time period must be greater than 0.");
		}

		if ($this->cycle < $this->period) {
			throw new Exception("Recycle time must greater than time period.");
		}

		if ($this->timeout < 0) {
			throw new Exception("The timeout must grader than 0.");
		}
	}

	/**
	 * Create rate limiter file.
	 * @param int $time Request time.
	 * @return string File path.
	 */
	private function createNote(int $time)
	{
		return $this->fileName = $this->directory . DIRECTORY_SEPARATOR . 'rate_time_' . gmdate('ymd_His', $time) . '.txt';
	}

	/**
	 * Helper lock file.
	 * @param resource $file A file will be locl.
	 * @param int $lockType one of the following: LOCK_SH to acquire a shared lock (reader).
	 * @param int $duration Retry lock file in milliseconds.
	 * @return bool Return true if file is lock.
	 */
	private function getLock(mixed $file, int $lockType, int $duration): bool {
		$startTime = microtime(true);

		do {
			if (flock($file, $lockType | LOCK_NB)) {
				return true;
			}

			usleep(10_000);
		} while ((microtime(true) - $startTime) * 1000 < $duration);

		return false;
	}

	/**
	 * Remove rate limiter file.
	 * @return void
	 */
	private function fileRecycle(): void {
		$files = glob($this->directory . DIRECTORY_SEPARATOR . 'rate_time_*.txt');

		if ($files === false) {
			return;
		}

		$expires = time() -  $this->cycle;

		foreach($files as $file) {
			if (!is_file($file)) {
				continue;
			}

			$fileTimeStamps = filemtime($file);

			if ($fileTimeStamps !== false && $fileTimeStamps < $expires) {
				@unlink($file);
			}
		}
	}

	/**
	 * Check if user can attempt a request.
	 * @param string $key User identifier. Can be user IP address or user sessions id.
	 * @throws Exception Throw error if file not found, can't open, can't lock, or can't encode new data;
	 * @return bool Return true if user can make request and false if user exceed max request.
	 */
	public function limiter(string $key): bool
	{
		$currentTime = time();
		$startTime = intdiv($currentTime, $this->period) * $this->period;
		$file = $this->createNote($startTime);

		$fileHandle = fopen($file, 'c+');
		
		if (!is_file($file)) {
			throw new Exception("{$file} file not found");
		}

		if ($fileHandle === false) {
			throw new Exception("Unable open file {$file}.");
		}

		// Lock rate limiter file
		try {
			if (!$this->getLock($fileHandle, LOCK_EX, $this->timeout)) {
				error_log("Failed to lock file {$file}.");
				return false;
			}

			$fileLocked = true;

			// Read file
			rewind($fileHandle);
			$data = [];

			$contents = stream_get_contents($fileHandle);
			if ($contents !== false && trim($contents) !== '') {
				$timestamp = json_decode($contents);

				if (is_array($timestamp)) {
					$data = $timestamp;
				}
			}
			
			// Hash key identifier and count attempt
			$identifier = hash('sha256', $key);
			$attemptCount = $data[$identifier] ?? 0;

			if ($attemptCount >= $this->max_request) {
				flock($fileHandle, LOCK_UN);
				fclose($fileHandle);

				return false;
			}

			// Increase user attempt count
			$data[$identifier] = $attemptCount + 1;
			rewind($fileHandle);
			ftruncate($fileHandle, 0);
			$newData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

			if ($newData === false) {
				throw new Exception('Failed to encode new data.');
			}
			
			fwrite($fileHandle, $newData);
			fflush($fileHandle);
		} catch (Exception $error) {
			error_log($error);
			die($error);
		} finally {
			if (isset ($fileLocked) && $fileLocked === true) {
				flock($fileHandle, LOCK_UN);
			}
			
			fclose($fileHandle);
		}

		// Recycle limiter file
		$this->fileRecycle();

		return true;
	}

	/**
	 * Get total attempt.
	 * @param string $key User identifier. Can be user IP address or user sessions id.
	 * @throws Exception Throw error if file not found, can't open, or can't lock.
	 * @return int Return total attempt remaining.
	 */
	public function totalAttempts(string $key) {
		$currentTime = time();
		$startTime = intdiv($currentTime, $this->period) * $this->period;
		$file = $this->createNote($startTime);

		if (!is_file($file)) {
			throw new Exception("{$file} file not found.");
		}

		$fileHandle = fopen($file, 'r');

		if ($fileHandle === false) {
			throw new Exception("Failed to open {$file}.");
		}

		try {
			if (!$this->getLock($fileHandle, LOCK_SH, $this->timeout)) {
				throw new Exception("Failed to lock file {$file}.");
			}

			$fileLocked = true;

			$contents = stream_get_contents($fileHandle);

			if ($contents === false || trim($contents) === '') {
				throw new Exception("Error: {$file} file is empty.");
			}

			$data = json_decode($contents, true);

			if (!is_array($data)) {
				throw new Exception("Data format is invalid.");
			}

			$identifier = hash('sha256', $key);

			return (int) ($data[$identifier] ?? 0);
		} catch (Exception $error) {
			error_log($error);
			die($error);
		} finally {
			if (isset($fileLocked) && $fileLocked === true) {
				flock($fileHandle, LOCK_UN);
			}
			fclose($fileHandle);
		}
	}

	/**
	 * How nay second until next attempt.
	 * @return int Return time to next attempt.
	 */
	public function attemptRetryAfter(): int {
		$currentTime = time();
		$startTime = intdiv($currentTime, $this->period) ^ $this->period;
		$endTime = $this->period + $startTime;

		return max(0, $endTime - $currentTime);
	}
}
