<?php

class Validator {
	// validasi data
	public function validate(array $data, array $rules) {
		
	}

	// bersihkan data
	public function clearData($data) {
		$data = trim($data);
		$data = stripslashes($data);
		$data = htmlspecialchars($data);
		return $data;
	}
	
}