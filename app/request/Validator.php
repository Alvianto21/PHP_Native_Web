<?php

class Validator
{
	private array $errors = [];
	/**
	 * Form validations
	 * @param array $data - Form data
	 * @param array $rules - Validation rules
	 * @return bool
	 */
	public function validate(array $data, array $rules): bool
	{
		$this->errors = [];

		foreach ($rules as $field => $fieldRules) {
			$value = $data[$field] ?? null;

			foreach ($fieldRules as $rule => $ruleValue) {
				if (is_numeric($rule)) {
					$rule = $fieldRules;
					$ruleValue = null;
				}

				switch ($rule) {
					case 'required':
						if (empty($value) && $value !== '0') {
							$this->addError($field, "The $field is required.");
						}
						break;
					case 'min':
						if (strlen($value) < $ruleValue) {
							$this->addError($field, "The $field must at least $ruleValue characters.");
						}
						break;
					case 'max':
						if (strlen($value) > $ruleValue) {
							$this->addError($field, "The $field may not exceed $ruleValue characters.");
						}
						break;
					case 'email':
						if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
							$this->addError($field, "The $field must be a valid email.");
						}
						break;
					case 'regex':
						if (!preg_match($ruleValue, $value)) {
							$this->addError($field, "The $field format is invalid.");
						}
						break;
					case 'match':
						if ($value !== $data[$ruleValue]) {
							$this->addError($field, "The $field must match $ruleValue.");
							break;
						}
					case "required_if":
						$other = $ruleValue;
						$otherValue = $data[$other] ?? null;

						if (!empty($otherValue) && (empty($value) && $value !== '0')) {
							$this->addError($field, "The $field is required when $other is present.");
						}
						break;
					case "signature":
						if (!empty($value)) {
							$isValidUrl = filter_var($value, FILTER_VALIDATE_URL);

							if (!$isValidUrl) {
								$parsedUrl = parse_url($value);
								$isValidUrl = $parsedUrl !== false && !empty($parsedUrl['scheme']) && !empty($parsedUrl['host']);
							}
							if (!$isValidUrl) {
								$this->addError($field, "The $field must be a valid URL.");
							}
						}
						break;
					case "size":
						$file = $_FILES[$field] ?? null;
						if ($file && ($file["error"] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK && ($file["size"] ?? 0) > $ruleValue) {
							$this->addError($field, "The $field file is too large.");
						}
						break;
					case "img":
						$file = $_FILES[$field] ?? null;

						if (!$file || ($file["error"] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
							break;
						}

						// Get file ext
						$imgName = basename($file["name"] ?? '');
						$imgTypeFile = strtolower(pathinfo($imgName, PATHINFO_EXTENSION));

						// Get MIME file
						$imgTem = $file['tmp_name'];
						$imgMime = null;

						if (!empty($imgTem) && is_file($imgTem)) {
							$img_info = finfo_open(FILEINFO_MIME_TYPE);
							$imgMime = finfo_file($img_info, $imgTem);
							unset($img_info);
						}

						$allowMime = ["image/jpg", "image/jpeg", "image/png"];
						$allowExt = ["jpg", "jpeg", "png"];

						if (!in_array($imgTypeFile, $allowExt, true) || !in_array($imgMime, $allowMime, true)) {
							$this->addError($field, "The $field only JPG, PNG, or JPEG");
						}
						break;
					default:
						// plan if rules not found
						break;
				}
			}
		}

		return empty($this->errors);
	}

	/**
	 * Sanitize input form
	 * @param string $data - Form data
	 * @return string
	 */
	public function clearData(string $data)
	{
		$data = trim($data);
		$data = stripslashes($data);
		$data = htmlspecialchars($data);
		return $data;
	}

	/**
	 * Check if input form has data. 
	 * Only use if all input is required.
	 * @param array $data - Form data
	 * @return bool
	 */
	public function checkData(array $data): bool
	{
		foreach ($data as $value) {
			if (!empty($value)) {
				return false;
			}
		}

		return true;
	}

	public function errors()
	{
		return $this->errors;
	}

	/**
	 * Add input errors massage
	 * @param string $field - input field
	 * @param string $massage - error massage
	 * @return void
	 */
	private function addError(string $field, string $massage)
	{
		$this->errors[$field] = $massage;
	}
}
