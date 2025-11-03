<?php
// ===============================================
// HALAMAN KELOLA DATA KENDARAAN (REVISI)
// ===============================================

require_once '../config/koneksi.php';

// --- Ambil Data Petugas untuk Dropdown Edit ---
$petugas_list = [];
try {
    $stmt_petugas = $pdo->query("SELECT id_petugas, nama_petugas FROM petugas ORDER BY nama_petugas ASC");
    $petugas_list = $stmt_petugas->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Abaikan error atau log, ini opsional
}


// --- Konfigurasi Pagination ---
$data_per_halaman = 10;
$halaman_saat_ini = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
$offset = ($halaman_saat_ini - 1) * $data_per_halaman;

// --- Konfigurasi Pencarian ---
$kata_kunci = isset($_GET['cari']) ? trim($_GET['cari']) : '';

// --- Logika Query Data ---
$kendaraan_list = [];
$total_data = 0;
$total_halaman = 1;

try {
    // 1. Query untuk menghitung total data (untuk pagination)
    $sql_count = "SELECT COUNT(*) FROM kendaraan k";
    $params = [];
    $where_clause = "";

    if (!empty($kata_kunci)) {
        $where_clause = " WHERE k.plat_nomor LIKE :cari OR k.nomor_pendaftaran LIKE :cari OR k.nama_pemilik_kendaraan LIKE :cari";
        $params[':cari'] = '%' . $kata_kunci . '%';
    }

    $stmt_count = $pdo->prepare($sql_count . $where_clause);
    $stmt_count->execute($params);
    $total_data = $stmt_count->fetchColumn();

    // Hitung total halaman
    $total_halaman = ceil($total_data / $data_per_halaman);
    if ($halaman_saat_ini > $total_halaman && $total_halaman > 0) {
        $halaman_saat_ini = $total_halaman;
        $offset = ($halaman_saat_ini - 1) * $data_per_halaman;
    }


    // 2. Query untuk mengambil data kendaraan
    $sql_data = "
        SELECT 
            k.id_kendaraan, k.plat_nomor, k.tanggal_pendaftaran, k.nomor_pendaftaran, k.status_survey,
            p.nama_petugas
        FROM 
            kendaraan k
        LEFT JOIN 
            petugas p ON k.id_petugas = p.id_petugas
        " . $where_clause . "
        ORDER BY 
            k.created_at DESC
        LIMIT :limit OFFSET :offset";

    $stmt_data = $pdo->prepare($sql_data);

    if (!empty($kata_kunci)) {
        $stmt_data->bindParam(':cari', $params[':cari'], PDO::PARAM_STR);
    }
    
    $stmt_data->bindParam(':limit', $data_per_halaman, PDO::PARAM_INT);
    $stmt_data->bindParam(':offset', $offset, PDO::PARAM_INT);
    
    $stmt_data->execute();
    $kendaraan_list = $stmt_data->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>QUERY GAGAL: " . $e->getMessage() . "</div>";
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Data Kendaraan | UPT PKB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
    .content {
        margin-left: 250px;
        padding: 20px;
    }

    .status-badge {
        min-width: 100px;
    }
    </style>
</head>

<body>

    <?php include 'sidebar.php' // Menggunakan sidebar yang sama ?>

    <div class="content">
        <h2 class="mb-4"><i class="bi bi-list-columns-reverse me-2"></i> Kelola Data Kendaraan</h2>

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
                <form action="kelola-kendaraan.php" method="GET" class="d-flex">
                    <input type="text" name="cari" class="form-control me-2"
                        placeholder="Cari Plat/No. Pendaftaran/Pemilik..."
                        value="<?php echo htmlspecialchars($kata_kunci); ?>">
                    <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i> Cari</button>
                    <?php if (!empty($kata_kunci)): ?>
                    <a href="kelola-kendaraan.php" class="btn btn-outline-secondary ms-2"><i class="bi bi-x-circle"></i>
                        Reset</a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="col-md-6 text-end">
                <a href="index.php" class="btn btn-success"><i class="bi bi-plus-square me-2"></i> Tambah Kendaraan
                    Baru</a>
            </div>
        </div>

        <p class="text-muted">Total data ditemukan: **<?php echo number_format($total_data); ?>**</p>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-primary">
                            <tr>
                                <th class="text-center">#</th>
                                <th>No. Pendaftaran</th>
                                <th>Plat Nomor</th>
                                <th>Tgl. Daftar</th>
                                <th>Petugas Lapangan</th>
                                <th class="text-center">Status Survey</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($kendaraan_list) > 0): ?>
                            <?php $no = $offset + 1; ?>
                            <?php foreach ($kendaraan_list as $data): ?>
                            <tr>
                                <td class="text-center"><?php echo $no++; ?></td>
                                <td class="fw-bold"><?php echo htmlspecialchars($data['nomor_pendaftaran'] ?? '-'); ?>
                                </td>
                                <td><?php echo htmlspecialchars($data['plat_nomor']); ?></td>
                                <td><?php echo date('d F Y', strtotime($data['tanggal_pendaftaran'])); ?></td>
                                <td><?php echo htmlspecialchars($data['nama_petugas'] ?? '-'); ?></td>
                                <td class="text-center">
                                    <?php 
                                        $status = $data['status_survey'] == 1 ? 'Sudah Survey' : 'Belum Survey';
                                        $badge_color = $data['status_survey'] == 1 ? 'bg-success' : 'bg-danger';
                                    ?>
                                    <span
                                        class="badge <?php echo $badge_color; ?> status-badge"><?php echo $status; ?></span>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-info text-white" title="Lihat Detail"
                                        onclick="showDetail(<?php echo $data['id_kendaraan']; ?>)">
                                        <i class="bi bi-eye"></i>
                                    </button>

                                    <button type="button" class="btn btn-sm btn-warning" title="Edit Data"
                                        onclick="showEditForm(<?php echo $data['id_kendaraan']; ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>

                                    <button type="button" class="btn btn-sm btn-danger" title="Hapus Data"
                                        onclick="konfirmasiHapus(<?php echo $data['id_kendaraan']; ?>, '<?php echo htmlspecialchars($data['plat_nomor']); ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">
                                    <i class="bi bi-info-circle me-1"></i> Data kendaraan tidak ditemukan.
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
    <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="detailModalLabel"><i class="bi bi-card-list me-2"></i> Detail Kendaraan
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="detail-modal-loader" class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Memuat data...</p>
                    </div>
                    <div id="detail-data-container" style="display:none;">
                        <table class="table table-bordered table-striped">
                            <tr>
                                <th>ID Kendaraan</th>
                                <td id="detail-id_kendaraan"></td>
                            </tr>
                            <tr>
                                <th>Plat Nomor</th>
                                <td id="detail-plat_nomor"></td>
                            </tr>
                            <tr>
                                <th>Nomor Pendaftaran</th>
                                <td id="detail-nomor_pendaftaran"></td>
                            </tr>
                            <tr>
                                <th>Merk / Tipe</th>
                                <td id="detail-merk_tipe"></td>
                            </tr>
                            <tr>
                                <th>Jenis Kendaraan</th>
                                <td id="detail-jenis_kendaraan"></td>
                            </tr>
                            <tr>
                                <th>Nama Pemilik</th>
                                <td id="detail-nama_pemilik_kendaraan"></td>
                            </tr>
                            <tr>
                                <th>Tanggal Pendaftaran</th>
                                <td id="detail-tanggal_pendaftaran"></td>
                            </tr>
                            <tr>
                                <th>Status Survey</th>
                                <td id="detail-status_survey"></td>
                            </tr>
                            <tr>
                                <th>Diinput Oleh Petugas</th>
                                <td id="detail-nama_petugas"></td>
                            </tr>
                            <tr>
                                <th>Waktu Input</th>
                                <td id="detail-created_at"></td>
                            </tr>
                        </table>

                        <div id="survey-area" class="mt-4 p-3 border rounded" style="display:none;">
                            <h6 class="fw-bold text-success"><i class="bi bi-check-circle-fill me-1"></i> Hasil Survey
                            </h6>
                            <div class="row">
                                <div class="col-md-4">Pelayanan: <span id="detail-rating_pelayanan"
                                        class="badge bg-primary"></span></div>
                                <div class="col-md-4">Fasilitas: <span id="detail-rating_fasilitas"
                                        class="badge bg-primary"></span></div>
                                <div class="col-md-4">Kecepatan: <span id="detail-rating_kecepatan"
                                        class="badge bg-primary"></span></div>
                            </div>
                            <h6 class="mt-3">Komentar:</h6>
                            <p id="detail-komentar" class="alert alert-light border"></p>
                            <small class="text-muted">Diisi pada: <span id="detail-filled_at"></span></small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="editForm" action="proses/proses_kelola-kendaraan.php?aksi=edit" method="POST">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title" id="editModalLabel"><i class="bi bi-pencil me-2"></i> Edit Data
                            Kendaraan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="edit-modal-loader" class="text-center">
                            <div class="spinner-border text-warning" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Memuat form edit...</p>
                        </div>
                        <div id="edit-form-container" style="display:none;">

                            <input type="hidden" name="id_kendaraan" id="edit-id_kendaraan">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="edit-plat_nomor" class="form-label">Plat Nomor <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="edit-plat_nomor" name="plat_nomor"
                                        required>
                                </div>
                                <div class="col-md-6">
                                    <label for="edit-nomor_pendaftaran" class="form-label">Nomor Pendaftaran</label>
                                    <input type="text" class="form-control" id="edit-nomor_pendaftaran"
                                        name="nomor_pendaftaran">
                                </div>
                                <div class="col-md-6">
                                    <label for="edit-nama_pemilik_kendaraan" class="form-label">Nama Pemilik</label>
                                    <input type="text" class="form-control" id="edit-nama_pemilik_kendaraan"
                                        name="nama_pemilik_kendaraan">
                                </div>
                                <div class="col-md-6">
                                    <label for="edit-jenis_kendaraan" class="form-label">Jenis Kendaraan</label>
                                    <input type="text" class="form-control" id="edit-jenis_kendaraan"
                                        name="jenis_kendaraan">
                                </div>
                                <div class="col-md-6">
                                    <label for="edit-merk" class="form-label">Merk</label>
                                    <input type="text" class="form-control" id="edit-merk" name="merk">
                                </div>
                                <div class="col-md-6">
                                    <label for="edit-tipe" class="form-label">Tipe</label>
                                    <input type="text" class="form-control" id="edit-tipe" name="tipe">
                                </div>
                                <div class="col-12">
                                    <label for="edit-id_petugas" class="form-label">Petugas Lapangan <span
                                            class="text-danger">*</span></label>
                                    <select id="edit-id_petugas" name="id_petugas" class="form-select" required>
                                        <option value="">-- Pilih Petugas --</option>
                                        <?php foreach ($petugas_list as $petugas): ?>
                                        <option value="<?php echo $petugas['id_petugas']; ?>">
                                            <?php echo htmlspecialchars($petugas['nama_petugas']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning"><i class="bi bi-save me-1"></i> Simpan
                            Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // --- 1. Fungsi Konfirmasi Hapus ---
    function konfirmasiHapus(id, plat) {
        if (confirm(
                `Apakah Anda yakin ingin menghapus data kendaraan dengan Plat Nomor: ${plat}?\n\nSemua data terkait (termasuk data survey) akan ikut terhapus!`
            )) {
            window.location.href = `proses/proses_kelola-kendaraan.php?aksi=hapus&id=${id}`;
        }
    }

    const endpoint = 'proses/get_detail-kendaraan.php?id=';

    // --- 2. Fungsi Ambil dan Tampilkan Detail Kendaraan (Modal Detail) ---
    async function showDetail(id) {
        const detailModal = new bootstrap.Modal(document.getElementById('detailModal'));
        const loader = document.getElementById('detail-modal-loader');
        const dataContainer = document.getElementById('detail-data-container');
        const surveyArea = document.getElementById('survey-area');

        loader.style.display = 'block';
        dataContainer.style.display = 'none';
        surveyArea.style.display = 'none';

        detailModal.show();

        try {
            const response = await fetch(endpoint + id);
            const result = await response.json();

            if (result.status === 'success') {
                const data = result.data;
                const survey = result.survey;

                // Helper untuk format tanggal
                const formatDate = (dateString) => new Date(dateString).toLocaleDateString('id-ID', {
                    day: '2-digit',
                    month: 'long',
                    year: 'numeric'
                });
                const formatDateTime = (dateString) => new Date(dateString).toLocaleString('id-ID');

                // Isi data kendaraan
                document.getElementById('detail-id_kendaraan').textContent = data.id_kendaraan;
                document.getElementById('detail-plat_nomor').textContent = data.plat_nomor || '-';
                document.getElementById('detail-nomor_pendaftaran').textContent = data.nomor_pendaftaran || '-';
                document.getElementById('detail-merk_tipe').textContent = (data.merk || '-') + ' / ' + (data.tipe ||
                    '-');
                document.getElementById('detail-jenis_kendaraan').textContent = data.jenis_kendaraan || '-';
                document.getElementById('detail-nama_pemilik_kendaraan').textContent = data
                    .nama_pemilik_kendaraan || '-';
                document.getElementById('detail-tanggal_pendaftaran').textContent = formatDate(data
                    .tanggal_pendaftaran);
                document.getElementById('detail-nama_petugas').textContent = data.nama_petugas || '-';
                document.getElementById('detail-created_at').textContent = formatDateTime(data.created_at);

                const statusBadge = data.status_survey == 1 ? '<span class="badge bg-success">Sudah Survey</span>' :
                    '<span class="badge bg-danger">Belum Survey</span>';
                document.getElementById('detail-status_survey').innerHTML = statusBadge;

                // Isi data survey jika tersedia
                if (data.status_survey == 1 && survey) {
                    document.getElementById('detail-rating_pelayanan').textContent = survey.rating_pelayanan +
                        ' / 5';
                    document.getElementById('detail-rating_fasilitas').textContent = survey.rating_fasilitas +
                        ' / 5';
                    document.getElementById('detail-rating_kecepatan').textContent = survey.rating_kecepatan +
                        ' / 5';
                    document.getElementById('detail-komentar').textContent = survey.komentar ||
                        'Tidak ada komentar.';
                    document.getElementById('detail-filled_at').textContent = formatDateTime(survey.filled_at);
                    surveyArea.style.display = 'block';
                } else {
                    surveyArea.style.display = 'none';
                }

                loader.style.display = 'none';
                dataContainer.style.display = 'block';

            } else {
                alert('Gagal mengambil detail data: ' + result.message);
                detailModal.hide();
            }

        } catch (error) {
            console.error('Fetch error:', error);
            alert('Terjadi kesalahan saat menghubungi server.');
            detailModal.hide();
        }
    }

    // --- 3. Fungsi Ambil dan Tampilkan Form Edit (Modal Edit) ---
    async function showEditForm(id) {
        const editModal = new bootstrap.Modal(document.getElementById('editModal'));
        const loader = document.getElementById('edit-modal-loader');
        const formContainer = document.getElementById('edit-form-container');

        loader.style.display = 'block';
        formContainer.style.display = 'none';

        editModal.show();

        try {
            const response = await fetch(endpoint + id);
            const result = await response.json();

            if (result.status === 'success') {
                const data = result.data;

                // Isi Form Edit
                document.getElementById('edit-id_kendaraan').value = data.id_kendaraan;
                document.getElementById('edit-plat_nomor').value = data.plat_nomor || '';
                document.getElementById('edit-nomor_pendaftaran').value = data.nomor_pendaftaran || '';
                document.getElementById('edit-nama_pemilik_kendaraan').value = data.nama_pemilik_kendaraan || '';
                document.getElementById('edit-jenis_kendaraan').value = data.jenis_kendaraan || '';
                document.getElementById('edit-merk').value = data.merk || '';
                document.getElementById('edit-tipe').value = data.tipe || '';

                // Set dropdown Petugas yang dipilih
                document.getElementById('edit-id_petugas').value = data.id_petugas || '';

                // Sembunyikan loader, tampilkan form
                loader.style.display = 'none';
                formContainer.style.display = 'block';
            } else {
                alert('Gagal memuat form edit: ' + result.message);
                editModal.hide();
            }

        } catch (error) {
            console.error('Fetch error:', error);
            alert('Terjadi kesalahan saat menghubungi server untuk memuat form.');
            editModal.hide();
        }
    }
    </script>
</body>

</html>