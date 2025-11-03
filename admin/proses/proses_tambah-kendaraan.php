<?php
// PASTIKAN TIDAK ADA SPASI/NEWLINE DI ATAS BARIS INI

header('Content-Type: application/json');

// Jika Anda masih melihat error PHP, aktifkan ini (Hanya saat DEBUGGING):
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// Pastikan hanya permintaan POST yang diproses
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    echo json_encode(['status' => 'error', 'pesan' => 'Akses tidak diizinkan.']);
    exit;
}

// Sertakan file koneksi PDO
require_once '../../config/koneksi.php'; 

// Ambil dan validasi data dari form
$plat_nomor = isset($_POST['plat_nomor']) ? trim($_POST['plat_nomor']) : null;
$tanggal_pendaftaran = isset($_POST['tanggal_pendaftaran']) ? $_POST['tanggal_pendaftaran'] : null;
$nomor_pendaftaran = isset($_POST['nomor_pendaftaran']) ? trim($_POST['nomor_pendaftaran']) : null;
$merk = isset($_POST['merk']) ? trim($_POST['merk']) : null;
$tipe = isset($_POST['tipe']) ? trim($_POST['tipe']) : null;
$jenis_kendaraan = isset($_POST['jenis_kendaraan']) ? trim($_POST['jenis_kendaraan']) : null;
$nama_pemilik_kendaraan = isset($_POST['nama_pemilik_kendaraan']) ? trim($_POST['nama_pemilik_kendaraan']) : null;
$id_petugas = isset($_POST['id_petugas']) ? (int)$_POST['id_petugas'] : null;

$status_survey = 0; // Default: Belum Survey

if (empty($plat_nomor) || empty($tanggal_pendaftaran) || empty($id_petugas)) {
    echo json_encode(['status' => 'error', 'pesan' => 'Data wajib (Plat Nomor, Tanggal, Petugas) tidak lengkap.']);
    exit;
}

try {
    // Query SQL dengan PDO Prepared Statement
    $sql = "INSERT INTO kendaraan (plat_nomor, tanggal_pendaftaran, nomor_pendaftaran, merk, tipe, jenis_kendaraan, nama_pemilik_kendaraan, id_petugas, status_survey) 
            VALUES (:plat_nomor, :tanggal_pendaftaran, :nomor_pendaftaran, :merk, :tipe, :jenis_kendaraan, :nama_pemilik_kendaraan, :id_petugas, :status_survey)";
    
    $stmt = $pdo->prepare($sql);

    // Binding parameter
    $stmt->bindParam(':plat_nomor', $plat_nomor);
    $stmt->bindParam(':tanggal_pendaftaran', $tanggal_pendaftaran);
    $stmt->bindParam(':nomor_pendaftaran', $nomor_pendaftaran);
    $stmt->bindParam(':merk', $merk);
    $stmt->bindParam(':tipe', $tipe);
    $stmt->bindParam(':jenis_kendaraan', $jenis_kendaraan);
    $stmt->bindParam(':nama_pemilik_kendaraan', $nama_pemilik_kendaraan);
    $stmt->bindParam(':id_petugas', $id_petugas, PDO::PARAM_INT);
    $stmt->bindParam(':status_survey', $status_survey, PDO::PARAM_INT);

    if ($stmt->execute()) {
        // Sukses
        echo json_encode([
            'status' => 'sukses', 
            'pesan' => "Data kendaraan Plat $plat_nomor berhasil ditambahkan."
        ]);
        exit;
    } else {
        // Gagal eksekusi query (jarang terjadi di sini jika DB sudah OK)
        echo json_encode(['status' => 'error', 'pesan' => 'Gagal menyimpan data. Query tidak berhasil dieksekusi.']);
        exit;
    }

} catch (PDOException $e) {
    // Tangkap error database (Foreign Key, Duplikat, dll.)
    
    $error_message = $e->getMessage();
    $pesan_spesifik = 'Terjadi kesalahan database: Gagal menyimpan data.';
    
    // Memberikan pesan yang lebih user-friendly untuk error umum
    if (strpos($error_message, 'Duplicate entry') !== false) {
        $pesan_spesifik = 'Gagal: Plat Nomor sudah terdaftar.';
    } elseif (strpos($error_message, 'foreign key constraint fails') !== false) {
        // Ini adalah error Foreign Key yang sangat mungkin terjadi (tabel petugas kosong)
        $pesan_spesifik = 'Gagal: Petugas Lapangan yang dipilih tidak valid atau belum ada data di tabel Petugas.';
    }
    
    echo json_encode(['status' => 'error', 'pesan' => $pesan_spesifik]);
    exit;
}