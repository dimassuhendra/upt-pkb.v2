<?php
// ===============================================
// HALAMAN KELOLA DATA PETUGAS (ADMIN) - REVISI
// ===============================================

require_once '../config/koneksi.php';

// --- Konfigurasi Pagination ---
$data_per_halaman = 10; 
$halaman_saat_ini = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
$offset = ($halaman_saat_ini - 1) * $data_per_halaman;

// --- Konfigurasi Pencarian ---
$kata_kunci = isset($_GET['cari']) ? trim($_GET['cari']) : '';

// --- Logika Query Data ---
$petugas_list = [];
$total_data = 0;
$total_halaman = 1;

try {
    $params = [];
    $where_clause = "";

    if (!empty($kata_kunci)) {
        // Hanya mencari berdasarkan nama petugas atau username
        $where_clause = " WHERE p.nama_petugas LIKE :cari OR u.username LIKE :cari";
        $params[':cari'] = '%' . $kata_kunci . '%';
    }

    // 1. Query untuk menghitung total data
    $sql_count = "SELECT COUNT(*) FROM petugas p LEFT JOIN users u ON p.id_users = u.id_users" . $where_clause;
    $stmt_count = $pdo->prepare($sql_count);
    $stmt_count->execute($params);
    $total_data = $stmt_count->fetchColumn();

    $total_halaman = ceil($total_data / $data_per_halaman);
    if ($halaman_saat_ini > $total_halaman && $total_halaman > 0) {
        $halaman_saat_ini = $total_halaman;
        $offset = ($halaman_saat_ini - 1) * $data_per_halaman;
    }

    // 2. Query untuk mengambil data petugas
    $sql_data = "
        SELECT 
            p.id_petugas, p.nama_petugas, p.created_at,
            u.username 
        FROM 
            petugas p
        LEFT JOIN 
            users u ON p.id_users = u.id_users
        " . $where_clause . "
        ORDER BY 
            p.created_at DESC
        LIMIT :limit OFFSET :offset";

    $stmt_data = $pdo->prepare($sql_data);

    if (!empty($kata_kunci)) {
        $stmt_data->bindParam(':cari', $params[':cari'], PDO::PARAM_STR);
    }
    
    $stmt_data->bindParam(':limit', $data_per_halaman, PDO::PARAM_INT);
    $stmt_data->bindParam(':offset', $offset, PDO::PARAM_INT);
    
    $stmt_data->execute();
    $petugas_list = $stmt_data->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>QUERY GAGAL: " . $e->getMessage() . "</div>";
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Data Petugas | UPT PKB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&display=swap" rel="stylesheet">

    <style>
    /* DEFINISI WARNA HIJAU UNTUK NUANSA TEMA */
    :root {
        /* Warna Dasar Hijau */
        --bs-green-main: #198754;
        /* Mirip Bootstrap Success */
        --bs-green-vibrant: #20c997;
        /* Untuk Detail/Info */
        --bs-green-soft: #95d5b2;
        /* Untuk Edit/Warning */
        --bs-green-text: #0f5132;
        --bs-green-bg: #d1e7dd;
    }

    body {
        font-family: 'Lato', sans-serif;
        /* APLIKASI FONT LATO */
    }

    .content {
        margin-left: 250px;
        padding: 20px;
    }

    /* Kustomisasi Warna untuk Nuansa Hijau */

    /* 1. Warna Utama (Primary): Diubah ke Hijau */
    .btn-outline-primary {
        --bs-btn-color: var(--bs-green-main);
        --bs-btn-border-color: var(--bs-green-main);
        --bs-btn-hover-bg: var(--bs-green-main);
        --bs-btn-hover-border-color: var(--bs-green-main);
        --bs-btn-active-bg: var(--bs-green-dark);
        --bs-btn-active-border-color: var(--bs-green-dark);
        color: var(--bs-green-main);
    }

    .table-primary {
        /* Header Tabel */
        --bs-table-bg: var(--bs-green-bg);
        --bs-table-border-color: #badbcc;
        color: var(--bs-green-text);
        border-color: var(--bs-green-text);
    }

    .text-primary {
        color: var(--bs-green-main) !important;
    }

    /* 2. Warna Info (Detail): Diubah ke Hijau Lebih Terang */
    .bg-info {
        background-color: var(--bs-green-vibrant) !important;
        color: white !important;
    }

    .btn-info {
        background-color: var(--bs-green-vibrant) !important;
        border-color: var(--bs-green-vibrant);
        color: white !important;
    }

    .text-info {
        color: var(--bs-green-vibrant) !important;
    }

    /* 3. Warna Warning (Edit): Diubah ke Hijau yang Berbeda (Olive Green) untuk Kontras */
    .bg-warning {
        background-color: var(--bs-green-soft) !important;
        color: #333 !important;
        /* Teks gelap agar terbaca */
    }

    .btn-warning {
        background-color: var(--bs-green-soft) !important;
        border-color: var(--bs-green-soft);
        color: #333 !important;
        /* Teks gelap agar terbaca */
    }

    .text-warning {
        /* Untuk spinner loading edit */
        color: var(--bs-green-soft) !important;
    }
    </style>
</head>

<body>

    <?php include 'sidebar.php' // Asumsi file sidebar.php ada ?>

    <div class="content">
        <h2 class="mb-4"><i class="bi bi-person-badge-fill me-2"></i> Kelola Data Petugas</h2>

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
                <form action="kelola-petugas.php" method="GET" class="d-flex">
                    <input type="text" name="cari" class="form-control me-2" placeholder="Cari Nama/Username..."
                        value="<?php echo htmlspecialchars($kata_kunci); ?>">
                    <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i> Cari</button>
                    <?php if (!empty($kata_kunci)): ?>
                    <a href="kelola-petugas.php" class="btn btn-outline-secondary ms-2"><i class="bi bi-x-circle"></i>
                        Reset</a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="col-md-6 text-end">
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#tambahModal">
                    <i class="bi bi-person-plus-fill me-2"></i> Tambah Petugas Baru
                </button>
            </div>
        </div>

        <p class="text-muted">Total data ditemukan: <?php echo number_format($total_data); ?></p>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-primary">
                            <tr>
                                <th class="text-center">#</th>
                                <th>Nama Petugas</th>
                                <th>Username Login</th>
                                <th>Tgl. Bergabung</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($petugas_list) > 0): ?>
                            <?php $no = $offset + 1; ?>
                            <?php foreach ($petugas_list as $data): ?>
                            <tr>
                                <td class="text-center"><?php echo $no++; ?></td>
                                <td class="fw-bold"><?php echo htmlspecialchars($data['nama_petugas']); ?></td>
                                <td><?php echo htmlspecialchars($data['username'] ?? '-'); ?></td>
                                <td><?php echo date('d F Y', strtotime($data['created_at'])); ?></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-info text-white" title="Lihat Detail"
                                        onclick="showDetail(<?php echo $data['id_petugas']; ?>)">
                                        <i class="bi bi-eye"></i>
                                    </button>

                                    <button type="button" class="btn btn-sm btn-warning" title="Edit Data"
                                        onclick="showEditForm(<?php echo $data['id_petugas']; ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>

                                    <button type="button" class="btn btn-sm btn-danger" title="Hapus Data"
                                        onclick="konfirmasiHapus(<?php echo $data['id_petugas']; ?>, '<?php echo htmlspecialchars($data['nama_petugas']); ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">
                                    <i class="bi bi-info-circle me-1"></i> Data petugas tidak ditemukan.
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
    <div class="modal fade" id="tambahModal" tabindex="-1" aria-labelledby="tambahModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="proses/proses_kelola-petugas.php?aksi=tambah" method="POST">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title" id="tambahModalLabel"><i class="bi bi-person-plus-fill me-2"></i> Tambah
                            Petugas</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <h6 class="text-primary mb-3">Data Petugas</h6>
                        <div class="mb-3">
                            <label for="tambah-nama_petugas" class="form-label">Nama Petugas <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="tambah-nama_petugas" name="nama_petugas"
                                required>
                        </div>

                        <h6 class="text-primary mt-4 mb-3">Data Akun Login</h6>
                        <div class="mb-3">
                            <label for="tambah-username" class="form-label">Username <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="tambah-username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="tambah-email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="tambah-email" name="email">
                        </div>
                        <div class="mb-3">
                            <label for="tambah-password" class="form-label">Password <span
                                    class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="tambah-password" name="password" required>
                            <small class="text-muted">Password awal untuk login.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success"><i class="bi bi-save me-1"></i> Simpan
                            Petugas</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="detailModalLabel"><i class="bi bi-card-list me-2"></i> Detail Petugas
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
                        <h6 class="text-primary mb-3">Informasi Petugas</h6>
                        <table class="table table-bordered table-striped">
                            <tr>
                                <th style="width: 40%;">ID Petugas</th>
                                <td id="detail-id_petugas"></td>
                            </tr>
                            <tr>
                                <th>Nama Petugas</th>
                                <td id="detail-nama_petugas"></td>
                            </tr>
                            <tr>
                                <th>Nama Lengkap User</th>
                                <td id="detail-nama_lengkap"></td>
                            </tr>
                            <tr>
                                <th>Tgl. Bergabung</th>
                                <td id="detail-created_at"></td>
                            </tr>
                        </table>

                        <h6 class="text-primary mt-4 mb-3">Informasi Akun Login</h6>
                        <table class="table table-bordered table-striped">
                            <tr>
                                <th>Username</th>
                                <td id="detail-username"></td>
                            </tr>
                            <tr>
                                <th>Email</th>
                                <td id="detail-email"></td>
                            </tr>
                            <tr>
                                <th>Role Akun</th>
                                <td id="detail-role"></td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editForm" action="proses/proses_kelola-petugas.php?aksi=edit" method="POST">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title" id="editModalLabel"><i class="bi bi-pencil me-2"></i> Edit Data Petugas
                        </h5>
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

                            <input type="hidden" name="id_petugas" id="edit-id_petugas">

                            <h6 class="text-primary mb-3">Edit Data Pribadi</h6>
                            <div class="mb-3">
                                <label for="edit-nama_petugas" class="form-label">Nama Petugas <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit-nama_petugas" name="nama_petugas"
                                    required>
                            </div>

                            <p class="mt-4 text-muted border-top pt-2">
                                <small>Data yang diedit hanya berlaku untuk kolom `nama_petugas` di tabel
                                    `petugas`.</small>
                            </p>
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
    const endpoint = 'proses/get_detail-petugas.php?id=';

    // Helper untuk format tanggal
    const formatDate = (dateString) => new Date(dateString).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });

    // --- 1. Fungsi Konfirmasi Hapus ---
    function konfirmasiHapus(id, nama) {
        if (confirm(
                `Apakah Anda yakin ingin menghapus data petugas: ${nama}?\n\nPERINGATAN: Penghapusan akan menghapus data login dan mungkin data terkait lainnya!`
            )) {
            window.location.href = `proses/proses_kelola-petugas.php?aksi=hapus&id=${id}`;
        }
    }

    // --- 2. Fungsi Ambil dan Tampilkan Detail Petugas (Modal Detail) ---
    async function showDetail(id) {
        const detailModal = new bootstrap.Modal(document.getElementById('detailModal'));
        const loader = document.getElementById('detail-modal-loader');
        const dataContainer = document.getElementById('detail-data-container');

        loader.style.display = 'block';
        dataContainer.style.display = 'none';

        detailModal.show();

        try {
            const response = await fetch(endpoint + id);
            const result = await response.json();

            if (result.status === 'success') {
                const data = result.data;

                // Isi data Petugas
                document.getElementById('detail-id_petugas').textContent = data.id_petugas;
                document.getElementById('detail-nama_petugas').textContent = data.nama_petugas || '-';
                document.getElementById('detail-nama_lengkap').textContent = data.nama_lengkap || '-';
                document.getElementById('detail-created_at').textContent = formatDate(data.created_at);

                // Isi data Akun Login
                document.getElementById('detail-username').textContent = data.username || '-';
                document.getElementById('detail-email').textContent = data.email || '-';
                // Menggunakan 'role'
                document.getElementById('detail-role').textContent = data.role ? (data.role.charAt(0)
                    .toUpperCase() + data.role.slice(1)) : '-';

                loader.style.display = 'none';
                dataContainer.style.display = 'block';

            } else {
                alert('Gagal mengambil detail data: ' + result.message);
                detailModal.hide();
            }

        } catch (error) {
            console.error('Fetch error:', error);
            alert('Terjadi kesalahan saat menghubungi server untuk memuat detail.');
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
                document.getElementById('edit-id_petugas').value = data.id_petugas;
                document.getElementById('edit-nama_petugas').value = data.nama_petugas || '';

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