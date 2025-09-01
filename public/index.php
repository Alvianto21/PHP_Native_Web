<?php

// Memanggil semua file app
require_once '../app/init.php';
require_once '../config.php';

// Jalankan aplikasi
$app = new App;
$db = $conn;