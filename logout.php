<?php
// ===============================================
// LOGOUT SCRIPT
// Skrip ini bertanggung jawab untuk mengakhiri sesi pengguna.
// ===============================================

// 1. MULAI ATAU LANJUTKAN SESI
// Selalu panggil session_start() sebelum mengakses variabel $_SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. HANCURKAN DATA SESI YANG TERSIMPAN DI SERVER
// Ini akan menghapus semua data sesi.
$_SESSION = []; // Opsional: Bersihkan array session agar tidak ada nilai tersisa

// 3. HAPUS COOKIE SESI DARI BROWSER (Jika menggunakan cookie untuk sesi)
// Dapatkan parameter cookie sesi
$params = session_get_cookie_params();
// Set cookie kadaluarsa di masa lalu
setcookie(session_name(), '', time() - 42000,
    $params["path"], $params["domain"],
    $params["secure"], $params["httponly"]
);

// 4. HANCURKAN SESI SECARA KESELURUHAN
session_destroy();

// 5. REDIRECT PENGGUNA KE HALAMAN LOGIN
// Ganti 'login.php' dengan nama file halaman login Anda.
header('Location: home.php');
exit();