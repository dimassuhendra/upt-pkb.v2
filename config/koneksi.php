<?php
// ===============================================
// FILE: config/koneksi.php
// Konfigurasi Koneksi Database Menggunakan PHP PDO
// ===============================================

define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'db_upt');

try {
    // Buat objek PDO
    $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    
    // Set mode error PDO ke Exception untuk penanganan error yang lebih baik
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Opsional: Pastikan koneksi menggunakan encoding UTF8
    $pdo->exec("set names utf8");
    
} catch(PDOException $e) {
    // Tampilkan pesan error jika koneksi gagal
    die("KONEKSI GAGAL: " . $e->getMessage());
}

// Catatan: Setelah file ini di-include, variabel $pdo akan tersedia untuk query.
?>