<?php

class Validator
{
	/**
	 * Error massages.
	 */
	private array $errors = [];

	/**
	 * database connection.
	 */
	private ?PDO $db = null;

	/**
	 * Determine whether the given value is considered empty for validation.
	 *
	 * This handles strings, arrays (including uploaded file arrays), null values,
	 * and empty values consistently so validation rules behave predictably.
	 *
	 * @param mixed $value The value to inspect.
	 * @return bool True when the value is empty or represents no uploaded file.
	 */
	private function isEmptyValue(mixed $value): bool
	{
		if ($value === null) {
			return true;
		}

		if (is_string($value)) {
			return trim($value) === '';
		}

		if (is_array($value)) {
			if (array_key_exists('error', $value)) {
				return (int) ($value['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK;
			}

			foreach ($value as $item) {
				if ($item !== null && $item !== '' && $item !== []) {
					return false;
				}
			}

			return true;
		}

		return empty($value);
	}

	/**
	 * Determine whether the given value contains a real submitted value.
	 *
	 * This is the opposite of isEmptyValue() and is useful for rules such as
	 * required_if where the presence of another field should be checked safely.
	 *
	 * @param mixed $value The value to inspect.
	 * @return bool True when the value is present and not empty.
	 */
	private function hasRealValue(mixed $value): bool
	{
		return !$this->isEmptyValue($value);
	}

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

				if (!array_key_exists($field, $data) && !in_array($rule, ['required', 'required_if'], true)) {
					continue;
				}

				switch ($rule) {
					case 'required':
						if ($this->isEmptyValue($value)) {
							$this->addError($field, $rule, "The '{$field}' is required.");
						}
						break;
					case 'min':
						if (strlen((string) $value) < $ruleValue) {
							$this->addError($field, $rule, "The '{$field}' must at least $ruleValue characters.");
						}
						break;
					case 'max':
						if (strlen((string) $value) > $ruleValue) {
							$this->addError($field, $rule, "The '{$field}' may not exceed $ruleValue characters.");
						}
						break;
					case 'email':
						if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
							$this->addError($field, $rule, "The '{$field}' must be a valid email.");
						}
						break;
					case 'regex':
						$resultCheck = preg_match($ruleValue, (string) $value);
						if ($resultCheck === 0) {
							$this->addError($field, $rule, "The '{$field}' format is invalid.");
						} elseif ($resultCheck === false) {
							$message = "Failed to check regex in field {$field} with rule {$ruleValue}.";
							error_log($message);
							throw new Exception($message);
							break;
						}
						break;
					case 'match':
						if ((string) $value !== (string) ($data[$ruleValue] ?? '')) {
							$this->addError($field, $rule, "The '{$field}' must match $ruleValue.");
							break;
						}
					case "required_if":
						$other = $ruleValue;
						$otherValue = $data[$other] ?? null;

						if ($this->hasRealValue($otherValue) && $this->isEmptyValue($value)) {
							$this->addError($field, $rule, "The '{$field}' is required when $other is present.");
						}
						break;
					case 'boolean':
						if (!in_array($value, ['0', '1'], true)) {
							$this->addError($field, $rule, "The '{$field}' input is invalid.");
						}
						break;
					case 'role_user':
						if (!in_array($value, $ruleValue, true)) {
							$this->addError($field, $rule, "The '{$field}' input is invalid.");
						}
						break;
					case "url":
						// recommended to update to PHP 8.5 and use Uri\Rfc3986\Uri class instead.
						if ($this->hasRealValue($value)) {
							$isValidUrl = filter_var($value, FILTER_VALIDATE_URL) !== false;

							if (!$isValidUrl) {
								$parsedUrl = parse_url($value);
								$isValidUrl = $parsedUrl !== false
									&& !empty($parsedUrl['scheme'])
									&& !empty($parsedUrl['host'])
									&& in_array(
										strtolower($parsedUrl['scheme']),
										['http', 'https'],
										true
									);
							}
							echo "{$rule}: {$isValidUrl}.\n";
							if (!$isValidUrl) {
								echo "error added.\n";
								$this->addError($field, $rule, "The '{$field}' must be a valid URL.");
							}
						}
						break;
					case "signature":
						if ($this->hasRealValue($value)) {
							$urlStatus = $this->validateSignUrl($value);

							if ($urlStatus !== true) {
								$this->addError($field, $rule, "The '{$field}' {$urlStatus}");
							}
						}
						break;
					case "size":
						$file = $_FILES[$field] ?? null;
						if ($this->hasRealValue($file) && ($file["size"] ?? 0) > $ruleValue) {
							$this->addError($field, $rule, "The '{$field}' file is too large.");
						}
						break;
					case "img":
						$file = $_FILES[$field] ?? null;

						if (!$this->hasRealValue($file)) {
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

						$allowMime = ["image/jpeg", "image/png"];
						$allowExt = ["jpg", "jpeg", "png"];

						if (!in_array($imgTypeFile, $allowExt, true) || !in_array($imgMime, $allowMime, true)) {
							$this->addError($field, $rule, "The '{$field}' only JPG, PNG, or JPEG.");
						}
						break;
					case 'unique':
						/**
						 * This rule use:
						 *  - callable: function($value): bool  -> returns TRUE if value EXISTS
						 * because database connection only be set by database helper class or class that extending database helper. 
						 */
						try {
							$exist = (bool) call_user_func($ruleValue, $value);
						} catch (Throwable $th) {
							$exist = true;
						}

						if ($exist) {
							$this->addError($field, $rule, "The '{$field}' has already been taken.");
						}
						break;
					default:
						// Log the rule and trow exception
						$massage = "Unsupported validation rule '{$rule}' for field '{$field}'. ";
						error_log($massage);
						throw new Exception($massage);
						break;
				}
			}
		}

		return empty($this->errors);
	}

	/**
	 * Validate temp sign URL.
	 * Return true if success.
	 * @param string $url - Sign URL.
	 * @return bool|string - Return true or error massage.
	 */
	public function validateSignUrl(string $url)
	{
		$secret = $_ENV["APP_KEY"] ?: 'no-value';
		$query = parse_url($url, PHP_URL_QUERY) ?? '';
		parse_str($query, $signUrlData);

		$path = $signUrlData['path'] ?? '';
		$expired = (int)($signUrlData['expires'] ?? 0);
		$signature = $signUrlData['sig'] ?? $signUrlData['signature'] ?? '';

		$expectedForm = hash_hmac('sha256', (string) $expired, $secret);
		$expectedShow = hash_hmac('sha256', $path . $expired, $secret);

		if ($expired < time()) {
			error_log("Time: " . (string) time() . " Input: " . (string) $expired);
			return "Link expired.";
		} elseif (hash_equals($expectedForm, $signature) || hash_equals($expectedShow, $signature)) {
			return true;
		} else {
			error_log("Signature: " . $expectedForm ?? $expectedShow . " Input: " . $signature);
			return "Signature Invalid.";
		}
	}

	/**
	 * Sanitize input form.
	 * @param string $data Form data.
	 * @return string Return sanitize data.
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
	 * Add input errors massage.
	 * @param string $field Input field.
	 * @param string $rule Validation rule.
	 * @param string $massage Error massage.
	 * @return void
	 */
	private function addError(string $field, string $rule, string $massage)
	{
		$this->errors[$field][$rule] = $massage;
	}
}
