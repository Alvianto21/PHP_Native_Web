// configTest.php
<?php

// Start output buffering
ob_start();

// Include the config file to test connection
include 'config.php';

// Get the output
$output = ob_get_clean();

// Check if connection was successful
if (strpos($output, "Connection successful") !== false) {
	echo "Test Passed: Connection successful\n";
} elseif (strpos($output, "Connection failed") !== false) {
	echo "Test Failed: " . $output . "\n";
} else {
	echo "Test Indeterminate: Unexpected output\n";
}
?>