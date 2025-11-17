<?php
// ===============================================
// HALAMAN UTAMA / INDEX - FORMULIR SURVEY PELANGGAN (REFACTOR TAMPILAN)
// ===============================================

// Path koneksi (Asumsi: koneksi.php ada di folder config/ dari root)
require_once 'config/koneksi.php';

$data_kendaraan = null;
$error_message = null;
$success_message = null;

// Cek status setelah submit form
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'sukses') {
        // Tambahkan refresh otomatis setelah 5 detik agar antrian survey berikutnya muncul
        header("Refresh: 5; url=index.php"); 
        $success_message = 'Terima kasih banyak atas waktu dan penilaian yang Anda berikan. Masukan Anda sangat berarti bagi kami!';
    } else if ($_GET['status'] === 'error' && isset($_GET['pesan'])) {
        $error_message = 'Gagal menyimpan survei: ' . htmlspecialchars(urldecode($_GET['pesan']));
    }
}

try {
    // REVISI QUERY: Mengambil nama petugas (p.nama_petugas) dengan JOIN
    $sql_kendaraan = "
        SELECT 
            k.id_kendaraan, k.plat_nomor, k.nomor_pendaftaran, k.nama_pemilik_kendaraan,
            p.nama_petugas
        FROM 
            kendaraan k
        LEFT JOIN
            petugas p ON k.id_petugas = p.id_petugas
        WHERE 
            k.status_survey = 0
        ORDER BY 
            k.tanggal_pendaftaran DESC, k.id_kendaraan DESC
        LIMIT 1";

    // Asumsi $pdo sudah terdefinisi di koneksi.php
    $stmt = $pdo->prepare($sql_kendaraan);
    $stmt->execute();
    $data_kendaraan = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = 'Kesalahan Database saat memuat data: ' . $e->getMessage();
}

/**
 * Fungsi untuk membuat input rating bintang yang lebih estetik.
 * Menggunakan konsep "rating star" (bintang yang diisi/aktif)
 */
function createRatingInput($name, $label) {
    $html = '<div class="mb-4 survey-question">';
    $html .= '<label class="form-label fw-bold h6 text-dark">' . $label . ' <span class="text-danger">*</span></label>';
    $html .= '<div class="rating-input-group d-flex flex-row-reverse justify-content-start align-items-center mt-2">';
    for ($i = 5; $i >= 1; $i--) {
        // Input Radio disembunyikan, menggunakan label dengan ikon
        $html .= '<input type="radio" id="' . $name . $i . '" name="' . $name . '" value="' . $i . '" required>';
        $html .= '<label for="' . $name . $i . '" title="Beri rating ' . $i . '">';
        $html .= '<i class="bi bi-star-fill star-icon"></i>';
        $html .= '</label>';
    }
    $html .= '</div>';
    $html .= '</div>';
    return $html;
}

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survey Kepuasan Pelanggan | UPT PKB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
    :root {
        /* Skema Warna Baru */
        --primary-color: #00796B;
        /* Teal/Hijau Tosca */
        --secondary-color: #26A69A;
        /* Light Teal */
        --warning-color: #FFC107;
        /* Kuning (untuk bintang) */
        --background-color: #E0F2F1;
        /* Sangat Light Teal */
    }

    body {
        background-color: var(--background-color);
        font-family: 'Arial', sans-serif;
    }

    .survey-container {
        max-width: 700px;
        margin: 50px auto;
    }

    /* Card Header dengan warna Primary */
    .card-header-custom {
        background-color: var(--primary-color);
        color: white;
        border-bottom: none;
        border-radius: 10px 10px 0 0 !important;
    }

    .card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    /* Styling untuk Rating Bintang */
    .rating-input-group>input {
        display: none;
        /* Sembunyikan radio button asli */
    }

    .rating-input-group>label {
        float: right;
        cursor: pointer;
        padding: 0 3px;
        font-size: 2.5rem;
        /* Ukuran Bintang */
        color: #ddd;
        /* Warna Bintang default (Abu-abu Muda) */
        transition: color 0.2s;
    }

    .rating-input-group>input:checked~label {
        color: var(--warning-color);
        /* Bintang yang sudah dipilih dan sebelumnya */
    }

    .rating-input-group:not(:checked)>label:hover,
    .rating-input-group:not(:checked)>label:hover~label {
        color: #ffda6a;
        /* Warna hover */
    }

    /* Info Kendaraan yang menonjol */
    .list-group-item-custom {
        background-color: var(--secondary-color);
        color: white;
        border: none;
        font-size: 1.1rem;
    }

    .list-group-item-custom .fw-bold {
        color: #FFEB3B;
        /* Kuning Terang untuk Plat Nomor */
        font-size: 1.2rem;
    }

    .petugas-info {
        background-color: #004D40 !important;
        /* Darker Teal */
        color: white !important;
    }

    .btn-submit-custom {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
        transition: background-color 0.3s;
    }

    .btn-submit-custom:hover {
        background-color: #004D40;
        border-color: #004D40;
    }
    </style>
</head>

<body>

    <div class="survey-container">
        <div class="card shadow-lg">
            <div class="card-header card-header-custom text-center py-4">
                <h3 class="mb-1"><i class="bi bi-person-heart me-2"></i> Survey Kepuasan Pelanggan</h3>
                <p class="lead mb-0">UPT Pengujian Kendaraan Bermotor</p>
            </div>
            <div class="card-body p-4 p-md-5">

                <?php if ($success_message): ?>
                <div class="alert alert-success text-center border-0 rounded-3 p-4">
                    <h4 class="alert-heading"><i class="bi bi-hand-thumbs-up-fill me-2"></i> Survey Berhasil!
                    </h4>
                    <p class="mb-3"><?php echo $success_message; ?></p>
                    <hr class="my-2">
                    <small class="text-muted">Halaman akan memuat data antrian berikutnya dalam 5 detik...</small>
                </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-x-octagon-fill me-2"></i> **Terjadi Kesalahan!** <?php echo $error_message; ?>
                </div>
                <?php endif; ?>

                <?php if ($data_kendaraan): ?>

                <div class="alert alert-info text-center rounded-3 p-3">
                    <i class="bi bi-clipboard-check-fill me-2"></i> Mohon Berikan Penilaian Anda untuk layanan
                    kendaraan berikut:
                </div>

                <h5 class="text-primary mt-4 mb-3"><i class="bi bi-car-front-fill me-2"></i> Data Kendaraan & Petugas
                    Uji</h5>
                <ul class="list-group mb-4 shadow-sm">
                    <li
                        class="list-group-item list-group-item-custom d-flex justify-content-between align-items-center">
                        Plat Nomor <span
                            class="fw-bold"><?php echo htmlspecialchars($data_kendaraan['plat_nomor']); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Nomor Pendaftaran
                        <span
                            class="text-muted"><?php echo htmlspecialchars($data_kendaraan['nomor_pendaftaran'] ?? '-'); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Nama Pemilik
                        <span
                            class="text-muted"><?php echo htmlspecialchars($data_kendaraan['nama_pemilik_kendaraan'] ?? '-'); ?></span>
                    </li>
                    <li class="list-group-item petugas-info d-flex justify-content-between align-items-center">
                        Petugas Penguji <span
                            class="fw-bold"><?php echo htmlspecialchars($data_kendaraan['nama_petugas'] ?? 'Petugas Tidak Ditemukan'); ?></span>
                    </li>
                </ul>

                <hr class="mb-5">
                <h4 class="text-primary mb-4"><i class="bi bi-star-half me-2"></i> Penilaian Layanan Anda</h4>
                <p class="text-muted">Pilih 1 (Sangat Buruk) hingga 5 (Sangat Baik).</p>

                <form action="proses/proses-survey.php" method="POST">
                    <input type="hidden" name="id_kendaraan" value="<?php echo $data_kendaraan['id_kendaraan']; ?>">

                    <?php echo createRatingInput('rating_pelayanan', '1. Bagaimana penilaian Anda terhadap Pelayanan Petugas?'); ?>

                    <?php echo createRatingInput('rating_fasilitas', '2. Bagaimana penilaian Anda terhadap Fasilitas & Kebersihan Lingkungan?'); ?>

                    <?php echo createRatingInput('rating_kecepatan', '3. Bagaimana penilaian Anda terhadap Kecepatan Proses Pengujian?'); ?>

                    <div class="mb-4">
                        <label for="komentar" class="form-label fw-bold h6 text-dark">4. Komentar dan Saran
                            (Opsional)</label>
                        <textarea class="form-control" id="komentar" name="komentar" rows="4"
                            placeholder="Tulis masukan, kritik, atau saran Anda di sini untuk membantu kami meningkatkan layanan."></textarea>
                    </div>

                    <div class="d-grid mt-5">
                        <button type="submit" class="btn btn-submit-custom btn-lg shadow">
                            <i class="bi bi-send-fill me-2"></i> Kirim Penilaian
                        </button>
                    </div>
                </form>

                <?php else: ?>
                <div class="alert alert-warning text-center p-5 rounded-3">
                    <i class="bi bi-hourglass-split display-1 text-warning"></i>
                    <h4 class="mt-3">Antrian Survey Kosong</h4>
                    <p class="mb-0">Belum ada data layanan kendaraan yang menunggu untuk diisi surveinya.
                        Silakan tunggu atau muat ulang halaman secara berkala.</p>
                </div>
                <?php endif; ?>

            </div>
            <div class="card-footer text-muted text-center py-3" style="background-color: #F5F5F5;">
                <small>&copy; <?php echo date('Y'); ?> UPT Pengujian Kendaraan Bermotor. Dikelola oleh Tim IT.</small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>