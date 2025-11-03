<?php
// ===============================================
// CONTROLLER: PENANGANAN AKSI HAPUS DAN EDIT KENDARAAN
// File ini ditempatkan di folder: proses/
// ===============================================

// Pastikan path koneksi ini benar! 
// Jika file ini di folder 'proses', maka koneksi.php harus 2 level di atas di folder 'config'.
require_once '../../config/koneksi.php'; 

// --- Cek apakah ada aksi yang diminta ---
if (!isset($_GET['aksi'])) {
    header('Location: ../kelola-kendaraan.php?status=error&pesan=Aksi tidak valid.');
    exit;
}

$aksi = strtolower($_GET['aksi']);
$id_kendaraan = $_POST['id_kendaraan'] ?? $_GET['id'] ?? null; // Ambil ID dari POST atau GET

try {
    // =======================================================
    // 1. LOGIKA HAPUS DATA KENDARAAN (Aksi dari Tombol Hapus)
    // =======================================================
    if ($aksi === 'hapus') {
        if (!$id_kendaraan) {
            header('Location: ../kelola-kendaraan.php?status=error&pesan=ID Kendaraan tidak ditemukan.');
            exit;
        }

        // Ambil data sebelum hapus
        $stmt_check = $pdo->prepare("SELECT plat_nomor FROM kendaraan WHERE id_kendaraan = :id");
        $stmt_check->bindParam(':id', $id_kendaraan, PDO::PARAM_INT);
        $stmt_check->execute();
        $data_kendaraan = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if (!$data_kendaraan) {
            header('Location: ../kelola-kendaraan.php?status=error&pesan=Data tidak ditemukan.');
            exit;
        }

        // Hapus data
        $stmt_delete = $pdo->prepare("DELETE FROM kendaraan WHERE id_kendaraan = :id");
        $stmt_delete->bindParam(':id', $id_kendaraan, PDO::PARAM_INT);
        $stmt_delete->execute();

        $pesan = 'Data kendaraan Plat Nomor ' . htmlspecialchars($data_kendaraan['plat_nomor']) . ' berhasil dihapus.';
        header('Location: ../kelola-kendaraan.php?status=sukses&pesan=' . urlencode($pesan));
        exit;
    }

    // =======================================================
    // 2. LOGIKA EDIT DATA KENDARAAN (Aksi dari Submit Modal Edit)
    // =======================================================
    if ($aksi === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        
        // Cek dan ambil data POST
        $plat_nomor = $_POST['plat_nomor'] ?? null;
        $nomor_pendaftaran = $_POST['nomor_pendaftaran'] ?? null; 
        $nama_pemilik = $_POST['nama_pemilik_kendaraan'] ?? null;
        $merk = $_POST['merk'] ?? null;
        $tipe = $_POST['tipe'] ?? null;
        $jenis = $_POST['jenis_kendaraan'] ?? null;
        $id_petugas = $_POST['id_petugas'] ?? null;

        if (!$id_kendaraan || !$plat_nomor || !$id_petugas) {
            $pesan_error = 'Data wajib tidak lengkap. (Plat Nomor/Petugas)';
            header('Location: ../kelola-kendaraan.php?status=error&pesan=' . urlencode($pesan_error));
            exit;
        }
        
        // Query UPDATE
        $sql_update = "
            UPDATE kendaraan 
            SET 
                plat_nomor = :plat,
                nomor_pendaftaran = :no_daftar,
                nama_pemilik_kendaraan = :pemilik,
                merk = :merk,
                tipe = :tipe,
                jenis_kendaraan = :jenis,
                id_petugas = :id_petugas
            WHERE id_kendaraan = :id_kendaraan";
        
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->bindParam(':plat', $plat_nomor);
        $stmt_update->bindParam(':no_daftar', $nomor_pendaftaran);
        $stmt_update->bindParam(':pemilik', $nama_pemilik);
        $stmt_update->bindParam(':merk', $merk);
        $stmt_update->bindParam(':tipe', $tipe);
        $stmt_update->bindParam(':jenis', $jenis);
        $stmt_update->bindParam(':id_petugas', $id_petugas, PDO::PARAM_INT);
        $stmt_update->bindParam(':id_kendaraan', $id_kendaraan, PDO::PARAM_INT);
        
        $stmt_update->execute();

        $pesan = 'Data kendaraan Plat Nomor ' . htmlspecialchars($plat_nomor) . ' berhasil diperbarui.';
        header('Location: ../kelola-kendaraan.php?status=sukses&pesan=' . urlencode($pesan));
        exit;
    }
    
    // Jika aksi tidak dikenal atau tidak melalui POST
    header('Location: ../kelola-kendaraan.php?status=error&pesan=Aksi yang diminta tidak dikenali atau metode salah.');
    exit;

} catch (PDOException $e) {
    // Penanganan error database
    $pesan_error = 'Kesalahan Database pada aksi ' . strtoupper($aksi) . ': ' . $e->getMessage();
    header('Location: ../kelola-kendaraan.php?status=error&pesan=' . urlencode($pesan_error));
    exit;
}
?>