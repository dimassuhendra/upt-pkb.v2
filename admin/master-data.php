<?php
// ===============================================
// HALAMAN MASTER DATA - UPT PKB (LENGKAP DENGAN STRUKTUR CSS)
// ===============================================

// Pastikan koneksi database sudah tersedia
require_once '../config/koneksi.php'; 

// Asumsi: Di sini adalah tempat Anda meletakkan logika otentikasi sesi Anda (session_start(), dll.)

// Tentukan tab yang aktif berdasarkan parameter URL
$tab_aktif = isset($_GET['tab']) ? $_GET['tab'] : 'petugas'; 

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Master Data | UPT PKB</title>

</head>

<body class="hold-transition sidebar-mini">
    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Master Data</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="admin-index.php">Home</a></li>
                            <li class="breadcrumb-item active">Master Data</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="card">
                <div class="card-header p-2">
                    <ul class="nav nav-pills">
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($tab_aktif == 'petugas' ? 'active' : ''); ?>"
                                href="master_data.php?tab=petugas">
                                Data Petugas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($tab_aktif == 'users' ? 'active' : ''); ?>"
                                href="master_data.php?tab=users">
                                Data Users (Login)
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        <?php 
                        // Tampilkan konten berdasarkan tab yang aktif
                        if ($tab_aktif == 'petugas') {
                            // Memuat konten dari master_petugas.php
                            include 'master_petugas.php';
                        } elseif ($tab_aktif == 'users') {
                            // Memuat konten dari master_users.php
                            include 'master_users.php';
                        } else {
                            include 'master_petugas.php'; // Default
                        }
                        ?>
                    </div>
                </div>
            </div>
        </section>
    </div>
</body>

</html>