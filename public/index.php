<?php

// Memanggil semua file app
require_once '../app/init.php';

// Jalankan aplikasi
$app = new App;
// $db = $conn;
$controller = new HomeController();
$data = $controller->index();
echo "<pre>";
print_r($data['articles']);
echo "</pre>";