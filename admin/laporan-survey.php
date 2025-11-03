<?php
// ===============================================
// HALAMAN LAPORAN SURVEY (REFACTOR: FIX EXPORT CSV)
// ===============================================

// *** PERBAIKAN 1: AKTIFKAN OUTPUT BUFFERING UNTUK MENCEGAH HEADER ERROR ***
ob_start();

// Pastikan koneksi ke database sudah tersedia
require_once '../config/koneksi.php';

// --- Mendapatkan nama file saat ini untuk Link dan Form ---
$current_file_name = basename($_SERVER['PHP_SELF']); 

// --- Konfigurasi Sortir dan Limit ---
// Daftar kolom DB yang diizinkan untuk disortir
$allowed_sort_columns_map = [
    'plat_nomor' => 'k.plat_nomor', 
    'nomor_pendaftaran' => 'k.nomor_pendaftaran',
    'petugas' => 'p.nama_petugas',
    'pelayanan' => 's.rating_pelayanan',
    'fasilitas' => 's.rating_fasilitas',
    'kecepatan' => 's.rating_kecepatan',
    'waktu' => 's.filled_at'
];

$default_sort_by = 'waktu';
$default_sort_order = 'DESC';

$sort_by = isset($_GET['sort_by']) && isset($allowed_sort_columns_map[$_GET['sort_by']]) 
    ? $_GET['sort_by'] 
    : $default_sort_by;
$sort_order = isset($_GET['sort_order']) && strtoupper($_GET['sort_order']) === 'ASC' 
    ? 'ASC' 
    : $default_sort_order;
$sort_column_db = $allowed_sort_columns_map[$sort_by];

// --- Konfigurasi Pagination & Limit ---
$allowed_limits = [10, 25, 50, 100];
$data_per_halaman = isset($_GET['limit']) && in_array((int)$_GET['limit'], $allowed_limits) 
    ? (int)$_GET['limit'] 
    : 10;
    
$halaman_saat_ini = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;

// --- Konfigurasi Pencarian ---
$kata_kunci = isset($_GET['cari']) ? trim($_GET['cari']) : '';

// ===============================================
// FUNGSI UTAMA UNTUK MEMBANGUN KLAUSA QUERY
// ===============================================
function build_query_filters($kata_kunci) {
    $params = [];
    $where_clause = " WHERE k.status_survey = 1"; // Hanya tampilkan yang sudah di survey

    if (!empty($kata_kunci)) {
        $where_clause .= " AND (k.plat_nomor LIKE :cari OR k.nomor_pendaftaran LIKE :cari)";
        $params[':cari'] = '%' . $kata_kunci . '%';
    }
    return ['where' => $where_clause, 'params' => $params];
}

// Fungsi helper untuk mempertahankan parameter GET pada link
function build_query_string($new_params = []) {
    $current_params = $_GET;
    $updated_params = array_merge($current_params, $new_params);
    if (!isset($new_params['halaman'])) {
        unset($updated_params['halaman']);
    }
    unset($updated_params['export']); // Hapus 'export' dari link biasa
    return http_build_query($updated_params);
}

// ===============================================
// 3. LOGIKA EXPORT CSV 
// ===============================================
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filters = build_query_filters($kata_kunci);
    
    $sql_export = "
        SELECT 
            k.plat_nomor, k.nomor_pendaftaran, k.nama_pemilik_kendaraan,
            p.nama_petugas,
            s.rating_pelayanan, s.rating_fasilitas, s.rating_kecepatan, s.komentar, s.filled_at
        FROM 
            kendaraan k
        INNER JOIN 
            survey s ON k.id_kendaraan = s.id_kendaraan
        LEFT JOIN 
            petugas p ON k.id_petugas = p.id_petugas
        {$filters['where']}
        ORDER BY 
            {$sort_column_db} {$sort_order}";

    try {
        $stmt_export = $pdo->prepare($sql_export);
        
        // Bind parameter pencarian
        foreach ($filters['params'] as $key => $value) {
            $stmt_export->bindValue($key, $value, PDO::PARAM_STR);
        }

        $stmt_export->execute();
        $results = $stmt_export->fetchAll(PDO::FETCH_ASSOC);

        // *** PERBAIKAN 2: CLEAN BUFFER SEBELUM MENGIRIM HEADER ***
        ob_end_clean(); 
        
        // Header CSV (menginstruksikan browser untuk download)
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=laporan_survey_' . date('Ymd_His') . '.csv');
        
        $output = fopen('php://output', 'w');

        // Tulis BOM (Byte Order Mark) untuk kompatibilitas Excel UTF-8
        fputs($output, "\xEF\xBB\xBF"); 

        // Tulis header kolom (Judul)
        fputcsv($output, [
            'Plat Nomor', 
            'Nomor Pendaftaran', 
            'Nama Pemilik', 
            'Petugas Lapangan', 
            'Rating Pelayanan (1-5)', 
            'Rating Fasilitas (1-5)', 
            'Rating Kecepatan (1-5)', 
            'Komentar', 
            'Waktu Survey'
        ]);

        // Tulis baris data
        foreach ($results as $row) {
            fputcsv($output, [
                $row['plat_nomor'],
                $row['nomor_pendaftaran'],
                $row['nama_pemilik_kendaraan'],
                $row['nama_petugas'],
                $row['rating_pelayanan'],
                $row['rating_fasilitas'],
                $row['rating_kecepatan'],
                $row['komentar'],
                date('d-m-Y H:i:s', strtotime($row['filled_at']))
            ]);
        }

        fclose($output);
        exit();
    } catch (PDOException $e) {
        // Jika gagal, hentikan buffering dan tampilkan pesan error sederhana
        ob_end_clean();
        http_response_code(500); // Set status kode error
        die("Gagal Export Data. Mohon periksa log database: " . $e->getMessage());
    }
}


// ===============================================
// 4. LOGIKA QUERY DATA UNTUK TAMPILAN HALAMAN (Tidak Berubah Signifikan)
// ===============================================
$laporan_list = [];
$total_data = 0;
$total_halaman = 1;
$filters = build_query_filters($kata_kunci);

try {
    // 1. Query untuk menghitung total data
    $sql_count = "
        SELECT 
            COUNT(k.id_kendaraan) 
        FROM 
            kendaraan k
        INNER JOIN 
            survey s ON k.id_kendaraan = s.id_kendaraan
        " . $filters['where'];
        
    $stmt_count = $pdo->prepare($sql_count);
    $stmt_count->execute($filters['params']);
    $total_data = $stmt_count->fetchColumn();

    $total_halaman = ceil($total_data / $data_per_halaman);
    if ($halaman_saat_ini > $total_halaman && $total_halaman > 0) {
        $halaman_saat_ini = $total_halaman;
    }
    $offset = ($halaman_saat_ini - 1) * $data_per_halaman;
    if ($offset < 0) $offset = 0;

    // 2. Query untuk mengambil data laporan dengan LIMIT & SORTING
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
        " . $filters['where'] . "
        ORDER BY 
            {$sort_column_db} {$sort_order}
        LIMIT :limit OFFSET :offset";

    $stmt_data = $pdo->prepare($sql_data);

    foreach ($filters['params'] as $key => $value) {
        $stmt_data->bindValue($key, $value, PDO::PARAM_STR);
    }
    
    $stmt_data->bindValue(':limit', $data_per_halaman, PDO::PARAM_INT);
    $stmt_data->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt_data->execute();
    $laporan_list = $stmt_data->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "QUERY GAGAL: " . $e->getMessage();
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

    .sortable-th {
        cursor: pointer;
        user-select: none;
    }

    .sort-icon {
        margin-left: 5px;
        font-size: 0.7em;
        vertical-align: middle;
    }
    </style>
</head>

<body>

    <?php include 'sidebar.php' // Asumsi file sidebar.php ada ?>

    <div class="content">
        <h2 class="mb-4"><i class="bi bi-bar-chart-line-fill me-2"></i> Laporan Hasil Survey</h2>

        <div id="alert-container">
            <?php 
                if (isset($error_message)) {
                    echo "<div class='alert alert-danger alert-dismissible fade show' role='alert'>";
                    echo "<i class='bi bi-x-octagon me-2'></i> " . htmlspecialchars($error_message);
                    echo "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>";
                    echo "</div>";
                } elseif (isset($_GET['status']) && isset($_GET['pesan'])) {
                    $status = ($_GET['status'] == 'sukses') ? 'success' : 'danger';
                    $icon = ($_GET['status'] == 'sukses') ? 'bi-check-circle' : 'bi-x-octagon';
                    echo "<div class='alert alert-$status alert-dismissible fade show' role='alert'>";
                    echo "<i class='bi $icon me-2'></i> " . htmlspecialchars($_GET['pesan']);
                    echo "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>";
                    echo "</div>";
                }
            ?>
        </div>

        <div class="row mb-3 align-items-center">
            <div class="col-md-7 d-flex">
                <form action="<?php echo $current_file_name; ?>" method="GET" class="d-flex me-3">
                    <?php if (isset($_GET['limit'])): ?>
                    <input type="hidden" name="limit" value="<?php echo htmlspecialchars($data_per_halaman); ?>">
                    <?php endif; ?>
                    <?php if (isset($_GET['sort_by'])): ?>
                    <input type="hidden" name="sort_by" value="<?php echo htmlspecialchars($sort_by); ?>">
                    <input type="hidden" name="sort_order" value="<?php echo htmlspecialchars($sort_order); ?>">
                    <?php endif; ?>

                    <input type="text" name="cari" class="form-control me-2" placeholder="Cari Plat/No. Pendaftaran..."
                        value="<?php echo htmlspecialchars($kata_kunci); ?>">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
                    <?php if (!empty($kata_kunci)): ?>
                    <a href="<?php echo $current_file_name; ?>?<?php echo build_query_string(['cari' => '']); ?>"
                        class="btn btn-outline-secondary ms-2" title="Reset Pencarian"><i class="bi bi-x-circle"></i>
                    </a>
                    <?php endif; ?>
                </form>

                <form action="<?php echo $current_file_name; ?>" method="GET" class="d-flex align-items-center me-3">
                    <?php foreach ($_GET as $key => $value): ?>
                    <?php if ($key != 'limit' && $key != 'halaman'): ?>
                    <input type="hidden" name="<?php echo htmlspecialchars($key); ?>"
                        value="<?php echo htmlspecialchars($value); ?>">
                    <?php endif; ?>
                    <?php endforeach; ?>
                    <label for="data_limit" class="me-2 text-muted" style="white-space: nowrap;">Tampilkan:</label>
                    <select name="limit" id="data_limit" class="form-select form-select-sm" style="width: auto;"
                        onchange="this.form.submit()">
                        <?php foreach ($allowed_limits as $limit): ?>
                        <option value="<?php echo $limit; ?>"
                            <?php echo ($data_per_halaman == $limit) ? 'selected' : ''; ?>>
                            <?php echo $limit; ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <div class="col-md-5 text-end">
                <a href="<?php echo $current_file_name; ?>?<?php echo build_query_string(['export' => 'csv']); ?>"
                    class="btn btn-success text-white">
                    <i class="bi bi-download me-2"></i> Export CSV
                </a>
            </div>
        </div>

        <p class="text-muted">Menampilkan <?php echo count($laporan_list); ?> dari total
            **<?php echo number_format($total_data); ?>** laporan.</p>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-primary">
                            <tr>
                                <th class="text-center">#</th>
                                <?php 
                                    function render_sort_header($label, $column_key, $current_sort_by, $current_sort_order) {
                                        $new_order = ($current_sort_by == $column_key && $current_sort_order == 'ASC') ? 'DESC' : 'ASC';
                                        $icon = '';
                                        if ($current_sort_by == $column_key) {
                                            $icon = ($current_sort_order == 'ASC') ? '<i class="bi bi-caret-up-fill sort-icon"></i>' : '<i class="bi bi-caret-down-fill sort-icon"></i>';
                                        }
                                        $query_string = build_query_string(['sort_by' => $column_key, 'sort_order' => $new_order, 'halaman' => 1]);
                                        return "<th class='sortable-th'><a href=\"?{$query_string}\" class='text-white text-decoration-none'>{$label} {$icon}</a></th>";
                                    }
                                ?>
                                <?php echo render_sort_header('Plat Nomor', 'plat_nomor', $sort_by, $sort_order); ?>
                                <?php echo render_sort_header('No. Pendaftaran', 'nomor_pendaftaran', $sort_by, $sort_order); ?>
                                <?php echo render_sort_header('Petugas Lapangan', 'petugas', $sort_by, $sort_order); ?>
                                <th class="text-center">
                                    <a href="?<?php echo build_query_string(['sort_by' => 'pelayanan', 'sort_order' => ($sort_by == 'pelayanan' && $sort_order == 'ASC' ? 'DESC' : 'ASC'), 'halaman' => 1]); ?>"
                                        class="text-white text-decoration-none">Pelayanan
                                        <?php if ($sort_by == 'pelayanan'): ?>
                                        <i
                                            class="bi bi-caret-<?php echo $sort_order == 'ASC' ? 'up' : 'down'; ?>-fill sort-icon"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th class="text-center">
                                    <a href="?<?php echo build_query_string(['sort_by' => 'fasilitas', 'sort_order' => ($sort_by == 'fasilitas' && $sort_order == 'ASC' ? 'DESC' : 'ASC'), 'halaman' => 1]); ?>"
                                        class="text-white text-decoration-none">Fasilitas
                                        <?php if ($sort_by == 'fasilitas'): ?>
                                        <i
                                            class="bi bi-caret-<?php echo $sort_order == 'ASC' ? 'up' : 'down'; ?>-fill sort-icon"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th class="text-center">
                                    <a href="?<?php echo build_query_string(['sort_by' => 'kecepatan', 'sort_order' => ($sort_by == 'kecepatan' && $sort_order == 'ASC' ? 'DESC' : 'ASC'), 'halaman' => 1]); ?>"
                                        class="text-white text-decoration-none">Kecepatan
                                        <?php if ($sort_by == 'kecepatan'): ?>
                                        <i
                                            class="bi bi-caret-<?php echo $sort_order == 'ASC' ? 'up' : 'down'; ?>-fill sort-icon"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th class="text-center">
                                    <a href="?<?php echo build_query_string(['sort_by' => 'waktu', 'sort_order' => ($sort_by == 'waktu' && $sort_order == 'DESC' ? 'ASC' : 'DESC'), 'halaman' => 1]); ?>"
                                        class="text-white text-decoration-none">Waktu Survey
                                        <?php if ($sort_by == 'waktu'): ?>
                                        <i
                                            class="bi bi-caret-<?php echo $sort_order == 'ASC' ? 'up' : 'down'; ?>-fill sort-icon"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
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
                                <td class="text-center">
                                    <?php echo date('d/m/Y H:i', strtotime($data['filled_at'])); ?>
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
                                <td colspan="10" class="text-center py-4">
                                    <i class="bi bi-info-circle me-1"></i> Tidak ada data survey yang ditemukan.
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
                        href="?<?php echo build_query_string(['halaman' => $halaman_saat_ini - 1]); ?>">Previous</a>
                </li>

                <?php 
                    $start_page = max(1, $halaman_saat_ini - 2);
                    $end_page = min($total_halaman, $halaman_saat_ini + 2);
                    
                    if ($start_page > 1) { echo '<li class="page-item disabled"><span class="page-link">...</span></li>'; }

                    for ($i = $start_page; $i <= $end_page; $i++): 
                ?>
                <li class="page-item <?php echo ($i == $halaman_saat_ini) ? 'active' : ''; ?>">
                    <a class="page-link"
                        href="?<?php echo build_query_string(['halaman' => $i]); ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>

                <?php if ($end_page < $total_halaman) { echo '<li class="page-item disabled"><span class="page-link">...</span></li>'; } ?>

                <li class="page-item <?php echo ($halaman_saat_ini >= $total_halaman) ? 'disabled' : ''; ?>">
                    <a class="page-link"
                        href="?<?php echo build_query_string(['halaman' => $halaman_saat_ini + 1]); ?>">Next</a>
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

    const formatDateTime = (dateString) => new Date(dateString).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });

    function createStarRating(rating) {
        let output = '';
        for (let i = 1; i <= 5; i++) {
            const color = (i <= rating) ? 'text-warning' : 'text-secondary';
            output += `<i class="bi bi-star-fill ${color}"></i>`;
        }
        return output;
    }

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

                document.getElementById('detail-plat_nomor_title').textContent = data.plat_nomor || 'N/A';
                document.getElementById('detail-nomor_pendaftaran').textContent = data.nomor_pendaftaran || '-';
                document.getElementById('detail-nama_pemilik_kendaraan').textContent = data
                    .nama_pemilik_kendaraan || '-';
                document.getElementById('detail-nama_petugas').textContent = data.nama_petugas || '-';
                document.getElementById('detail-filled_at').textContent = formatDateTime(data.filled_at);

                document.getElementById('detail-rating_pelayanan').innerHTML = createStarRating(data
                    .rating_pelayanan);
                document.getElementById('detail-rating_fasilitas').innerHTML = createStarRating(data
                    .rating_fasilitas);
                document.getElementById('detail-rating_kecepatan').innerHTML = createStarRating(data
                    .rating_kecepatan);

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