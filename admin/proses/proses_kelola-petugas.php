<?php
// ===============================================
// CONTROLLER: PENANGANAN AKSI TAMBAH, EDIT, DAN HAPUS PETUGAS
// File ini ditempatkan di folder: proses/
// ===============================================

require_once '../../config/koneksi.php'; 

// Cek aksi dari GET parameter
$aksi = strtolower($_GET['aksi'] ?? '');
$id_petugas = $_POST['id_petugas'] ?? $_GET['id'] ?? null; 

if (empty($aksi)) {
    header('Location: ../kelola-petugas.php?status=error&pesan=Aksi tidak valid.');
    exit;
}

try {
    // ... LOGIKA HAPUS (Tidak Berubah) ...
    // =======================================================
    // 1. LOGIKA HAPUS DATA PETUGAS
    // =======================================================
    if ($aksi === 'hapus') {
        if (!$id_petugas) {
            header('Location: ../kelola-petugas.php?status=error&pesan=ID Petugas tidak ditemukan.');
            exit;
        }

        $stmt_check = $pdo->prepare("SELECT nama_petugas, id_users FROM petugas WHERE id_petugas = :id");
        $stmt_check->bindParam(':id', $id_petugas, PDO::PARAM_INT);
        $stmt_check->execute();
        $data_petugas = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if (!$data_petugas) {
            header('Location: ../kelola-petugas.php?status=error&pesan=Data tidak ditemukan.');
            exit;
        }

        $nama_petugas = $data_petugas['nama_petugas'];
        $id_users = $data_petugas['id_users'];

        $pdo->beginTransaction();

        $stmt_delete_petugas = $pdo->prepare("DELETE FROM petugas WHERE id_petugas = :id");
        $stmt_delete_petugas->bindParam(':id', $id_petugas, PDO::PARAM_INT);
        $stmt_delete_petugas->execute();

        // Hapus data dari tabel 'users' (dengan asumsi ON DELETE CASCADE)
        if ($id_users) {
            $stmt_delete_users = $pdo->prepare("DELETE FROM users WHERE id_users = :id");
            $stmt_delete_users->bindParam(':id', $id_users, PDO::PARAM_INT);
            $stmt_delete_users->execute();
        }

        $pdo->commit();
        
        $pesan = 'Data petugas ' . htmlspecialchars($nama_petugas) . ' berhasil dihapus. Data login terkait juga dihapus.';
        header('Location: ../kelola-petugas.php?status=sukses&pesan=' . urlencode($pesan));
        exit;
    }

    // =======================================================
    // 2. LOGIKA EDIT DATA PETUGAS
    // =======================================================
    if ($aksi === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        
        $nama_petugas = $_POST['nama_petugas'] ?? null;
        
        if (!$id_petugas || !$nama_petugas) {
            $pesan_error = 'Data wajib (Nama Petugas) tidak lengkap.';
            header('Location: ../kelola-petugas.php?status=error&pesan=' . urlencode($pesan_error));
            exit;
        }
        
        // Query UPDATE PETUGAS (hanya nama petugas)
        $sql_update = "
            UPDATE petugas 
            SET 
                nama_petugas = :nama
            WHERE id_petugas = :id_petugas";
        
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->bindParam(':nama', $nama_petugas);
        $stmt_update->bindParam(':id_petugas', $id_petugas, PDO::PARAM_INT);
        $stmt_update->execute();

        $pesan = 'Data petugas ' . htmlspecialchars($nama_petugas) . ' berhasil diperbarui.';
        header('Location: ../kelola-petugas.php?status=sukses&pesan=' . urlencode($pesan));
        exit;
    }

    // =======================================================
    // 3. LOGIKA TAMBAH DATA PETUGAS
    // =======================================================
    if ($aksi === 'tambah' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        
        $nama_petugas = $_POST['nama_petugas'] ?? null;
        $username = $_POST['username'] ?? null;
        $email = $_POST['email'] ?? null;
        $password = $_POST['password'] ?? null;
        $role = 'petugas'; // Menggunakan 'role'
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        if (!$nama_petugas || !$username || !$password) {
            $pesan_error = 'Data wajib (Nama, Username, Password) tidak lengkap.';
            header('Location: ../kelola-petugas.php?status=error&pesan=' . urlencode($pesan_error));
            exit;
        }

        $pdo->beginTransaction();
        
        // 1. Insert ke tabel Users (nama_lengkap diisi dengan nama_petugas)
        $sql_insert_user = "INSERT INTO users (username, nama_lengkap, email, password, role) VALUES (:user, :nama_lengkap, :email, :pass, :role)";
        $stmt_user = $pdo->prepare($sql_insert_user);
        $stmt_user->bindParam(':user', $username);
        $stmt_user->bindParam(':nama_lengkap', $nama_petugas); // Menggunakan nama_petugas sebagai nama_lengkap
        $stmt_user->bindParam(':email', $email);
        $stmt_user->bindParam(':pass', $hashed_password);
        $stmt_user->bindParam(':role', $role);
        $stmt_user->execute();
        $last_id_user = $pdo->lastInsertId();

        // 2. Insert ke tabel Petugas (tanpa no_telepon)
        $sql_insert_petugas = "INSERT INTO petugas (nama_petugas, id_users) VALUES (:nama, :id_user)";
        $stmt_petugas = $pdo->prepare($sql_insert_petugas);
        $stmt_petugas->bindParam(':nama', $nama_petugas);
        $stmt_petugas->bindParam(':id_user', $last_id_user, PDO::PARAM_INT);
        $stmt_petugas->execute();

        $pdo->commit();

        $pesan = 'Petugas ' . htmlspecialchars($nama_petugas) . ' berhasil ditambahkan dengan username: ' . htmlspecialchars($username);
        header('Location: ../kelola-petugas.php?status=sukses&pesan=' . urlencode($pesan));
        exit;
    }
    
    // Jika aksi tidak dikenal
    header('Location: ../kelola-petugas.php?status=error&pesan=Aksi yang diminta tidak dikenali.');
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($e->getCode() == 23000) {
        $pesan_error = 'Gagal: Username atau Email sudah digunakan.';
    } else {
        $pesan_error = 'Kesalahan Database: ' . $e->getMessage();
    }
    
    header('Location: ../kelola-petugas.php?status=error&pesan=' . urlencode($pesan_error));
    exit;
}
?>