<?php
// ===============================================
// CONTROLLER: PENANGANAN FORMULIR SURVEY
// File ini ditempatkan di folder: proses/
// ===============================================

// Path koneksi (Asumsi: koneksi.php ada di dua level di atas di folder config/)
require_once '../config/koneksi.php'; 

$pesan_error = '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $pesan_error = 'Metode akses tidak diizinkan.';
    header('Location: ../index.php?status=error&pesan=' . urlencode($pesan_error));
    exit;
}

// Ambil dan validasi data POST
$id_kendaraan = $_POST['id_kendaraan'] ?? null;
$rating_pelayanan = $_POST['rating_pelayanan'] ?? null;
$rating_fasilitas = $_POST['rating_fasilitas'] ?? null;
$rating_kecepatan = $_POST['rating_kecepatan'] ?? null;
$komentar = $_POST['komentar'] ?? null;

// Cek data wajib
if (!$id_kendaraan || !$rating_pelayanan || !$rating_fasilitas || !$rating_kecepatan) {
    $pesan_error = 'Harap isi semua rating sebelum mengirim survei.';
    header('Location: ../index.php?status=error&pesan=' . urlencode($pesan_error));
    exit;
}

// Validasi nilai rating (pastikan antara 1 dan 5)
if (!in_array($rating_pelayanan, [1, 2, 3, 4, 5]) || 
    !in_array($rating_fasilitas, [1, 2, 3, 4, 5]) || 
    !in_array($rating_kecepatan, [1, 2, 3, 4, 5])) {
    $pesan_error = 'Nilai rating tidak valid.';
    header('Location: ../index.php?status=error&pesan=' . urlencode($pesan_error));
    exit;
}

try {
    // Mulai Transaksi Database
    $pdo->beginTransaction();

    // 1. INSERT data ke tabel survey
    $sql_insert = "
        INSERT INTO survey (id_kendaraan, rating_pelayanan, rating_fasilitas, rating_kecepatan, komentar, filled_at) 
        VALUES (:id_kendaraan, :pelayanan, :fasilitas, :kecepatan, :komentar, NOW())";
    
    $stmt_insert = $pdo->prepare($sql_insert);
    $stmt_insert->bindParam(':id_kendaraan', $id_kendaraan, PDO::PARAM_INT);
    $stmt_insert->bindParam(':pelayanan', $rating_pelayanan, PDO::PARAM_INT);
    $stmt_insert->bindParam(':fasilitas', $rating_fasilitas, PDO::PARAM_INT);
    $stmt_insert->bindParam(':kecepatan', $rating_kecepatan, PDO::PARAM_INT);
    $stmt_insert->bindParam(':komentar', $komentar);
    $stmt_insert->execute();

    // 2. UPDATE status_survey di tabel kendaraan
    $sql_update = "
        UPDATE kendaraan 
        SET status_survey = 1 
        WHERE id_kendaraan = :id_kendaraan";

    $stmt_update = $pdo->prepare($sql_update);
    $stmt_update->bindParam(':id_kendaraan', $id_kendaraan, PDO::PARAM_INT);
    $stmt_update->execute();

    // Commit Transaksi
    $pdo->commit();

    // Redirect ke halaman index dengan status sukses
    header('Location: ../index.php?status=sukses');
    exit;

} catch (PDOException $e) {
    // Rollback jika ada error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $pesan_error = 'Kesalahan saat memproses data: ' . $e->getMessage();
    header('Location: ../index.php?status=error&pesan=' . urlencode($pesan_error));
    exit;
}
?>