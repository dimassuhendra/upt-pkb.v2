<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Login - UPT PKB Bandar Lampung</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Ubuntu:wght@500;700&display=swap"
        rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
    /* ========================================= */
    /* Gaya Umum & Background Gradasi */
    /* ========================================= */
    body {
        font-family: 'Open Sans', sans-serif;

        /* Background Gradasi Biru-Hijau */
        background: linear-gradient(135deg, #007bff 0%, #00c6ff 50%, #7cfc00 100%);

        display: flex;
        flex-direction: column;
        /* Penting untuk mendorong footer ke bawah */
        justify-content: center;
        align-items: center;
        min-height: 100vh;
    }

    .main-title {
        font-family: 'Ubuntu', sans-serif;
        font-weight: 700;
        letter-spacing: 1px;
    }

    /* Mengatur agar container berada di tengah dan footer di bawah */
    .page-content {
        flex-grow: 1;
        /* Memastikan konten mengisi ruang yang tersedia */
        display: flex;
        justify-content: center;
        align-items: center;
        width: 100%;
    }

    /* ========================================= */
    /* Gaya Split Card & Flip */
    /* ========================================= */
    .card-flip-container {
        width: 700px;
        height: 450px;
        perspective: 1000px;
        margin: 20px 0;
        /* Margin atas/bawah agar tidak terlalu mepet */
    }

    .card-flipper {
        position: relative;
        width: 100%;
        height: 100%;
        transition: transform 0.8s;
        transform-style: preserve-3d;
    }

    .is-flipped {
        transform: rotateY(180deg);
    }

    .card-face {
        position: absolute;
        width: 100%;
        height: 100%;
        backface-visibility: hidden;
        border-radius: 15px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        /* Sedikit lebih gelap karena background gradasi */
        overflow: hidden;
        display: flex;
    }

    /* ----- Sisi Kiri (Copywrite/Dynamic Color) ----- */
    .card-left-panel {
        width: 40%;
        padding: 30px;
        color: white;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
    }

    /* Warna default untuk panel kiri (Pilihan Peran) */
    .default-bg {
        background-color: #007bff;
    }

    .admin-bg {
        background-color: #28a745;
    }

    .petugas-bg {
        background-color: #ffc107;
        color: #333 !important;
    }

    .petugas-bg h2,
    .petugas-bg p {
        color: #333 !important;
    }

    /* ----- Sisi Kanan (Form/White Panel) ----- */
    .card-right-panel {
        width: 60%;
        background-color: white;
        padding: 40px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        text-align: center;
    }

    /* Sisi Belakang (Form Login) - Diputar 180deg */
    .card-back-login {
        transform: rotateY(180deg);
    }

    /* Mengatur tata letak form di sisi kanan */
    #mainLoginForm {
        padding: 0 20px;
    }

    /* ========================================= */
    /* Gaya Footer */
    /* ========================================= */
    .app-footer {
        margin-top: auto;
        /* Mendorong footer ke bawah */
        padding: 15px 0;
        color: rgba(255, 255, 255, 0.7);
        /* Putih transparan agar terlihat di gradasi */
        text-align: center;
        width: 100%;
    }
    </style>
</head>

<body>

    <div class="page-content">
        <div class="card-flip-container">
            <div class="card-flipper" id="loginCard">

                <div class="card-face card-front-role-select">
                    <div class="card-left-panel default-bg">
                        <h2 class="main-title mb-4">SELAMAT DATANG!</h2>
                        <p>Silakan pilih jenis akun Anda (Admin atau Petugas Lapangan) untuk masuk ke sistem Evaluasi
                            Layanan UPT PKB.</p>
                    </div>

                    <div class="card-right-panel">
                        <h2 class="main-title text-primary mb-4">MASUK SISTEM</h2>
                        <p class="lead mb-4">Pilih jenis akun Anda:</p>

                        <button type="button" data-role="admin" class="btn btn-lg login-btn-custom w-100 mb-3"
                            style="background-color: #28a745; color: white;">
                            <i class="bi bi-person-fill-gear"></i> Login Admin
                        </button>

                        <!-- <button type="button" data-role="petugas" class="btn btn-lg login-btn-custom w-100"
                            style="background-color: #ffc107; color: #333;">
                            <i class="bi bi-person-fill"></i> Login Petugas
                        </button> -->
                    </div>
                </div>

                <div class="card-face card-back-login" id="loginFormContainer">

                    <div class="card-left-panel" id="copywritePanel">
                        <h2 class="main-title mb-3" id="copywriteTitle"></h2>
                        <p id="copywriteDesc"></p>

                        <button type="button" id="unflipButtonCopywrite" class="btn btn-outline-light btn-lg mt-3">
                            <i class="bi bi-arrow-left-circle"></i> Ganti Peran
                        </button>
                    </div>

                    <div class="card-right-panel">
                        <h2 class="main-title text-primary mb-4" id="formTitle"></h2>
                        <p class="mb-4 text-muted" id="formDescription"></p>

                        <form action="proses/proses-login.php" method="POST" id="mainLoginForm">
                            <input type="hidden" name="role" id="inputRole" value="">

                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="email" name="email" placeholder="Email"
                                    required>
                                <label for="email">Email</label>
                            </div>

                            <div class="form-floating mb-4">
                                <input type="password" class="form-control" id="password" name="password"
                                    placeholder="Password" required>
                                <label for="password">Password</label>
                            </div>

                            <button type="submit" class="btn btn-lg w-100 fw-bold text-white" id="submitButton"
                                style="background-color: #28a745;">
                                <i class="bi bi-box-arrow-in-right"></i> MASUK
                            </button>

                            <button type="button" id="unflipButtonForm" class="btn btn-sm btn-outline-secondary mt-3">
                                <i class="bi bi-x-circle"></i> Batal
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <footer class="app-footer">
        <p class="mb-0"><small>UPT Pengujian Kendaraan Bermotor (PKB) | Dinas Perhubungan Kota Bandar Lampung</small>
        </p>
        <p class="mb-0"><small>&copy; Copyright <?php echo date("Y"); ?></small></p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
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