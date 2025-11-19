<?php
// Mulai sesi. Ini penting untuk menyimpan status login pengguna.
session_start();

// Panggil file koneksi database dari folder config
// Asumsi: File ini berada di proses/ dan koneksi.php berada di config/
require_once '../config/koneksi.php'; 

// ===============================================
// 1. TANGKAP DATA DAN VALIDASI AWAL
// ===============================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Pastikan semua input ada
    if (empty(trim($_POST["email"])) || empty(trim($_POST["password"])) || empty(trim($_POST["role"]))) {
        $_SESSION['login_error'] = "Email, password, dan peran harus diisi.";
        // Arahkan kembali ke halaman index.php (tingkat satu folder di atas)
        header("location: ../index.php"); 
        exit;
    }
    
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    $role     = trim($_POST["role"]); // 'admin' atau 'petugas'

    // Validasi peran
    if ($role != 'admin' && $role != 'petugas') {
        $_SESSION['login_error'] = "Peran tidak valid.";
        header("location: ../home.php");
        exit;
    }

    // ===============================================
    // 2. PREPARE DAN EXECUTE QUERY
    // Gunakan $pdo dari koneksi.php
    // ===============================================
    $sql = "SELECT id_users, email, password, role, nama_lengkap FROM users WHERE email = :email AND role = :role";
    
    if($stmt = $pdo->prepare($sql)){
        
        // Bind parameter
        $stmt->bindParam(":email", $param_email, PDO::PARAM_STR);
        $stmt->bindParam(":role", $param_role, PDO::PARAM_STR);
        
        // Set parameter
        $param_email = $email;
        $param_role = $role;
        
        if($stmt->execute()){
            
            if($stmt->rowCount() == 1){
                if($row = $stmt->fetch()){
                    $hashed_password = $row['password'];
                    
                    // ===============================================
                    // 3. VERIFIKASI PASSWORD
                    // ===============================================
                    if(password_verify($password, $hashed_password)){
                        // Password benar, buat sesi baru
                        
                        $_SESSION["loggedin"]    = true;
                        $_SESSION["id_users"]    = $row['id_users'];
                        $_SESSION["email"]    = $row['email'];
                        $_SESSION["nama_lengkap"] = $row['nama_lengkap'];
                        $_SESSION["role"]        = $row['role']; 
                        
                        // ===============================================
                        // 4. REDIRECT BERDASARKAN PERAN
                        // ===============================================
                        if ($role == 'admin') {
                            // Dari proses/ ke admin/index.php
                            header("location: ../admin/index2.php"); 
                        } else { // petugas
                            // Dari proses/ ke petugas/index.php
                            header("location: ../petugas/home.php");
                        }
                        exit;
                        
                    } else{
                        $_SESSION['login_error'] = "Password yang Anda masukkan salah.";
                    }
                }
            } else{
                $_SESSION['login_error'] = "Email atau peran tersebut tidak terdaftar.";
            }
            
        } else{
            $_SESSION['login_error'] = "Terjadi kesalahan pada database (Query Gagal).";
        }

        // Tutup statement
        unset($stmt);
    }
    
    // Redirect kembali ke halaman index.php jika verifikasi gagal
    header("location: ../home.php");
    exit;
    
} else {
    // Jika diakses tanpa metode POST, redirect ke index
    header("location: ../home.php");
    exit;
}
?>