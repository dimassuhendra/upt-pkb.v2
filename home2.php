<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemerintah Kota Bandar Lampung - Dinas Perhubungan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Montserrat:ital,wght@0,100..900;1,100..900&family=Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Styling Dasar */
body {
    font-family: "Libre Baskerville", serif;
    margin: 0;
    padding: 0;
    /* Warna latar belakang body diambil dari gambar */
    background-color: #ECE9D8; 
    display: flex;
    flex-direction: column;
    align-items: center;
    min-height: 100vh;
}

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
    border-bottom: 5px solid #ffcc00; /* Garis kuning di bawah header */
}

/* Logo */
.logo-left img {
    width: 100px; /* Sesuaikan ukuran logo */
    height: auto;
    padding: 10px 0;
    display: block;
}

 .logo-right img {
    width: 100px; /* Sesuaikan ukuran logo */
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
    font-size: 31px; /* Ukuran lebih besar dari H1 */
    font-weight: 800; /* Paling Tebal */
    text-transform: uppercase;
    letter-spacing: 3.1px;
    padding-bottom: 2px;
    display: inline-block; /* Agar garis bawah hanya sepanjang tulisan */
}

.title-block .address {
    font-size: 11px;
    font-weight: normal;
    font-family: 'Times New Roman', Times, serif, sans-serif;
}

/* Info Bar (Baris Waktu, Kontak, dll) */
.info-bar {
    width: 100%;
    font-family: 'Times New Roman', Times, serif, sans-serif;
    font-size: 12px;
    padding: 5px 0;
    box-sizing: border-box;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #aaaaaa;
    color: #333;
}

.info-bar .version {
    color: aqua;
}
.info-bar .contact {
    font-weight: bold;
    color: #ffcc00;
}

/* Penampung Login di Tengah */
.login-container {
    padding-top: 70px; /* Jarak dari atas */
    display: flex;
    justify-content: center;
    align-items: center;
    width: 100%;
}

/* Form Login */
.login-form {
    width: 250px; /* Ukuran form login */
    padding: 15px;
    background-color: #d1e2f7; /* Warna latar belakang form (biru muda) */
    border: 1px solid #336699;
    box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.3);
    text-align: center;
    box-sizing: border-box;
}

/* Header Form Login (User Log-In) */
.login-header {
    background-color: #ff6600; /* Warna oranye */
    color: white;
    font-weight: bold;
    padding: 5px;
    font-size: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px 10px 0 0;
}

.login-header .user-icon {
    width: 24px; /* Ukuran icon */
    height: auto;
    margin-right: 5px;
}

/* Grup Input (Label + Input) */
.input-group {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
    font-size: 12px;
}

.input-group label {
    width: 80px; /* Lebar label agar sejajar */
    text-align: right;
    padding-right: 10px;
}

.input-group input[type="text"],
.input-group input[type="password"] {
    flex-grow: 1;
    padding: 3px;
    border: 1px solid #999;
}

.login-button {
    background-color: #007bff; /* Warna biru */
    color: white;
    border: 1px solid #0056b3;
    padding: 5px 20px;
    cursor: pointer;
    font-weight: bold;
    margin-top: 10px;
    display: block; /* Agar tombol mengambil lebar penuh, jika diperlukan */
    margin-left: auto;
    margin-right: auto;
}

.login-button:hover {
    background-color: #0056b3;
}

/* NEw Style */
.body-login {
    margin: 0;
    font-family: 'Poppins', sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    background: linear-gradient(135deg, #e0c3fc 0%, #8ec5fc 100%); /* Gradien Ungu-Biru Pucat */
    background-size: cover;
    color: #424242;
}

.login-container {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 100%;
    height: 100%;
}

.login-box {
    background: #d1e2f7;
    border-radius: 15px;
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
    text-align: center;
    width: 350px;
    max-width: 90%;
}

.login-box h2 {
    margin-bottom: 30px;
    color: #333;
    font-weight: 600;
    font-size: 28px;
}
.login-box form {
    margin: 20px 40px;
}

.input-group {
    margin-bottom: 25px;
    text-align: left;
    gap: 10px;
}

.input-group i {
    /* left: 15px;
    top: 50%;
    transform: translateY(-50%); */
    color: #9E9E9E;
    font-size: 18px;
}

.input-group input {
    width: calc(100% - 70px); /* Kurangi padding dan icon */
    padding: 12px 20px 12px 50px;
    border: none;
    border-bottom: 2px solid #E0E0E0;
    background: transparent;
    font-size: 16px;
    color: #424242;
    transition: border-bottom-color 0.3s ease;
}

.input-group input:focus {
    border-bottom-color: #008080; /* Teal gelap untuk fokus */
    outline: none;
}

.input-group input::placeholder {
    color: #BDBDBD;
}

.login-button {
    width: 70%;
    padding: 10px;
    background: #2492c2; /* Teal gelap */
    color: white;
    border: none;
    border-radius: 99px;
    font-size: 15px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.3s ease, transform 0.2s ease;
    margin-top: 20px;
}

.login-button:hover {
    background: #006A6A;
    transform: translateY(-2px);
}

.links {
    margin-top: 25px;
    font-size: 10px;
    display: flex;
    justify-content: space-between;
}

.links a {
    color: #008080; /* Teal gelap */
    text-decoration: none;
    margin: 0 10px;
    transition: color 0.3s ease;
}

.links a:hover {
    text-decoration: underline;
    color: #006A6A;
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
            <h2 class="mt-1">DINAS  PERHUBUNGAN</h2>
            <p class="address mt-1">JL. BASUKI RAHMAT NO. 34, SUMUR PUTRI, TLK BETUNG UTARA, KOTA BANDAR LAMPUNG, LAMPUNG 35211</p>
        </div>

        <div class="logo-right pe-1 mb-2">
            <img src="assets/img/dishub.png" alt="Logo Kanan Dishub">
        </div>
    </div>
    
    <div class="info-bar bg-black">
        <marquee>
            <span class="version">SIMPKB 6.9 - 24062020 | <?php date_default_timezone_set('Asia/Jakarta'); setlocale(LC_TIME, 'id_ID', 'id_ID.utf8', 'id_ID.UTF-8', 'ind'); $tanggal_indonesia = strftime('%A , %e %B %Y | %H : %M WIB'); echo $tanggal_indonesia;?></span>
            
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

            <span class="contact">Jakartaa cp Edy Susanto : 08122797823</span>
        </marquee>
    </div>

    <div class="login-container">
        <div class="login-box">
            <h2 class="login-header text-white">Form Login</h2>
            <form action="proses/proses-login.php" method="POST" id="mainLoginForm">                    <input type="hidden" name="role" id="inputRole" value="admin">

                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" placeholder="Email" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" placeholder="Password" required>
                </div>
                <button type="submit" class="login-button">LOG IN</button>
                <div class="links">
                    <a href="#" class="forgot-password">Forgot Password?</a>
                    <a href="#" class="sign-up">Sign Up Now</a>
                </div>
            </form>
        </div>
    </div>

    <!-- <div class="login-container">
        <form action="proses/proses-login.php" method="POST" id="mainLoginForm" class="login-form">
            <div class="login-header">
                <img src="assets/img/8.png" alt="User Login Icon" class="user-icon">
                User Log-In
            </div>

            <input type="hidden" name="role" id="inputRole" value="admin">

            <div class="input-group">
                <label for="user-id">Email</label>
                <input type="text" id="email" name="email">
            </div>
            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password">
            </div>
            <button type="submit" class="login-button" id="submitButton">Login</button>
        </form>
    </div> -->

      <script>
        document.addEventListener('DOMContentLoaded', function() {
        const flipper = document.getElementById('loginCard');
        const flipButtons = document.querySelectorAll('.login-btn-custom');

        // Elemen di sisi belakang (Copywrite & Form)
        const copywritePanel = document.getElementById('copywritePanel');
        const copywriteTitle = document.getElementById('copywriteTitle');
        const copywriteDesc = document.getElementById('copywriteDesc');
        const formTitle = document.getElementById('formTitle');
        const formDescription = document.getElementById('formDescription');
        const inputRole = document.getElementById('inputRole');
        const submitButton = document.getElementById('submitButton');
        const unflipButtons = document.querySelectorAll('#unflipButtonCopywrite, #unflipButtonForm');

        // Data konfigurasi
        const rolesConfig = {
            'admin': {
                bgClass: 'admin-bg',
                copyTitle: 'Selamat Datang Admin!',
                copyDesc: 'Masuk ke panel Admin untuk mengelola data kendaraan, petugas, dan melihat dashboard evaluasi layanan secara lengkap.',
                formTitle: 'LOGIN ADMIN SISTEM',
                formDesc: 'Gunakan kredensial Admin Anda.',
                submitColor: '#28a745'
            },
            'petugas': {
                bgClass: 'petugas-bg',
                copyTitle: 'Halo, Petugas Lapangan!',
                copyDesc: 'Masuk untuk menginput data kendaraan yang baru diuji dan menautkannya dengan hasil survei dari pengguna.',
                formTitle: 'LOGIN PETUGAS UJI',
                formDesc: 'Gunakan Email dan Password Petugas.',
                submitColor: '#ffc107'
            }
        };

        // Menangani klik tombol di sisi depan (Pilihan Peran)
        flipButtons.forEach(button => {
            button.addEventListener('click', function() {
                const role = this.getAttribute('data-role');
                const config = rolesConfig[role];

                // 1. SET WARNA & TEKS PANEL KIRI
                copywritePanel.classList.remove('admin-bg', 'petugas-bg');
                copywritePanel.classList.add(config.bgClass);

                // Set teks copywrite
                copywriteTitle.textContent = config.copyTitle;
                copywriteDesc.textContent = config.copyDesc;

                // 2. SET WARNA & TEKS FORM PANEL KANAN
                formTitle.textContent = config.formTitle;
                formDescription.textContent = config.formDesc;
                inputRole.value = role;

                // Set warna tombol submit
                submitButton.style.backgroundColor = config.submitColor;
                submitButton.style.color = (role === 'petugas') ? '#333' : 'white';

                // 3. LAKUKAN EFEK BALIK
                flipper.classList.add('is-flipped');
            });
        });

        // Menangani klik tombol kembali di sisi belakang
        unflipButtons.forEach(button => {
            button.addEventListener('click', function() {
                flipper.classList.remove('is-flipped');
            });
        });
    });
    </script>

</body>
</html>