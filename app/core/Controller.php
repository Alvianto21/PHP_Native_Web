<?php

class Controller {
	// deklarasi model
	public function model($model) {
		require_once __DIR__ . '/../models/' . $model . '.php';
		return new $model;
	}
}