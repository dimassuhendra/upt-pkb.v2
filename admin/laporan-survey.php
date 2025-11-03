<?php
// ===============================================
// HALAMAN LAPORAN SURVEY
// ===============================================

require_once '../config/koneksi.php';

// --- Konfigurasi Pagination ---
$data_per_halaman = 10; 
$halaman_saat_ini = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
$offset = ($halaman_saat_ini - 1) * $data_per_halaman;

// --- Konfigurasi Pencarian ---
$kata_kunci = isset($_GET['cari']) ? trim($_GET['cari']) : '';

// --- Logika Query Data ---
$laporan_list = [];
$total_data = 0;
$total_halaman = 1;

try {
    $params = [];
    $where_clause = " WHERE k.status_survey = 1"; // Hanya tampilkan yang sudah di survey

    if (!empty($kata_kunci)) {
        $where_clause .= " AND (k.plat_nomor LIKE :cari OR k.nomor_pendaftaran LIKE :cari)";
        $params[':cari'] = '%' . $kata_kunci . '%';
    }

    // 1. Query untuk menghitung total data
    $sql_count = "
        SELECT 
            COUNT(k.id_kendaraan) 
        FROM 
            kendaraan k
        INNER JOIN 
            survey s ON k.id_kendaraan = s.id_kendaraan
        " . $where_clause;
        
    $stmt_count = $pdo->prepare($sql_count);
    $stmt_count->execute($params);
    $total_data = $stmt_count->fetchColumn();

    $total_halaman = ceil($total_data / $data_per_halaman);
    if ($halaman_saat_ini > $total_halaman && $total_halaman > 0) {
        $halaman_saat_ini = $total_halaman;
        $offset = ($halaman_saat_ini - 1) * $data_per_halaman;
    }

    // 2. Query untuk mengambil data laporan
    $sql_data = "
        SELECT 
            k.id_kendaraan, k.plat_nomor, k.nomor_pendaftaran,
            p.nama_petugas,
            s.rating_pelayanan, s.rating_fasilitas, s.rating_kecepatan, s.filled_at
        FROM 
            kendaraan k
        INNER JOIN 
            survey s ON k.id_kendaraan = s.id_kendaraan
        LEFT JOIN 
            petugas p ON k.id_petugas = p.id_petugas
        " . $where_clause . "
        ORDER BY 
            s.filled_at DESC
        LIMIT :limit OFFSET :offset";

    $stmt_data = $pdo->prepare($sql_data);

    if (!empty($kata_kunci)) {
        $stmt_data->bindParam(':cari', $params[':cari'], PDO::PARAM_STR);
    }
    
    $stmt_data->bindParam(':limit', $data_per_halaman, PDO::PARAM_INT);
    $stmt_data->bindParam(':offset', $offset, PDO::PARAM_INT);
    
    $stmt_data->execute();
    $laporan_list = $stmt_data->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>QUERY GAGAL: " . $e->getMessage() . "</div>";
}

// Fungsi untuk membuat bintang rating
function getStarRating($rating) {
    $output = '';
    for ($i = 1; $i <= 5; $i++) {
        $color = ($i <= $rating) ? 'text-warning' : 'text-secondary';
        $output .= "<i class='bi bi-star-fill $color' style='font-size: 0.8rem;'></i>";
    }
    return $output;
}

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Survey | UPT PKB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
    .content {
        margin-left: 250px;
        padding: 20px;
    }
    </style>
</head>

<body>

    <?php include 'sidebar.php' // Asumsi file sidebar.php ada ?>

    <div class="content">
        <h2 class="mb-4"><i class="bi bi-bar-chart-line-fill me-2"></i> Laporan Hasil Survey</h2>

        <div id="alert-container">
            <?php 
                if (isset($_GET['status']) && isset($_GET['pesan'])) {
                    $status = ($_GET['status'] == 'sukses') ? 'success' : 'danger';
                    $icon = ($_GET['status'] == 'sukses') ? 'bi-check-circle' : 'bi-x-octagon';
                    echo "<div class='alert alert-$status alert-dismissible fade show' role='alert'>";
                    echo "<i class='bi $icon me-2'></i> " . htmlspecialchars($_GET['pesan']);
                    echo "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>";
                    echo "</div>";
                }
            ?>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <form action="laporan-survei.php" method="GET" class="d-flex">
                    <input type="text" name="cari" class="form-control me-2" placeholder="Cari Plat/No. Pendaftaran..."
                        value="<?php echo htmlspecialchars($kata_kunci); ?>">
                    <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i> Cari</button>
                    <?php if (!empty($kata_kunci)): ?>
                    <a href="laporan-survei.php" class="btn btn-outline-secondary ms-2"><i class="bi bi-x-circle"></i>
                        Reset</a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="col-md-6 text-end">
                <button class="btn btn-info text-white" disabled><i class="bi bi-download me-2"></i> Export
                    Data</button>
            </div>
        </div>

        <p class="text-muted">Total laporan ditemukan: **<?php echo number_format($total_data); ?>**</p>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-primary">
                            <tr>
                                <th class="text-center">#</th>
                                <th>Plat Nomor</th>
                                <th>No. Pendaftaran</th>
                                <th>Petugas Lapangan</th>
                                <th class="text-center">Pelayanan (Rata-rata)</th>
                                <th class="text-center">Fasilitas</th>
                                <th class="text-center">Kecepatan</th>
                                <th class="text-center">Waktu Survey</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($laporan_list) > 0): ?>
                            <?php $no = $offset + 1; ?>
                            <?php foreach ($laporan_list as $data): ?>
                            <tr>
                                <td class="text-center"><?php echo $no++; ?></td>
                                <td class="fw-bold"><?php echo htmlspecialchars($data['plat_nomor']); ?></td>
                                <td><?php echo htmlspecialchars($data['nomor_pendaftaran'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($data['nama_petugas'] ?? '-'); ?></td>
                                <td class="text-center"><?php echo getStarRating($data['rating_pelayanan']); ?></td>
                                <td class="text-center"><?php echo getStarRating($data['rating_fasilitas']); ?></td>
                                <td class="text-center"><?php echo getStarRating($data['rating_kecepatan']); ?></td>
                                <td class="text-center"><?php echo date('d/m/Y H:i', strtotime($data['filled_at'])); ?>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-info text-white" title="Lihat Komentar"
                                        onclick="showDetailSurvey(<?php echo $data['id_kendaraan']; ?>)">
                                        <i class="bi bi-file-earmark-text"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">
                                    <i class="bi bi-info-circle me-1"></i> Belum ada data survey yang terisi.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if ($total_halaman > 1): ?>
        <nav>
            <ul class="pagination justify-content-center">
                <li class="page-item <?php echo ($halaman_saat_ini <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link"
                        href="?halaman=<?php echo $halaman_saat_ini - 1; ?>&cari=<?php echo urlencode($kata_kunci); ?>">Previous</a>
                </li>

                <?php for ($i = 1; $i <= $total_halaman; $i++): ?>
                <li class="page-item <?php echo ($i == $halaman_saat_ini) ? 'active' : ''; ?>">
                    <a class="page-link"
                        href="?halaman=<?php echo $i; ?>&cari=<?php echo urlencode($kata_kunci); ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>

                <li class="page-item <?php echo ($halaman_saat_ini >= $total_halaman) ? 'disabled' : ''; ?>">
                    <a class="page-link"
                        href="?halaman=<?php echo $halaman_saat_ini + 1; ?>&cari=<?php echo urlencode($kata_kunci); ?>">Next</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>

    </div>
    <div class="modal fade" id="surveyDetailModal" tabindex="-1" aria-labelledby="surveyDetailModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="surveyDetailModalLabel"><i class="bi bi-chat-dots me-2"></i> Detail
                        Hasil Survey</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="survey-modal-loader" class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Memuat data...</p>
                    </div>
                    <div id="survey-data-container" style="display:none;">
                        <h5 class="mb-3"><span id="detail-plat_nomor_title" class="badge bg-primary"></span></h5>
                        <table class="table table-bordered table-sm">
                            <tr>
                                <th>No. Pendaftaran</th>
                                <td id="detail-nomor_pendaftaran"></td>
                            </tr>
                            <tr>
                                <th>Nama Pemilik</th>
                                <td id="detail-nama_pemilik_kendaraan"></td>
                            </tr>
                            <tr>
                                <th>Petugas Lapangan</th>
                                <td id="detail-nama_petugas"></td>
                            </tr>
                            <tr>
                                <th>Waktu Survey</th>
                                <td id="detail-filled_at"></td>
                            </tr>
                        </table>

                        <h6 class="mt-4 text-primary">Rating Pelanggan</h6>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Pelayanan Petugas
                                <span id="detail-rating_pelayanan"></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Kelengkapan Fasilitas
                                <span id="detail-rating_fasilitas"></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Kecepatan Proses
                                <span id="detail-rating_kecepatan"></span>
                            </li>
                        </ul>

                        <h6 class="mt-4 text-primary">Komentar Pelanggan</h6>
                        <p id="detail-komentar" class="alert alert-light border"></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    const endpoint = 'proses/get_detail-survey.php?id=';

    // Helper untuk format tanggal dan waktu
    const formatDateTime = (dateString) => new Date(dateString).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });

    // Fungsi untuk membuat bintang rating
    function createStarRating(rating) {
        let output = '';
        for (let i = 1; i <= 5; i++) {
            const color = (i <= rating) ? 'text-warning' : 'text-secondary';
            output += `<i class="bi bi-star-fill ${color}"></i>`;
        }
        return output;
    }

    // --- Fungsi Ambil dan Tampilkan Detail Survey (Modal Detail) ---
    async function showDetailSurvey(id) {
        const detailModal = new bootstrap.Modal(document.getElementById('surveyDetailModal'));
        const loader = document.getElementById('survey-modal-loader');
        const dataContainer = document.getElementById('survey-data-container');

        loader.style.display = 'block';
        dataContainer.style.display = 'none';

        detailModal.show();

        try {
            const response = await fetch(endpoint + id);
            const result = await response.json();

            if (result.status === 'success') {
                const data = result.data;

                // Isi data Header
                document.getElementById('detail-plat_nomor_title').textContent = data.plat_nomor || 'N/A';
                document.getElementById('detail-nomor_pendaftaran').textContent = data.nomor_pendaftaran || '-';
                document.getElementById('detail-nama_pemilik_kendaraan').textContent = data
                    .nama_pemilik_kendaraan || '-';
                document.getElementById('detail-nama_petugas').textContent = data.nama_petugas || '-';
                document.getElementById('detail-filled_at').textContent = formatDateTime(data.filled_at);

                // Isi Rating
                document.getElementById('detail-rating_pelayanan').innerHTML = createStarRating(data
                    .rating_pelayanan);
                document.getElementById('detail-rating_fasilitas').innerHTML = createStarRating(data
                    .rating_fasilitas);
                document.getElementById('detail-rating_kecepatan').innerHTML = createStarRating(data
                    .rating_kecepatan);

                // Isi Komentar
                document.getElementById('detail-komentar').textContent = data.komentar ||
                    'Pelanggan tidak memberikan komentar.';

                loader.style.display = 'none';
                dataContainer.style.display = 'block';

            } else {
                alert('Gagal mengambil detail data: ' + result.message);
                detailModal.hide();
            }

        } catch (error) {
            console.error('Fetch error:', error);
            alert('Terjadi kesalahan saat menghubungi server untuk memuat detail survei.');
            detailModal.hide();
        }
    }
    </script>
</body>

</html>