<?php
// ===============================================
// PENGAMBILAN DATA MENGGUNAKAN PDO
// ===============================================

// Pastikan Anda telah menempatkan file koneksi.php di path yang benar: ../config/koneksi.php
require_once '../config/koneksi.php'; 

// Variabel $pdo sekarang sudah tersedia jika koneksi berhasil.

// 1. Inisialisasi variabel dengan nilai default (digunakan jika tidak ada data)
$total_kendaraan = 0;
$kendaraan_minggu_ini = 0;
$kendaraan_bulan_ini = 0;
$avg_rating = 0.00;
$list_petugas = [];

// Default data untuk Chart (agar Chart.js tidak error jika data kosong)
$chart_data_labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
$chart_data_ratings = [0, 0, 0, 0]; 

try {
    // a. Total Kendaraan Terdaftar
    $stmt_total = $pdo->query("SELECT COUNT(*) FROM kendaraan");
    $total_kendaraan = $stmt_total->fetchColumn();

    // b. Kendaraan Minggu Ini
    $stmt_minggu = $pdo->query("SELECT COUNT(*) FROM kendaraan WHERE YEARWEEK(tanggal_pendaftaran) = YEARWEEK(NOW())");
    $kendaraan_minggu_ini = $stmt_minggu->fetchColumn();

    // c. Kendaraan Bulan Ini
    $stmt_bulan = $pdo->query("SELECT COUNT(*) FROM kendaraan WHERE MONTH(tanggal_pendaftaran) = MONTH(NOW()) AND YEAR(tanggal_pendaftaran) = YEAR(NOW())");
    $kendaraan_bulan_ini = $stmt_bulan->fetchColumn();

    // d. Rata-rata Rating Keseluruhan
    $stmt_avg_rating = $pdo->query("SELECT AVG((rating_pelayanan + rating_fasilitas + rating_kecepatan) / 3) FROM survey");
    $avg_rating_raw = $stmt_avg_rating->fetchColumn();
    $avg_rating = $avg_rating_raw ? round((float)$avg_rating_raw, 2) : 0.00;
    
    // e. List Petugas untuk Form (Petugas Lapangan)
    $list_petugas = $pdo->query("SELECT id_petugas, nama_petugas FROM petugas ORDER BY nama_petugas ASC")->fetchAll(PDO::FETCH_ASSOC);
    
    // ---------------------------------------------------------------------
    // f. GENERASI NOMOR PENDAFTARAN OTOMATIS (DD/MM/YY/XXX)
    // ---------------------------------------------------------------------
    // 1. Hitung jumlah pendaftaran hari ini
    $sql_today_count = "SELECT COUNT(*) FROM kendaraan WHERE DATE(tanggal_pendaftaran) = CURDATE()";
    $stmt_today_count = $pdo->query($sql_today_count);
    $today_count = $stmt_today_count->fetchColumn();
    
    // 2. Tentukan nomor urut berikutnya
    $next_sequence_number = $today_count + 1;
    
    // 3. Format tanggal dan gabungkan
    $current_date_format = date('d/m/y');
    $nomor_pendaftaran_auto = $current_date_format . '/' . str_pad($next_sequence_number, 3, '0', STR_PAD_LEFT);
    
} catch (PDOException $e) {
    // Penanganan error Query
    echo "<div class='alert alert-danger'>QUERY GAGAL: " . $e->getMessage() . "</div>";
    // Variabel akan tetap menggunakan nilai default (0)
    $nomor_pendaftaran_auto = date('d/m/y') . '/001'; // Default jika query hitung gagal
}


// Data tanggal saat ini untuk tampilan
$tanggal_saat_ini = date('l, d F Y');
$bulan_saat_ini = date('F Y');

// Siapkan data chart untuk JS 
$chart_data_json_labels = json_encode($chart_data_labels);
$chart_data_json_ratings = json_encode($chart_data_ratings);

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin | UPT PKB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
    .sidebar {
        height: 100vh;
        position: fixed;
        top: 0;
        left: 0;
        width: 250px;
        padding-top: 56px;
        background-color: #343a40;
        color: white;
    }

    .content {
        margin-left: 250px;
        padding: 20px;
    }

    .sidebar a {
        color: #adb5bd;
        padding: 10px 15px;
        text-decoration: none;
        display: block;
    }

    .sidebar a:hover,
    .sidebar .active {
        background-color: #495057;
        color: white;
    }

    .calendar th,
    .calendar td {
        font-size: 0.8rem;
        padding: 5px !important;
        height: 30px;
        width: 30px;
    }
    </style>
</head>

<body>

    <div class="sidebar d-flex flex-column">
        <div class="p-3 text-center">
            <h5 class="text-white mb-0">UPT PKB DASHBOARD</h5>
            <small class="text-secondary">Administrator Mode</small>
        </div>
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item">
                <a href="index.php" class="nav-link active" aria-current="page">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="kelola_kendaraan.php" class="nav-link">
                    <i class="bi bi-truck me-2"></i> Kelola Kendaraan
                </a>
            </li>
            <li>
                <a href="kelola_petugas.php" class="nav-link">
                    <i class="bi bi-person-badge me-2"></i> Kelola Petugas
                </a>
            </li>
            <li>
                <a href="laporan_survey.php" class="nav-link">
                    <i class="bi bi-bar-chart-line me-2"></i> Laporan Survei
                </a>
            </li>
            <li>
                <a href="master_data.php" class="nav-link">
                    <i class="bi bi-database me-2"></i> Master Data
                </a>
            </li>
        </ul>
        <div class="mt-auto p-3 border-top">
            <a href="../logout.php" class="btn btn-outline-danger w-100">
                <i class="bi bi-box-arrow-right me-2"></i> Logout
            </a>
        </div>
    </div>

    <div class="content">
        <h2 class="mb-4">Dashboard Administrasi</h2>

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100 p-2">
                    <div class="card-body">
                        <h5 class="card-title text-primary text-center"><i class="bi bi-calendar3 me-2"></i>
                            <?php echo $bulan_saat_ini; ?></h5>
                        <div class="calendar mt-3">
                            <?php
                        // PHP untuk membuat kalender sederhana
                        $dayOfWeek = date('w', strtotime('first day of this month')); // 0 (Sun) to 6 (Sat)
                        $daysInMonth = date('t');
                        $currentDay = date('j');
                        
                        echo '<table class="table table-sm table-bordered text-center">';
                        echo '<thead><tr><th>Min</th><th>Sen</th><th>Sel</th><th>Rab</th><th>Kam</th><th>Jum</th><th>Sab</th></tr></thead>';
                        echo '<tbody><tr>';
                        
                        // Isi hari kosong di awal bulan
                        for ($i = 0; $i < $dayOfWeek; $i++) {
                            echo '<td></td>';
                        }
                        
                        // Isi tanggal
                        for ($day = 1; $day <= $daysInMonth; $day++) {
                            $isToday = ($day == $currentDay) ? 'bg-primary text-white fw-bold' : '';
                            
                            // Tambahkan hari di awal baris baru (setelah 7 hari)
                            if (($dayOfWeek + $day - 1) % 7 == 0 && $day != 1) {
                                echo '</tr><tr>';
                            }
                            
                            echo '<td class="'.$isToday.'">'.$day.'</td>';
                        }
                        
                        // Isi hari kosong di akhir bulan
                        $lastDayIndex = ($dayOfWeek + $daysInMonth - 1) % 7;
                        for ($i = $lastDayIndex; $i < 6; $i++) {
                            echo '<td></td>';
                        }
                        
                        echo '</tr></tbody></table>';
                        ?>
                        </div>
                        <p class="text-center mt-2 text-muted">Hari Ini: **<?php echo $tanggal_saat_ini; ?>**</p>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white">
                        <i class="bi bi-plus-square me-2"></i> **Tambah Data Kendaraan Baru**
                    </div>
                    <div class="card-body">
                        <form action="proses/proses_tambah-kendaraan.php" method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nomor_pendaftaran" class="form-label">Nomor Pendaftaran</label>
                                    <input type="text" class="form-control fw-bold bg-light" id="nomor_pendaftaran"
                                        name="nomor_pendaftaran" value="<?php echo $nomor_pendaftaran_auto; ?>"
                                        readonly>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="tanggal_pendaftaran" class="form-label">Tgl. Pendaftaran <span
                                            class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="tanggal_pendaftaran"
                                        name="tanggal_pendaftaran" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="plat_nomor" class="form-label">Plat Nomor <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="plat_nomor" name="plat_nomor" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="nama_pemilik" class="form-label">Nama Pemilik Kendaraan</label>
                                    <input type="text" class="form-control" id="nama_pemilik"
                                        name="nama_pemilik_kendaraan" placeholder="Boleh dikosongkan">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="merk" class="form-label">Merk</label>
                                    <input type="text" class="form-control" id="merk" name="merk"
                                        placeholder="Boleh dikosongkan">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="tipe" class="form-label">Tipe</label>
                                    <input type="text" class="form-control" id="tipe" name="tipe"
                                        placeholder="Boleh dikosongkan">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="jenis_kendaraan" class="form-label">Jenis Kendaraan</label>
                                    <input type="text" class="form-control" id="jenis_kendaraan" name="jenis_kendaraan"
                                        placeholder="Boleh dikosongkan">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="id_petugas" class="form-label">Petugas Lapangan <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="id_petugas" name="id_petugas" required>
                                    <option value="">Pilih Petugas</option>
                                    <?php 
                                    foreach ($list_petugas as $petugas) {
                                        echo "<option value='{$petugas['id_petugas']}'>{$petugas['nama_petugas']}</option>";
                                    }
                                 ?>
                                </select>
                                <small class="text-muted">Status Survey akan otomatis **Belum Survey** (nilai 0) setelah
                                    data disimpan.</small>
                            </div>
                            <button type="submit" class="btn btn-success"><i class="bi bi-save me-2"></i> Simpan
                                Pendaftaran</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card text-white bg-dark shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-truck-flatbed display-4 me-3"></i>
                            <div>
                                <div class="text-uppercase fw-bold">Total Kendaraan Terdaftar</div>
                                <div class="h3 mb-0"><?php echo number_format($total_kendaraan); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card text-white bg-info shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-calendar-week display-4 me-3"></i>
                            <div>
                                <div class="text-uppercase fw-bold">Pendaftaran Minggu Ini</div>
                                <div class="h3 mb-0"><?php echo number_format($kendaraan_minggu_ini); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card text-white bg-warning shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-calendar-event display-4 me-3"></i>
                            <div>
                                <div class="text-uppercase fw-bold">Pendaftaran Bulan Ini</div>
                                <div class="h3 mb-0"><?php echo number_format($kendaraan_bulan_ini); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card text-white bg-success shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-star-fill display-4 me-3"></i>
                            <div>
                                <div class="text-uppercase fw-bold">Rata-rata Rating</div>
                                <div class="h3 mb-0"><?php echo number_format($avg_rating, 2); ?> / 5</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-secondary text-white">
                        <i class="bi bi-graph-up me-2"></i> **Tren Perkembangan Rating Kepuasan Pelanggan**
                    </div>
                    <div class="card-body">
                        <canvas id="ratingChart"></canvas>
                        <div class="alert alert-info mt-3" role="alert">
                            **CATATAN PENGEMBANGAN:** Anda perlu mengambil dan memproses data rating dari tabel `survey`
                            (misalnya, rata-rata rating per minggu/bulan) untuk menggantikan nilai default pada variabel
                            `$chart_data_labels` dan `$chart_data_ratings` di bagian PHP.
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
    // =======================================================
    // 1. FUNGSI UTAMA UNTUK MENAMPILKAN DAN MENGHILANGKAN ALERT
    // =======================================================
    function displayAlert(status, message) {
        const container = document.getElementById('alert-container');
        // Jika container tidak ada, hentikan fungsi di sini
        if (!container) {
            console.error("ALERT ERROR: Elemen #alert-container tidak ditemukan.");
            // Untuk memastikan pesan tersampaikan, gunakan alert bawaan browser:
            alert(status.toUpperCase() + ": " + message);
            return;
        }

        const alertType = status === 'sukses' ? 'alert-success' : 'alert-danger';

        // Hapus konten lama
        container.innerHTML = '';

        // Buat HTML alert
        const alertHtml = `
        <div class="alert ${alertType} fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;

        // Sisipkan HTML
        container.innerHTML = alertHtml;

        // Hapus alert setelah 5 detik (menggunakan DOM murni)
        setTimeout(() => {
            const currentAlert = container.querySelector('.alert');
            if (currentAlert) {
                // Hilangkan alert dengan menghapus elemennya
                currentAlert.remove();
            }
        }, 5000); // Alert akan hilang setelah 5 detik
    }

    // =======================================================
    // 2. FUNGSI UNTUK MEMPERBARUI FORM (Termasuk Nomor Pendaftaran Baru)
    // =======================================================
    function updateFormSection() {
        const formContainer = document.querySelector('.col-md-8');
        if (!formContainer) return;

        const scrollPosition = window.scrollY;

        // Fetch index.php untuk mengambil HTML baru
        fetch('index.php')
            .then(response => {
                if (!response.ok) throw new Error('Gagal mengambil konten halaman.');
                return response.text();
            })
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newFormContainer = doc.querySelector('.col-md-8');

                if (newFormContainer) {
                    formContainer.innerHTML = newFormContainer.innerHTML;
                }

                window.scrollTo(0, scrollPosition);

                // PASANG KEMBALI LISTENER DI FORM BARU
                attachFormSubmitListener();

            })
            .catch(error => {
                console.error('Error saat memuat ulang form:', error);
                displayAlert('error', 'Gagal memuat ulang form pendaftaran otomatis.');
            });
    }


    // =======================================================
    // 3. FUNGSI PENGIRIMAN FORM MENGGUNAKAN AJAX (Fetch API)
    // =======================================================
    function handleFormSubmit(event) {
        // HANYA UNTUK DEBUGGING: HARUS MUNCUL DI CONSOLE JIKA FUNGSI BERJALAN
        console.log("--- Form Submit Handler Dipanggil! ---");

        event.preventDefault();

        const form = event.target;
        const submitButton = form.querySelector('button[type="submit"]');

        // Pengecekan agar TypeError: Cannot set properties of null tidak terjadi
        if (!submitButton) {
            console.error("Kesalahan Fatal: Tombol submit tidak ditemukan.");
            displayAlert('error', 'Kesalahan Internal: Tombol Simpan tidak terdeteksi.');
            return;
        }

        const formData = new FormData(form);
        const alertContainer = document.getElementById('alert-container');
        if (alertContainer) {
            alertContainer.innerHTML = '';
        }

        // Tampilkan status loading
        submitButton.disabled = true;
        submitButton.innerHTML =
            '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Memproses...';

        fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // Cek apakah response OK, jika tidak, kita masih coba parse JSON
                if (!response.ok) {
                    console.warn(`Response HTTP: ${response.status}. Akan mencoba parse JSON.`);
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'sukses') {
                    // PASTIKAN BARIS INI TEPAT
                    displayAlert('sukses', data.pesan);

                    form.reset();
                    updateFormSection();

                } else {
                    // Dan baris ini untuk error
                    displayAlert('error', data.pesan);
                }
            })
            .catch(error => {
                displayAlert('error',
                    'Terjadi kesalahan jaringan atau server tidak merespons. Pastikan file proses_tambah-kendaraan.php mengembalikan JSON yang valid.'
                );
                console.error('AJAX/JSON Parsing Error:', error);
            })
            .finally(() => {
                // Pastikan tombol diaktifkan kembali
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="bi bi-save me-2"></i> Simpan Pendaftaran';
            });
    }

    // =======================================================
    // 4. CHART.JS IMPLEMENTATION (tetap sama)
    // =======================================================
    const ctx = document.getElementById('ratingChart');
    // ... (kode chart.js Anda di sini, biarkan tetap sama) ...
    // Data PHP yang sudah di-JSON-encode 
    const labels = <?php echo $chart_data_json_labels; ?>;
    const data_ratings = <?php echo $chart_data_json_ratings; ?>;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Rata-rata Rating',
                data: data_ratings,
                borderColor: 'rgba(75, 192, 192, 1)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderWidth: 2,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 5,
                    title: {
                        display: true,
                        text: 'Rating (1-5)'
                    }
                }
            }
        }
    });


    // =======================================================
    // 5. ATTACH EVENT LISTENER
    // =======================================================
    function attachFormSubmitListener() {
        const formElement = document.querySelector('form[action="proses/proses_tambah-kendaraan.php"]');
        if (formElement) {
            // Hapus listener lama dan pasang yang baru
            formElement.removeEventListener('submit', handleFormSubmit);
            formElement.addEventListener('submit', handleFormSubmit);
        } else {
            console.warn("Form dengan action 'proses/proses_tambah-kendaraan.php' belum ditemukan di DOM.");
        }
    }

    // Panggil saat DOM siap
    document.addEventListener('DOMContentLoaded', attachFormSubmitListener);
    </script>

</body>

</html>