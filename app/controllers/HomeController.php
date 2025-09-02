<?php

class HomeController extends Controller{
	public function index() {
		$data['articles'] = $this->model('Article')->all();
		echo "heloo, I home controll";
		return $data;
	}
}