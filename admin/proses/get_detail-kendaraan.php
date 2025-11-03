<?php
// ===============================================
// ENDPOINT API UNTUK MENGAMBIL DETAIL KENDARAAN (JSON)
// File ini ditempatkan di folder: proses/
// ===============================================

// Atur header untuk merespon dalam format JSON
header('Content-Type: application/json');

// Pastikan path koneksi ini benar!
require_once '../../config/koneksi.php'; 

$response = ['status' => 'error', 'message' => 'ID Kendaraan tidak valid.'];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_kendaraan = $_GET['id'];

    try {
        // 1. Ambil Detail Data Kendaraan + Petugas
        $sql_kendaraan = "
            SELECT 
                k.*, p.nama_petugas 
            FROM 
                kendaraan k
            LEFT JOIN 
                petugas p ON k.id_petugas = p.id_petugas
            WHERE 
                k.id_kendaraan = :id_kendaraan";
        
        $stmt_kendaraan = $pdo->prepare($sql_kendaraan);
        $stmt_kendaraan->bindParam(':id_kendaraan', $id_kendaraan, PDO::PARAM_INT);
        $stmt_kendaraan->execute();
        $data_kendaraan = $stmt_kendaraan->fetch(PDO::FETCH_ASSOC);

        if ($data_kendaraan) {
            $response['data'] = $data_kendaraan;
            $response['status'] = 'success';
            $response['message'] = 'Data berhasil dimuat.';
            $response['survey'] = null;

            // 2. Ambil Data Survey jika status_survey = 1
            if ($data_kendaraan['status_survey'] == 1) {
                $sql_survey = "
                    SELECT 
                        rating_pelayanan, rating_fasilitas, rating_kecepatan, komentar, filled_at 
                    FROM 
                        survey 
                    WHERE 
                        id_kendaraan = :id_kendaraan";
                
                $stmt_survey = $pdo->prepare($sql_survey);
                $stmt_survey->bindParam(':id_kendaraan', $id_kendaraan, PDO::PARAM_INT);
                $stmt_survey->execute();
                $data_survey = $stmt_survey->fetch(PDO::FETCH_ASSOC);

                if ($data_survey) {
                    $response['survey'] = $data_survey;
                }
            }
            
        } else {
            $response['message'] = 'Data kendaraan tidak ditemukan.';
        }

    } catch (PDOException $e) {
        $response['message'] = 'Kesalahan database: ' . $e->getMessage();
    }
}

echo json_encode($response);
exit;
?>