<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DASHBOARD ADMIN | Dinas Perhubungan Kota Bandar Lampung</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        /* --- STYLING UMUM (DARI HALAMAN LOGIN) --- */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #ECE9D8; 
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            /* background-color: # */
        }

        /* Penampung Header */
        /* Penampung Header */
        .header-container {
            width: 100%;
            /* Menggunakan background gradien biru tua seperti pada gambar */
            background: linear-gradient(to right, #ffecd2, #250142 30%, #250142 70%, #ffecd2);
            color: white;
            padding-top: 10px;
            padding-left: 20px;
            padding-right: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 5px solid #ffcc00;
            /* Garis kuning di bawah header */
        }

        /* Logo */
        .logo-left img {
            width: 100px;
            /* Sesuaikan ukuran logo */
            height: auto;
            padding: 10px 0;
            display: block;
        }

        .logo-right img {
            width: 100px;
            /* Sesuaikan ukuran logo */
            height: auto;
            padding: 10px 0;
            display: block;
        }

        .title-block {
            display: flex;
            flex-direction: column;
            align-items: space-between;
            text-align: center;
            height: 100%;
        }

        .title-block h1 {
            font-size: 20px;
            font-weight: 520;
            text-transform: uppercase;
        }

        .title-block h2 {
            font-size: 31px;
            /* Ukuran lebih besar dari H1 */
            font-weight: 800;
            /* Paling Tebal */
            text-transform: uppercase;
            letter-spacing: 3.1px;
            padding-bottom: 2px;
            display: inline-block;
            /* Agar garis bawah hanya sepanjang tulisan */
        }

        .title-block .address {
            font-size: 11px;
            font-weight: normal;
            font-family: 'Times New Roman', Times, serif, sans-serif;
        }

        /* Info Bar (Baris Waktu, Kontak, dll) */
        .info-bar {
            width: 100%;
            background-color: #dddddd;
            font-size: 12px;
            padding: 5px 20px;
            box-sizing: border-box;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #aaaaaa;
            color: #333;
        }
        
        .info-bar span:first-child {
            margin-right: 20px;
        }

        /* --- STYLING KHUSUS DASHBOARD --- */
        
        /* Navigasi Menu Utama */
        .nav-menu {
            width: 100%;
            background-color: #f1f1f1; /* Latar belakang menu */
            border-bottom: 2px solid #ccc;
            font-size: 14px;
            padding: 0 20px;
            box-sizing: border-box;
        }

        .nav-menu ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
        }

        .nav-menu ul li {
            position: relative;
        }

        .nav-menu ul li a {
            display: block;
            padding: 8px 15px;
            text-decoration: none;
            color: #333;
            font-weight: bold;
            border-right: 1px solid #ccc;
        }
        
        .nav-menu ul li:first-child a {
            border-left: 1px solid #ccc;
        }

        .nav-menu ul li a:hover {
            background-color: #e0e0e0;
        }
        
        /* Status Bar Selamat Datang */
        .welcome-bar {
            width: 100%;
            background-color: #1a75ff; /* Biru terang */
            color: white;
            padding: 5px 20px;
            font-size: 12px;
            font-weight: bold;
            box-sizing: border-box;
        }
        
        /* Konten Utama Dashboard */
        .dashboard-content {
            width: 100%;
            padding: 20px;
            box-sizing: border-box;
            display: flex;
            flex-wrap: wrap; /* Agar card bisa turun ke baris baru */
            gap: 20px; /* Jarak antar card */
            justify-content: flex-start;
        }

        /* Styling Card Konten */
        .content-card {
            background-color: white;
            border: 1px solid #ccc;
            width: calc(33.333% - 14px); /* Tiga kolom dengan sedikit ruang */
            box-sizing: border-box;
            padding: 10px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 250px; /* Ketinggian minimal */
        }
        
        .card-title {
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        
        .card-description {
            font-size: 12px;
            margin-bottom: 15px;
            color: #666;
            flex-grow: 1; /* Agar deskripsi mengambil ruang vertikal yang tersedia */
        }
        
        .card-image-large {
            width: 100%;
            max-width: 120px; /* Ukuran gambar utama */
            height: auto;
            margin-bottom: 10px;
        }
        
        .card-image-small {
            width: 100%;
            max-width: 80px; /* Ukuran gambar kecil (truk, mobil) */
            height: auto;
            margin-bottom: 10px;
        }
        
        /* Gaya khusus untuk link/tombol aksi */
        .card-action {
            display: block;
            padding: 5px 10px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            font-size: 12px;
            font-weight: bold;
            border-radius: 3px;
            margin-top: auto; /* Memposisikan di paling bawah card */
        }
        
        .card-action:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    
    <div class="header-container">
        <div class="logo-left ps-1 mb-2">
            <img src="assets/img/bandar-lampung.png" alt="Logo Kota Bandar Lampung">
        </div>

        <div class="title-block mt-1">
            <h1>PEMERINTAH KOTA BANDAR LAMPUNG</h1>
            <h2 class="mt-1">DINAS PERHUBUNGAN</h2>
            <p class="address mt-1">JL. BASUKI RAHMAT NO. 34, SUMUR PUTRI, TLK BETUNG UTARA, KOTA BANDAR LAMPUNG,
                LAMPUNG 35211</p>
        </div>

        <div class="logo-right pe-1 mb-2">
            <img src="assets/img/dishub.png" alt="Logo Kanan Dishub">
        </div>
    </div>
    
    <div class="info-bar">
        <span>SIMPKB 6.9 - 24062020 | <?php echo date('l, d-m-Y');?></span>
        <span class="welcome-message">Selamat datang di Sistem Informasi Uji Kendaraan Bermotor Dinas Perhubungan Kota Bandar Lampung</span>
    </div>
    
    <div class="nav-menu">
        <ul>
            <li><a href="#">Data Master</a></li>
            <li><a href="#">Pendaftaran</a></li>
            <li><a href="#">Hasil Uji</a></li>
            <li><a href="#">Laporan</a></li>
            <li><a href="#">Utility</a></li>
            <li><a href="../logout.php">Log-Out</a></li>
        </ul>
    </div>
    
    <div class="dashboard-content">
        
        <div class="content-card">
            <img src="../assets/img/1.png" alt="Icon Pemilik" class="card-image-large">
            <p class="card-title">Update Data Pemilik Kendaraan</p>
            <p class="card-description">Update Data Pemilik Kendaraan. Form ini digunakan untuk memasukkan data pemilik kendaraan baik KBWU maupun bukan KBWU</p>
            <a href="#" class="card-action">Update</a>
        </div>
        
        <div class="content-card">
            <img src="../assets/img/5.png" alt="Icon Pendaftaran" class="card-image-large">
            <p class="card-title">Pendaftaran Uji Kendaraan</p>
            <p class="card-description">Pendaftaran Uji Kendaraan. Form ini digunakan untuk memasukkan data pendaftaran uji, baik uji pertama, uji berkala, maupun uji menumpang</p>
            <a href="#" class="card-action">Registrasi</a>
        </div>
        
        <div class="content-card">
            <img src="../assets/img/4.png" alt="Icon Verifikasi" class="card-image-large">
            <p class="card-title">Verifikasi Hasil Uji</p>
            <p class="card-description">Verifikasi Hasil Uji Kendaraan. Form ini digunakan untuk memverifikasi data hasil uji yang telah diinput dan bagian pengumuman, sekaligus dilakukan verifikasi keaslian hasil uji dan pengiriman data hasil uji ke aplikasi E-KIR Uji Elektronik (EBLUE)</p>
            <a href="#" class="card-action">Verifikasi</a>
        </div>
        
        <div class="content-card">
            <img src="../assets/img/2.png" alt="Icon Truk" class="card-image-large">
            <p class="card-title">Update Data Kendaraan</p>
            <p class="card-description">Update Data Kendaraan. Form ini digunakan untuk memasukkan data kendaraan baik KBWU maupun bukan KBWU</p>
            <a href="#" class="card-action">Update</a>
        </div>
        
        <div class="content-card">
            <img src="../assets/img/2.png" alt="Icon Input Uji" class="card-image-large">
            <p class="card-title">Input Hasil Uji</p>
            <p class="card-description">Form ini digunakan untuk memasukkan data hasil pemeriksaan dan penilaian kelaikan kendaraan.</p>
            <a href="#" class="card-action">Input</a>
        </div>
        
        <div class="content-card">
            <img src="../assets/img/3.png" alt="Icon Alat Uji" class="card-image-large">
            <p class="card-title">Input Hasil Uji Kendaraan</p>
            <p class="card-description">Input Hasil Uji Kendaraan. Beralih ke aplikasi input hasil uji kendaraan</p>
            <a href="#" class="card-action">Aplikasi Input</a>
        </div>
        
    </div>

</body>
</html>