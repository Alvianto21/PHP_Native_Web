<?php

/**
 * Base controller providing convenience helpers for loading models and views.
 */
class Controller {
	/**
	 * Load and instantiate a model class.
	 *
	 * @param string $model Model class name (file located in app/models)
	 * @return object An instance of the requested model
	 */
	public function model($model) {
		require_once __DIR__ . '/../models/' . $model . '.php';
		return new $model;
	}

	/**
	 * Load a view file and optionally pass data to it.
	 *
	 * @param string $view View path relative to app/views (without .php)
	 * @param array $data Optional associative array of data to expose to the view
	 * @return void
	 */
	public function view($view, $data = []) {
		require_once __DIR__ . '/../views/' . $view . '.php';
	}
}