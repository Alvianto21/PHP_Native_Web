<?php

class App {
	// Mendefinisikan kontroller default
	protected $controller = "HomeController";
	protected $method = "index";
	protected $params = [];

	public function __construct() {
		$url = $this->paseURL();

		// Controller name by url
		$controlName = ucfirst($url[0]) . 'Controller';

		// Cek apakah controller ada
		if (file_exists(__DIR__ . '/../../app/controllers/' . $controlName . '.php')) {
			// jika ada, set sebagai controller
			$this->controller = $controlName;
			unset($url[0]);
		}
		
		// Load controller
		require_once __DIR__ . '/../../app/controllers/' . $this->controller . '.php';
		$this->controller = new $this->controller;

		// Cek method
		if (isset($url[1])) {
			if (method_exists($this->controller, $url[1])) {
				$this->method = $url[1];
				unset($url[1]);
			}
		}

		// Cek parameter
		if (!empty($url)) {
			$this->params = array_values($url);
		}

		// Jalankan controller & method serta kirim params jika ada
		call_user_func_array([$this->controller, $this->method], $this->params);
	}
	
	// Routing
	public function paseURL() {
		$request = $_SERVER['REQUEST_URI'];
		$request = str_replace('/public/', '', $request);
		$request = rtrim($request, '/');
		$request = filter_var($request, FILTER_SANITIZE_URL);
		$request = explode('/', $request);
		return $request;
	}
}