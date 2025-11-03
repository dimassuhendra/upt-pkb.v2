<?php
// ===============================================
// ENDPOINT API UNTUK MENGAMBIL DETAIL LAPORAN SURVEY (JSON)
// File ini ditempatkan di folder: proses/
// ===============================================

header('Content-Type: application/json');

// Pastikan path koneksi ini benar!
require_once '../../config/koneksi.php'; 

$response = ['status' => 'error', 'message' => 'ID Kendaraan tidak valid.'];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_kendaraan = $_GET['id'];

    try {
        // Ambil Data Kendaraan, Petugas, dan Survey
        $sql = "
            SELECT 
                k.plat_nomor, k.nomor_pendaftaran, k.nama_pemilik_kendaraan,
                p.nama_petugas,
                s.rating_pelayanan, s.rating_fasilitas, s.rating_kecepatan, s.komentar, s.filled_at
            FROM 
                kendaraan k
            INNER JOIN 
                survey s ON k.id_kendaraan = s.id_kendaraan
            LEFT JOIN 
                petugas p ON k.id_petugas = p.id_petugas
            WHERE 
                k.id_kendaraan = :id_kendaraan AND k.status_survey = 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id_kendaraan', $id_kendaraan, PDO::PARAM_INT);
        $stmt->execute();
        $data_laporan = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data_laporan) {
            $response['data'] = $data_laporan;
            $response['status'] = 'success';
            $response['message'] = 'Laporan berhasil dimuat.';
        } else {
            $response['message'] = 'Laporan survei tidak ditemukan atau belum diisi.';
        }

    } catch (PDOException $e) {
        $response['message'] = 'Kesalahan database: ' . $e->getMessage();
    }
}

echo json_encode($response);
exit;
?>