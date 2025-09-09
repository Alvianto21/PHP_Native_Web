<?php

// Start session
if (!session_id()) session_start();

// Memanggil semua file app
require_once '../app/init.php';

// Jalankan aplikasi
$app = new App;