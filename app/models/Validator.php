<?php

class Validator {
	private $errors = [];
	// validasi data
	public function validate($data, $form) {
		// 
	}

	// bersihkan data
	public function clearData($data) {
		$data = trim($data);
		$data = stripslashes($data);
		$data = htmlspecialchars($data);
		return $data;
	}
	
}