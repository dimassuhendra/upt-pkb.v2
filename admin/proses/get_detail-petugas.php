<?php
// ===============================================
// ENDPOINT API UNTUK MENGAMBIL DETAIL PETUGAS (JSON)
// File ini ditempatkan di folder: proses/
// ===============================================

header('Content-Type: application/json');

// Pastikan path koneksi ini benar!
require_once '../../config/koneksi.php'; 

$response = ['status' => 'error', 'message' => 'ID Petugas tidak valid.'];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_petugas = $_GET['id'];

    try {
        // Ambil Detail Data Petugas, gabungkan dengan tabel users untuk informasi login
        $sql_petugas = "
            SELECT 
                p.id_petugas, p.nama_petugas, p.created_at,
                u.id_users, u.username, u.email, u.nama_lengkap, u.role
            FROM 
                petugas p
            LEFT JOIN 
                users u ON p.id_users = u.id_users
            WHERE 
                p.id_petugas = :id_petugas";
        
        $stmt_petugas = $pdo->prepare($sql_petugas);
        $stmt_petugas->bindParam(':id_petugas', $id_petugas, PDO::PARAM_INT);
        $stmt_petugas->execute();
        $data_petugas = $stmt_petugas->fetch(PDO::FETCH_ASSOC);

        if ($data_petugas) {
            $response['data'] = $data_petugas;
            $response['status'] = 'success';
            $response['message'] = 'Data berhasil dimuat.';
        } else {
            $response['message'] = 'Data petugas tidak ditemukan.';
        }

    } catch (PDOException $e) {
        $response['message'] = 'Kesalahan database: ' . $e->getMessage();
    }
}

echo json_encode($response);
exit;
?>