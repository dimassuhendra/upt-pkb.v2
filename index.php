<?php
// ===============================================
// HALAMAN UTAMA / INDEX - FORMULIR SURVEY PELANGGAN (REVISI: Menampilkan Petugas)
// File ini ditempatkan di folder root
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
        $success_message = 'Terima kasih atas waktu dan penilaian yang Anda berikan. Masukan Anda sangat berarti bagi kami!';
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

    $stmt = $pdo->prepare($sql_kendaraan);
    $stmt->execute();
    $data_kendaraan = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = 'Kesalahan Database saat memuat data: ' . $e->getMessage();
}

// Fungsi untuk membuat input rating bintang
function createRatingInput($name, $label) {
    $html = '<div class="mb-3">';
    $html .= '<label class="form-label fw-bold">' . $label . ' <span class="text-danger">*</span></label>';
    $html .= '<div class="rating-input d-flex gap-3">';
    for ($i = 5; $i >= 1; $i--) {
        $html .= '<div class="form-check form-check-inline">';
        $html .= '<input class="form-check-input" type="radio" name="' . $name . '" id="' . $name . $i . '" value="' . $i . '" required>';
        $html .= '<label class="form-check-label" for="' . $name . $i . '">';
        $html .= '<i class="bi bi-star-fill text-warning me-1"></i> ' . $i;
        $html .= '</label>';
        $html .= '</div>';
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
    body {
        background-color: #f8f9fa;
    }

    .survey-container {
        max-width: 650px;
        margin: 50px auto;
    }

    .rating-input .form-check-input {
        opacity: 0;
        position: absolute;
    }

    .rating-input .form-check-label {
        cursor: pointer;
        padding: 5px 10px;
        border: 1px solid #dee2e6;
        border-radius: 5px;
        transition: all 0.2s;
    }

    .rating-input .form-check-input:checked+.form-check-label {
        background-color: #ffc107;
        color: #212529;
        border-color: #ffc107;
        box-shadow: 0 0 5px rgba(255, 193, 7, 0.5);
    }
    </style>
</head>

<body>

    <div class="survey-container">
        <div class="card shadow-lg border-0">
            <div class="card-header bg-primary text-white text-center py-3">
                <h4 class="mb-0"><i class="bi bi-heart-pulse-fill me-2"></i> Survey Kepuasan Pelanggan</h4>
                <p class="mb-0">UPT Pengujian Kendaraan Bermotor</p>
            </div>
            <div class="card-body p-4">

                <?php if ($success_message): ?>
                <div class="alert alert-success text-center">
                    <h5 class="alert-heading"><i class="bi bi-check-circle-fill me-2"></i> Survey Berhasil Disimpan!
                    </h5>
                    <p class="mb-0"><?php echo $success_message; ?></p>
                    <hr>
                    <small class="text-muted">Halaman akan memuat data antrian survey berikutnya dalam 5
                        detik...</small>
                </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-x-octagon-fill me-2"></i> <?php echo $error_message; ?>
                </div>
                <?php endif; ?>

                <?php if ($data_kendaraan): ?>

                <div class="alert alert-info">
                    <i class="bi bi-info-circle-fill me-2"></i> Silakan berikan penilaian Anda untuk layanan kendaraan
                    berikut:
                </div>

                <h5 class="text-primary mb-3">Data Kendaraan & Petugas Uji</h5>
                <ul class="list-group mb-4">
                    <li class="list-group-item d-flex justify-content-between">
                        **Plat Nomor** <span
                            class="fw-bold"><?php echo htmlspecialchars($data_kendaraan['plat_nomor']); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        **Nomor Pendaftaran**
                        <span><?php echo htmlspecialchars($data_kendaraan['nomor_pendaftaran'] ?? '-'); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        **Nama Pemilik**
                        <span><?php echo htmlspecialchars($data_kendaraan['nama_pemilik_kendaraan'] ?? '-'); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between bg-light">
                        **Petugas Penguji** <span
                            class="fw-bold text-success"><?php echo htmlspecialchars($data_kendaraan['nama_petugas'] ?? 'Petugas Tidak Ditemukan'); ?></span>
                    </li>
                </ul>

                <hr>
                <h5 class="text-primary mt-4 mb-3">Penilaian Layanan (1 = Sangat Buruk, 5 = Sangat Baik)</h5>

                <form action="proses/proses-survey.php" method="POST">
                    <input type="hidden" name="id_kendaraan" value="<?php echo $data_kendaraan['id_kendaraan']; ?>">

                    <?php echo createRatingInput('rating_pelayanan', '1. Bagaimana penilaian Anda terhadap Pelayanan Petugas?'); ?>

                    <?php echo createRatingInput('rating_fasilitas', '2. Bagaimana penilaian Anda terhadap Fasilitas & Kebersihan Lingkungan?'); ?>

                    <?php echo createRatingInput('rating_kecepatan', '3. Bagaimana penilaian Anda terhadap Kecepatan Proses Pengujian?'); ?>

                    <div class="mb-4">
                        <label for="komentar" class="form-label fw-bold">4. Komentar dan Saran (Opsional)</label>
                        <textarea class="form-control" id="komentar" name="komentar" rows="3"
                            placeholder="Tulis masukan Anda di sini..."></textarea>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="bi bi-send-fill me-2"></i> Kirim Penilaian
                        </button>
                    </div>
                </form>

                <?php else: ?>
                <div class="alert alert-warning text-center p-5">
                    <i class="bi bi-hourglass-split display-4"></i>
                    <h4 class="mt-3">Semua Survey Sudah Terisi</h4>
                    <p class="mb-0">Belum ada antrian layanan kendaraan baru yang menunggu untuk diisi surveinya.
                        Silakan tunggu layanan berikutnya.</p>
                </div>
                <?php endif; ?>

            </div>
            <div class="card-footer text-muted text-center">
                &copy; <?php echo date('Y'); ?> UPT Pengujian Kendaraan Bermotor.
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>