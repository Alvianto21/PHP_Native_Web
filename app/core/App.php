<?php

/**
 * Application front controller and router.
 *
 * Parses the request URL, loads the appropriate controller, and executes
 * the resolved method with any parameters.
 */
class App {
	/** @var string Default controller class name */
	protected $controller = "HomeController";

	/** @var string Default method name */
	protected $method = "index";

	/** @var array Route parameters extracted from the URL */
	protected $params = [];

	/**
	 * App constructor.
	 *
	 * Resolves the incoming request into a controller, method and parameters,
	 * then invokes the resolved controller action.
	 *
	 * @return void
	 */
	public function __construct() {
		$url = $this->paseURL();

		// Controller name by url
		if (!empty($url[0])) {
			$controlName = ucfirst($url[0]) . 'Controller';

			if (file_exists(__DIR__ . '/../../app/controllers/' . $controlName . '.php')) {
				// jika ada, set sebagai controller
				$this->controller = $controlName;
				$url = array_values(array_slice($url, 1));
			}
		}

		// Cek apakah controller ada
        
		// Load controller
		require_once __DIR__ . '/../../app/controllers/' . $this->controller . '.php';
		$this->controller = new $this->controller;

		// Cek method
		if (isset($url[0])) {
			if (method_exists($this->controller, $url[0])) {
				$this->method = $url[0];
				$url = array_values(array_slice($url, 1));
			}
		}

		// Cek parameter
		$this->params = $url;
		call_user_func_array([$this->controller, $this->method], $this->params);
		// if (!empty($url)) {
		// 	$this->params = array_values($url);
		// }

		// Jalankan controller & method serta kirim params jika ada
		call_user_func_array([$this->controller, $this->method], $this->params);
	}
    
	/**
	 * Parse the current request URI into an array of segments.
	 *
	 * @return string[] Array of URL segments
	 */
	public function paseURL() {
		$request = $_SERVER['REQUEST_URI'] ?? '';
		$path = parse_url($request, PHP_URL_PATH ?? '/');
		
		$path = preg_replace('#^/public/?#', '/', $path);
		$path = trim($path, '/');

		return $path === '' ? [] : explode('/', $path);
	}
}