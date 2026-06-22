<?php

/**
 * Core bootstrap file
 * Loads all environment variables and core components
 */

function loadData(string $path) {
	if (!file_exists($path)) {
		throw new RuntimeException(".env file not found at {$path}");
	}

	$lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

	foreach($lines as $line) {
		$line = trim($line);

		// Skip comment and empty lines
		if ($line === '' || str_starts_with($line, '#')) {
			continue;
		}

		// Slipt into key and value
		[$name, $value] = array_map('trim', explode('=', $line, 2));

		// Remove surrounding quotes if any
		$value = trim($value, "\"'");

		// Set environment variables
		$_ENV[$name] = $value;
		putenv("{$name}={$value}");
	}
}

// Call loader at the very start
try {
	loadData(__DIR__ . '/../.env');
} catch (RuntimeException $e) {
	die($e->getMessage());
}

require_once 'core/App.php';
require_once 'core/Controller.php';
require_once 'core/Database.php';
require_once 'core/Flasher.php';
require_once 'config/app.php';