<?php

// Memanggil semua file app
require_once '../app/init.php';

// Register database session handler
$sessionHandler = new Sessions();
session_set_save_handler($sessionHandler, true);

// Start session
if (!session_id()) session_start();

// Jalankan aplikasi
$app = new App;