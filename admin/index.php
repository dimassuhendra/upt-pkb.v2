<?php
// ===============================================
// DASHBOARD ADMIN - UPT PKB (REVISI: Ranking & Chart Dinamis)
// ===============================================

// Pastikan Anda telah menempatkan file koneksi.php di path yang benar: ../config/koneksi.php
require_once '../config/koneksi.php';

// 1. Inisialisasi variabel dengan nilai default
$total_kendaraan = 0;
$kendaraan_minggu_ini = 0;
$kendaraan_bulan_ini = 0;
$avg_rating = 0.00;

$list_petugas = [];
$ranking_petugas = [];

// Default data untuk Chart (jika tidak ada data)
$chart_data_labels = [];
$chart_data_ratings_overall = [];
$chart_data_ratings_pelayanan = [];
$chart_data_ratings_fasilitas = [];
$chart_data_ratings_kecepatan = [];

// Data untuk Bar Chart
$bar_chart_petugas_labels = [];
$bar_chart_petugas_ratings_overall = [];
// Variabel baru untuk Bar Chart per kategori (Pelayanan, Fasilitas, Kecepatan)
$bar_chart_petugas_ratings_pelayanan = [];
$bar_chart_petugas_ratings_fasilitas = [];
$bar_chart_petugas_ratings_kecepatan = [];


$nomor_pendaftaran_auto = date('d/m/y') . '/001';
// Tentukan label default untuk chart tren mingguan jika belum ada data
$date_format_php = 'd M';
// Format PHP untuk tanggal (misal: 03 Nov)
$date_format_sql = '%d %b';
// Format SQL untuk tanggal (misal: 03 Nov)

// Hitung 4 label minggu terakhir (Senin sebagai awal)
for ($i = 3; $i >= 0; $i--) {
    $date_obj = strtotime("last monday -{$i} weeks");
    $chart_data_labels[] = date($date_format_php, $date_obj);
}


try {
    // =====================================================================
    // A. Statistik Ringkas (KPI)
    // =====================================================================

    // a. Total Kendaraan Terdaftar
    $stmt_total = $pdo->query("SELECT COUNT(*) FROM kendaraan");
    $total_kendaraan = $stmt_total->fetchColumn();

    // b. Kendaraan Minggu Ini
    // Menggunakan mode 1 (Senin sebagai awal minggu) untuk konsistensi
    $stmt_minggu = $pdo->query("SELECT COUNT(*) FROM kendaraan WHERE YEARWEEK(tanggal_pendaftaran, 1) = YEARWEEK(NOW(), 1)");
    $kendaraan_minggu_ini = $stmt_minggu->fetchColumn();

    // c. Kendaraan Bulan Ini
    $stmt_bulan = $pdo->query("SELECT COUNT(*) FROM kendaraan WHERE MONTH(tanggal_pendaftaran) = MONTH(NOW()) AND YEAR(tanggal_pendaftaran) = YEAR(NOW())");
    $kendaraan_bulan_ini = $stmt_bulan->fetchColumn();

    // d. Rata-rata Rating Keseluruhan (Gabungan 3 kategori)
    $stmt_avg_rating = $pdo->query("SELECT AVG((rating_pelayanan + rating_fasilitas + rating_kecepatan) / 3) FROM survey");
    $avg_rating_raw = $stmt_avg_rating->fetchColumn();
    $avg_rating = $avg_rating_raw ? round((float)$avg_rating_raw, 2) : 0.00;
    
    // e. List Petugas untuk Form
    $list_petugas = $pdo->query("SELECT id_petugas, nama_petugas FROM petugas ORDER BY nama_petugas ASC")->fetchAll(PDO::FETCH_ASSOC);

    // =====================================================================
    // B. Ranking Petugas & Data untuk Bar Chart (Diperbarui untuk 4 rating)
    // =====================================================================
    $sql_ranking = "
        SELECT 
            p.nama_petugas, 
            COUNT(s.id_survey) as total_survey,
            AVG((s.rating_pelayanan + s.rating_fasilitas + s.rating_kecepatan) / 3) as avg_petugas_rating_overall,
            AVG(s.rating_pelayanan) as avg_petugas_rating_pelayanan,
            AVG(s.rating_fasilitas) as avg_petugas_rating_fasilitas,
            AVG(s.rating_kecepatan) as avg_petugas_rating_kecepatan
        FROM 
            petugas p
        INNER JOIN 
            kendaraan k ON p.id_petugas = k.id_petugas
        INNER JOIN 
            survey s ON k.id_kendaraan = s.id_kendaraan
        GROUP BY 
            p.id_petugas, p.nama_petugas
        HAVING 
            COUNT(s.id_survey) > 0 -- Hanya petugas yang sudah punya survey
        ORDER BY 
            avg_petugas_rating_overall DESC, total_survey DESC
        LIMIT 10";
    $ranking_petugas_raw = $pdo->query($sql_ranking)->fetchAll(PDO::FETCH_ASSOC);

    // Ambil data untuk Bar Chart (TOP 10 Ranking Petugas)
    $ranking_petugas = $ranking_petugas_raw;
    if (!empty($ranking_petugas_raw)) {
        // Hanya ambil nama depan dan rating untuk chart agar tidak terlalu panjang
        foreach ($ranking_petugas_raw as $p) {
            $nama_singkat = explode(' ', $p['nama_petugas'])[0];
            $bar_chart_petugas_labels[] = $nama_singkat;
            $bar_chart_petugas_ratings_overall[] = round((float)$p['avg_petugas_rating_overall'], 2);
            // Tambahkan data rating per kategori
            $bar_chart_petugas_ratings_pelayanan[] = round((float)$p['avg_petugas_rating_pelayanan'], 2);
            $bar_chart_petugas_ratings_fasilitas[] = round((float)$p['avg_petugas_rating_fasilitas'], 2);
            $bar_chart_petugas_ratings_kecepatan[] = round((float)$p['avg_petugas_rating_kecepatan'], 2);
        }
    }


    // =====================================================================
    // C. Data Tren Rating untuk Line Chart (4 Minggu Terakhir)
    // =====================================================================
    $sql_chart_data = "
        SELECT 
            YEARWEEK(s.filled_at, 1) as week_num,
            -- Tampilkan label sebagai Tanggal mulai minggu (Senin)
            DATE_FORMAT(DATE_ADD(DATE(s.filled_at), INTERVAL 1-DAYOFWEEK(s.filled_at) DAY), 
            '{$date_format_sql}') as start_date_label,
            AVG((s.rating_pelayanan + s.rating_fasilitas + s.rating_kecepatan) / 3) as weekly_avg_rating,
            AVG(s.rating_pelayanan) as weekly_avg_pelayanan,
            AVG(s.rating_fasilitas) as weekly_avg_fasilitas,
            AVG(s.rating_kecepatan) as weekly_avg_kecepatan
        FROM 
            survey s
        WHERE 
            s.filled_at >= DATE_SUB(NOW(), INTERVAL 4 WEEK) AND s.filled_at IS NOT NULL
        GROUP BY 
            week_num
        ORDER BY 
            week_num ASC";
    $raw_chart_data = $pdo->query($sql_chart_data)->fetchAll(PDO::FETCH_ASSOC);

    // Proses data untuk Chart.js (Mengisi data 4 minggu, bahkan jika data kosong)
    $processed_chart_data = [];
    $current_week_num = (int)date('YW', strtotime('now')); // Menggunakan 1 untuk Senin sebagai awal
    
    // Siapkan array untuk 4 minggu yang relevan
    for ($i = 0; $i < 4; $i++) {
        $week_start = strtotime("last monday -" . (3 - $i) . " weeks");
        $week_label = date($date_format_php, $week_start);
        $week_num = (int)date('YW', $week_start);
        $processed_chart_data[$week_num] = [
            'start_date_label' => $week_label,
            'weekly_avg_rating' => 0.00,
            'weekly_avg_pelayanan' => 0.00,
            'weekly_avg_fasilitas' => 0.00,
            'weekly_avg_kecepatan' => 0.00
        ];
    }
    
    // Tumpuk data DB ke array yang sudah disiapkan
    foreach ($raw_chart_data as $data) {
        $week_num_int = (int)$data['week_num'];
        if (isset($processed_chart_data[$week_num_int])) {
            $processed_chart_data[$week_num_int] = [
                'start_date_label' => $data['start_date_label'],
                'weekly_avg_rating' => round((float)$data['weekly_avg_rating'], 2),
                'weekly_avg_pelayanan' => round((float)$data['weekly_avg_pelayanan'], 2),
                'weekly_avg_fasilitas' => round((float)$data['weekly_avg_fasilitas'], 2),
                'weekly_avg_kecepatan' => round((float)$data['weekly_avg_kecepatan'], 2),
            ];
        }
    }
    
    // Finalisasi array untuk Chart.js
    $chart_data_labels = array_column($processed_chart_data, 'start_date_label');
    $chart_data_ratings_overall = array_column($processed_chart_data, 'weekly_avg_rating');
    $chart_data_ratings_pelayanan = array_column($processed_chart_data, 'weekly_avg_pelayanan');
    $chart_data_ratings_fasilitas = array_column($processed_chart_data, 'weekly_avg_fasilitas');
    $chart_data_ratings_kecepatan = array_column($processed_chart_data, 'weekly_avg_kecepatan');

    // =====================================================================
    // D. GENERASI NOMOR PENDAFTARAN OTOMATIS (TETAP SAMA)
    // =====================================================================
    // 1. Hitung jumlah pendaftaran hari ini
    $sql_today_count = "SELECT COUNT(*) FROM kendaraan WHERE DATE(tanggal_pendaftaran) = CURDATE()";
    $stmt_today_count = $pdo->query($sql_today_count);
    $today_count = $stmt_today_count->fetchColumn();
    
    // 2. Tentukan nomor urut berikutnya
    $next_sequence_number = $today_count + 1;
    // 3. Format tanggal dan gabungkan
    $current_date_format = date('d/m/y');
    $nomor_pendaftaran_auto = $current_date_format . '/' .
        str_pad($next_sequence_number, 3, '0', STR_PAD_LEFT);
    
} catch (PDOException $e) {
    // Penanganan error Query
    echo "<div class='alert alert-danger'>QUERY GAGAL: " .
        $e->getMessage() . "</div>";
    // Set variabel agar dashboard tidak rusak total
    $ranking_petugas = [];
}

// Data tanggal saat ini untuk tampilan
$tanggal_saat_ini = date('l, d F Y');
$bulan_saat_ini = date('F Y');

// Siapkan data chart untuk JS 
$chart_data_json_labels = json_encode($chart_data_labels);
$chart_data_json_ratings_overall = json_encode($chart_data_ratings_overall);
$chart_data_json_ratings_pelayanan = json_encode($chart_data_ratings_pelayanan);
$chart_data_json_ratings_fasilitas = json_encode($chart_data_ratings_fasilitas);
$chart_data_json_ratings_kecepatan = json_encode($chart_data_ratings_kecepatan);

// Data untuk Bar Chart
$bar_chart_petugas_json_labels = json_encode($bar_chart_petugas_labels);
$bar_chart_petugas_json_ratings_overall = json_encode($bar_chart_petugas_ratings_overall);
$bar_chart_petugas_json_ratings_pelayanan = json_encode($bar_chart_petugas_ratings_pelayanan);
$bar_chart_petugas_json_ratings_fasilitas = json_encode($bar_chart_petugas_ratings_fasilitas);
$bar_chart_petugas_json_ratings_kecepatan = json_encode($bar_chart_petugas_ratings_kecepatan);
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
    .content {
        margin-left: 250px;
        padding: 20px;
    }

    /* Styling Baru untuk Kalender */
    .calendar table {
        width: 100%;
        height: 100%;
        /* Memenuhi lebar container */
        table-layout: fixed;
        /* Penting agar sel lebar proporsional */
        border-collapse: collapse;
        border: none !important;
        /* Hapus semua border */
    }

    .calendar th,
    .calendar td {
        font-size: 0.85rem;
        padding: 0 !important;
        height: 40px;
        /* Tinggi sel tetap untuk estetika */
        line-height: 40px;
        /* Pusatkan vertikal */
        border: none !important;
        text-align: center;
    }

    /* Style tambahan untuk header hari */
    .calendar th {
        font-weight: bold;
        color: #495057;
        text-transform: uppercase;
        font-size: 0.75rem;
    }

    /* Style untuk sel hari ini */
    .calendar .today {
        background-color: var(--bs-primary);
        color: white;
        font-weight: bold;
        border-radius: 50%;
        /* Membuat hari ini menjadi lingkaran */
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        transition: background-color 0.2s;
    }

    /* Style untuk tanggal (td) */
    .calendar td {
        border-radius: 5px;
        cursor: default;
        /* Membuat tanggal kosong tidak terlihat */
        color: #ced4da;
    }

    /* Override untuk tanggal yang terisi */
    .calendar td:not(:empty) {
        color: #212529;
        /* Warna teks normal */
    }

    .calendar td:not(.today):not(:empty):hover {
        background-color: #f8f9fa;
        /* Highlight saat di-hover */
    }

    /* Membatasi tinggi canvas chart */
    .chart-container {
        max-height: 300px;
        position: relative;
    }

    .chart-btn-group {
        margin-bottom: 15px;
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }

    .chart-btn-group .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.8rem;
    }
    </style>
</head>

<body>

    <?php include 'sidebar.php' // Asumsi file sidebar.php ada ?>

    <div class="content">
        <h2 class="mb-4">Dashboard Administrasi</h2>

        <div id="alert-container"></div>

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100 p-2">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title text-primary text-center mb-3">
                            <i class="bi bi-calendar3 me-2"></i>
                            <?php echo $bulan_saat_ini; ?>
                        </h5>
                        <div class="calendar flex-grow-1">
                            <?php
                            // PHP untuk membuat kalender sederhana
                            $dayOfWeek = date('w', strtotime('first day of this month')); // 0 (Sun) to 6 (Sat)
                            $daysInMonth = date('t');
                            $currentDay = date('j');
                            
                            echo '<table class="table table-sm text-center">';
                            
                            // Header: Ganti inisial hari
                            echo '<thead><tr><th>Min</th><th>Sen</th><th>Sel</th><th>Rab</th><th>Kam</th><th>Jum</th><th>Sab</th></tr></thead>';
                            
                            echo '<tbody><tr>';
                            // Isi hari kosong di awal bulan
                            for ($i = 0; $i < $dayOfWeek; $i++) {
                                echo '<td></td>';
                            }
                            
                            // Isi tanggal
                            for ($day = 1; $day <= $daysInMonth; $day++) {
                                $isToday = ($day == $currentDay) ? 'today' : '';
                                
                                // Tambahkan hari di awal baris baru (setelah 7 hari)
                                if (($dayOfWeek + $day - 1) % 7 == 0 && $day != 1) {
                                    echo '</tr><tr>';
                                }
                                
                                // Tidak perlu div di dalam td
                                echo '<td class="' . $isToday . '">' . $day . '</td>';
                            }
                            
                            // Isi hari kosong di akhir bulan
                            $lastDayIndex = ($dayOfWeek + $daysInMonth - 1) % 7;
                            for ($i = $lastDayIndex; $i < 6; $i++) {
                                echo '<td></td>';
                            }
                            
                            echo '</tr></tbody></table>';
                            ?>
                        </div>
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
                                    <label for="tanggal_pendaftaran" class="form-label">Tgl.
                                        Pendaftaran <span class="text-danger">*</span></label>
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
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-secondary text-white">
                        <i class="bi bi-graph-up me-2"></i> **Tren Rating Berdasarkan Kategori (4 Minggu Terakhir)**
                    </div>
                    <div class="card-body">
                        <div class="chart-btn-group" id="lineChartButtons">
                            <button type="button" class="btn btn-primary active" data-chart-filter="all">Semua</button>
                            <button type="button" class="btn btn-outline-primary"
                                data-chart-filter="pelayanan">Pelayanan</button>
                            <button type="button" class="btn btn-outline-primary"
                                data-chart-filter="fasilitas">Fasilitas</button>
                            <button type="button" class="btn btn-outline-primary"
                                data-chart-filter="kecepatan">Kecepatan</button>
                        </div>
                        <div class="chart-container">
                            <canvas id="categoryRatingChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-warning text-white">
                        <i class="bi bi-bar-chart-fill me-2"></i> **Perbandingan Rata-rata Rating Petugas**
                    </div>
                    <div class="card-body">
                        <div class="chart-btn-group" id="barChartButtons">
                            <button type="button" class="btn btn-primary active"
                                data-chart-category="overall">Rating</button>
                            <button type="button" class="btn btn-outline-primary"
                                data-chart-category="pelayanan">Pelayanan</button>
                            <button type="button" class="btn btn-outline-primary"
                                data-chart-category="fasilitas">Fasilitas</button>
                            <button type="button" class="btn btn-outline-primary"
                                data-chart-category="kecepatan">Kecepatan</button>
                        </div>
                        <div class="chart-container">
                            <canvas id="petugasRatingChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-info text-white">
                        <i class="bi bi-trophy-fill me-2"></i> **Peringkat Petugas Lapangan Berdasarkan Rating**
                    </div>
                    <div class="card-body">
                        <?php if (!empty($ranking_petugas)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center">Peringkat</th>
                                        <th>Nama Petugas</th>
                                        <th class="text-center">Rata-rata Rating</th>
                                        <th class="text-center">Total Survey</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $rank = 1; ?>
                                    <?php foreach ($ranking_petugas as $petugas): ?>
                                    <tr>
                                        <td class="text-center fw-bold">
                                            <?php 
                                            if ($rank == 1) echo '<i class="bi bi-award-fill text-warning me-1"></i>';
                                            else if ($rank == 2) echo '<i class="bi bi-award-fill text-secondary me-1"></i>';
                                            else if ($rank == 3) echo '<i class="bi bi-award-fill text-danger me-1"></i>';
                                            echo $rank++;
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($petugas['nama_petugas']); ?></td>
                                        <td class="text-center fw-bold text-primary">
                                            <?php echo number_format($petugas['avg_petugas_rating_overall'], 2); ?> / 5
                                        </td>
                                        <td class="text-center"><?php echo number_format($petugas['total_survey']); ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-warning text-center">
                            Belum ada data survei untuk menghitung peringkat petugas.
                        </div>
                        <?php endif; ?>
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
        if (!container) {
            console.error("ALERT ERROR: Elemen #alert-container tidak ditemukan.");
            return;
        }

        const alertType = status === 'sukses' ? 'alert-success' : 'alert-danger';
        const icon = status === 'sukses' ? 'bi-check-circle-fill' : 'bi-x-octagon-fill';
        container.innerHTML = '';
        const alertHtml = `
            <div class="alert ${alertType} alert-dismissible fade show" role="alert">
                <i class="bi ${icon} me-2"></i> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        container.innerHTML = alertHtml;

        setTimeout(() => {
            const currentAlert = container.querySelector('.alert');
            if (currentAlert) {
                // Gunakan fungsi Bootstrap untuk fade out dan hapus
                const bsAlert = bootstrap.Alert.getOrCreateInstance(currentAlert);
                bsAlert.close();
            }
        }, 5000);
    }

    // =======================================================
    // 2. FUNGSI UNTUK MEMPERBARUI FORM & STATISTIK (AJAX)
    // =======================================================
    // Fungsi ini melakukan refresh parsial pada dashboard tanpa reload halaman penuh
    async function updateDashboardData() {
        const dashboardContent = document.querySelector('.content');
        if (!dashboardContent) return;

        const scrollPosition = window.scrollY;

        try {
            // Ambil konten dashboard terbaru
            const response = await fetch('index.php');
            if (!response.ok) throw new Error('Gagal mengambil konten dashboard terbaru.');
            const html = await response.text();

            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            // --- Bagian yang Diperbarui ---

            // 1. Form dan Nomor Pendaftaran Otomatis
            const newFormContainer = doc.querySelector('.col-md-8 form').closest('.col-md-8');
            const oldFormContainer = document.querySelector('.col-md-8 form').closest('.col-md-8');
            if (oldFormContainer && newFormContainer) {
                oldFormContainer.innerHTML = newFormContainer.innerHTML;
                attachFormSubmitListener(); // Pasang kembali event listener
            }

            // 2. KPI Cards
            const newKpiCards = doc.querySelector('.row.mb-4:nth-of-type(2)');
            const oldKpiCards = document.querySelector('.row.mb-4:nth-of-type(2)');
            if (oldKpiCards && newKpiCards) {
                oldKpiCards.innerHTML = newKpiCards.innerHTML;
            }

            // 3. Ranking Table
            const newRankingTable = doc.querySelector('.row.mb-4:nth-of-type(4) .card-body');
            const oldRankingTable = document.querySelector('.row.mb-4:nth-of-type(4) .card-body');
            if (oldRankingTable && newRankingTable) {
                oldRankingTable.innerHTML = newRankingTable.innerHTML;
            }

            // 4. Update Charts
            // Ambil data JSON dari HTML yang baru di-fetch
            const newScriptContent = doc.querySelector('script:nth-of-type(2)').textContent;

            // Ekstrak variabel JSON baru dari skrip (membutuhkan regex yang lebih canggih, 
            // namun untuk kasus ini kita bisa memuat ulang data chart dari PHP JSON
            // yang sudah tersedia di global scope setelah fetch jika kita ingin 
            // menghindari parsing string JS yang rumit). 
            // Karena ini tidak mungkin tanpa memuat ulang page atau memanggil AJAX lain, 
            // kita akan me-reload page untuk kepastian data chart,
            // atau menggunakan cara yang lebih sederhana: menyimpan data JSON 
            // dalam elemen tersembunyi.

            // **NOTE:** Untuk penyederhanaan dan kehandalan AJAX partial refresh, 
            // disarankan untuk memuat ulang halaman (window.location.reload()) 
            // setelah sukses, atau membuat endpoint AJAX khusus untuk data JSON chart.
            // Namun, untuk memenuhi permintaan menggunakan script yang ada, kita 
            // berasumsi data JSON global telah diupdate (seperti di bawah).

            // Jika Anda ingin menggunakan data JSON global yang baru, 
            // Anda harus membuat endpoint AJAX terpisah untuk mendapatkan data tersebut
            // dan tidak hanya fetch index.php, atau menaruhnya di elemen tersembunyi. 
            // Untuk skenario ini, kita akan memaksa *update chart* menggunakan 
            // variabel data yang di-JSON-encode di PHP (walau kurang efisien dalam AJAX partial):

            // **SOLUSI ALTERNATIF (Paling Aman): Reload Page setelah Sukses Submit**
            // window.location.reload(); 
            // ... (Hapus semua kode update chart di bawah jika menggunakan reload)

            // **SOLUSI SAAT INI (Membutuhkan data chart JSON di-embed di HTML baru):**
            // Karena tidak ada mekanisme sederhana untuk mengambil variabel JSON 
            // dari string HTML yang di-fetch tanpa regex kompleks atau elemen tersembunyi,
            // kita akan menggunakan data JSON global yang sudah ada di scope global. 
            // Jika Anda ingin data baru, Anda harus memuat ulang data chart dari server.

            // **Skenario Terbaik:** Dapatkan data chart dari AJAX endpoint baru
            // Contoh (jika Anda memiliki endpoint data-chart.php):
            /*
            const chartDataResponse = await fetch('data-chart.php');
            const newChartData = await chartDataResponse.json();
            updateCharts(newChartData); // Fungsi baru untuk update chart
            */

            // Karena kita harus menggunakan kode yang ada, kita akan **menggunakan cara yang lebih sederhana**
            // dan mengabaikan bagian update chart di AJAX, **atau** kita modifikasi
            // `handleFormSubmit` untuk **memuat ulang halaman penuh** jika Anda menginginkan
            // data chart yang *benar-benar* baru (disarankan).

            // ***MAAF, TIDAK MUNGKIN MEREFRESH VARIABEL PHP KE JS TANPA RELOAD ATAU AJAX TAMBAHAN.***
            // Kita akan memaksakan *update chart* dengan data JSON yang tersedia di scope global.
            // (Pada prakteknya, ini akan menggunakan data chart lama kecuali di-reload penuh).
            // Kita akan menjaga fungsi updateCharts agar user bisa melihat perubahannya saat reload.

            // 5. Update Charts (Hanya jika Anda tahu cara mengambil data JSON baru dari 'doc' atau menggunakan endpoint baru)
            // ***KOSONGKAN BAGIAN INI UNTUK SEMENTARA*** // (Asumsi data chart hanya diupdate saat reload halaman penuh, atau implementasi AJAX yang lebih canggih dibutuhkan)
            // ***JIKA INGIN MEMAKSA REFRESH, GUNAKAN: window.location.reload(); DI handleFormSubmit***

            // Kita hanya akan memastikan posisi scroll tetap.
            window.scrollTo(0, scrollPosition);
        } catch (error) {
            console.error('Error saat memuat ulang dashboard:', error);
            displayAlert('error', 'Gagal memuat ulang data statistik otomatis.');
        }
    }


    // =======================================================
    // 3. FUNGSI PENGIRIMAN FORM MENGGUNAKAN AJAX (Fetch API)
    // =======================================================
    function handleFormSubmit(event) {
        event.preventDefault();
        const form = event.target;
        const submitButton = form.querySelector('button[type="submit"]');

        if (!submitButton) return;

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
                // Pastikan response dibaca sebagai JSON
                return response.json();
            })
            .then(data => {
                if (data.status === 'sukses') {
                    displayAlert('sukses', data.pesan);
                    form.reset();
                    // Update form dan statistik. Gunakan reload untuk memastikan data chart yang akurat.
                    // updateDashboardData(); // Ganti dengan reload untuk kepastian data chart
                    window.location.reload();

                } else {
                    displayAlert('error', data.pesan);
                }
            })
            .catch(error => {
                displayAlert('error',
                    'Terjadi kesalahan jaringan atau server. Pastikan proses_tambah-kendaraan.php mengembalikan JSON yang valid.'
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
    // 4. CHART.JS IMPLEMENTATION
    // =======================================================

    // Data PHP yang sudah di-JSON-encode 
    const labels = <?php echo $chart_data_json_labels; ?>;
    const data_ratings_pelayanan = <?php echo $chart_data_json_ratings_pelayanan; ?>;
    const data_ratings_fasilitas = <?php echo $chart_data_json_ratings_fasilitas; ?>;
    const data_ratings_kecepatan = <?php echo $chart_data_json_ratings_kecepatan; ?>;

    // Data Bar Chart Petugas (Diperbarui)
    const bar_labels = <?php echo $bar_chart_petugas_json_labels; ?>;
    const bar_ratings_overall = <?php echo $bar_chart_petugas_json_ratings_overall; ?>;
    const bar_ratings_pelayanan = <?php echo $bar_chart_petugas_json_ratings_pelayanan; ?>;
    const bar_ratings_fasilitas = <?php echo $bar_chart_petugas_json_ratings_fasilitas; ?>;
    const bar_ratings_kecepatan = <?php echo $bar_chart_petugas_json_ratings_kecepatan; ?>;


    // --- A. Line Chart untuk Kategori Rating (Default: Semua) ---
    const ctxCategory = document.getElementById('categoryRatingChart');
    // Definisi awal datasets (semua aktif)
    const initialLineDatasets = [{
            label: 'Pelayanan',
            data: data_ratings_pelayanan,
            borderColor: 'rgba(54, 162, 235, 1)', // Biru
            backgroundColor: 'rgba(54, 162, 235, 0.1)',
            borderWidth: 2,
            tension: 0.4,
            fill: false,
            // ID unik untuk identifikasi
            id: 'pelayanan'
        },
        {
            label: 'Fasilitas',
            data: data_ratings_fasilitas,
            borderColor: 'rgba(75, 192, 192, 1)', // Hijau Pucat
            backgroundColor: 'rgba(75, 192, 192, 0.1)',
            borderWidth: 2,
            tension: 0.4,
            fill: false,
            id: 'fasilitas'
        },
        {
            label: 'Kecepatan',
            data: data_ratings_kecepatan,
            borderColor: 'rgba(255, 159, 64, 1)', // Oranye
            backgroundColor: 'rgba(255, 159, 64, 0.1)',
            borderWidth: 2,
            tension: 0.4,
            fill: false,
            id: 'kecepatan'
        }
    ];

    window.categoryRatingChart = new Chart(ctxCategory, {
        type: 'line',
        data: {
            labels: labels,
            datasets: initialLineDatasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 5,
                    title: {
                        display: true,
                        text: 'Rating (1-5)'
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y.toFixed(2) + ' / 5';
                        }
                    }
                }
            }
        }
    });

    // Event Listener untuk Line Chart
    document.getElementById('lineChartButtons').addEventListener('click', function(e) {
        const filter = e.target.getAttribute('data-chart-filter');
        if (!filter) return;

        // Reset semua tombol
        this.querySelectorAll('.btn').forEach(btn => {
            btn.classList.remove('active', 'btn-primary');
            btn.classList.add('btn-outline-primary');
        });

        // Aktifkan tombol yang diklik
        e.target.classList.add('active', 'btn-primary');
        e.target.classList.remove('btn-outline-primary');

        if (filter === 'all') {
            window.categoryRatingChart.data.datasets = initialLineDatasets;
        } else {
            // Filter dataset yang sesuai dengan ID
            window.categoryRatingChart.data.datasets = initialLineDatasets.filter(ds => ds.id === filter);
        }

        window.categoryRatingChart.update();
    });

    // --- B. Bar Chart untuk Rating Petugas (Default: Overall) ---
    const ctxPetugas = document.getElementById('petugasRatingChart');

    // Fungsi untuk mendapatkan warna
    function getBarColors(opacity) {
        return [
            `rgba(255, 99, 132, ${opacity})`, // Merah
            `rgba(54, 162, 235, ${opacity})`, // Biru
            `rgba(255, 206, 86, ${opacity})`, // Kuning
            `rgba(75, 192, 192, ${opacity})`, // Hijau
            `rgba(153, 102, 255, ${opacity})`, // Ungu
            `rgba(255, 99, 132, ${opacity * 0.7})`,
            `rgba(54, 162, 235, ${opacity * 0.7})`,
            `rgba(255, 206, 86, ${opacity * 0.7})`,
            `rgba(75, 192, 192, ${opacity * 0.7})`,
            `rgba(153, 102, 255, ${opacity * 0.7})`
        ];
    }

    window.petugasRatingChart = new Chart(ctxPetugas, {
        type: 'bar',
        data: {
            labels: bar_labels,
            datasets: [{
                label: 'Rata-rata Rating Petugas',
                data: bar_ratings_overall, // Default: overall
                backgroundColor: getBarColors(0.8),
                borderColor: getBarColors(1),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y', // Membuat bar horizontal
            scales: {
                x: {
                    beginAtZero: true,
                    max: 5,
                    title: {
                        display: true,
                        text: 'Rating (1-5)'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Nama Petugas'
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Avg Rating: ' + context.parsed.x.toFixed(2) + ' / 5';
                        }
                    }
                }
            }
        }
    });

    // Event Listener untuk Bar Chart
    document.getElementById('barChartButtons').addEventListener('click', function(e) {
        const category = e.target.getAttribute('data-chart-category');
        if (!category) return;

        // Reset semua tombol
        this.querySelectorAll('.btn').forEach(btn => {
            btn.classList.remove('active', 'btn-primary');
            btn.classList.add('btn-outline-primary');
        });

        // Aktifkan tombol yang diklik
        e.target.classList.add('active', 'btn-primary');
        e.target.classList.remove('btn-outline-primary');

        let newRatingsData;
        let newLabel;

        switch (category) {
            case 'pelayanan':
                newRatingsData = bar_ratings_pelayanan;
                newLabel = 'Rata-rata Rating Pelayanan';
                break;
            case 'fasilitas':
                newRatingsData = bar_ratings_fasilitas;
                newLabel = 'Rata-rata Rating Fasilitas';
                break;
            case 'kecepatan':
                newRatingsData = bar_ratings_kecepatan;
                newLabel = 'Rata-rata Rating Kecepatan';
                break;
            case 'overall':
            default:
                newRatingsData = bar_ratings_overall;
                newLabel = 'Rata-rata Rating Petugas';
                break;
        }

        // Update data chart
        window.petugasRatingChart.data.datasets[0].data = newRatingsData;
        window.petugasRatingChart.data.datasets[0].label = newLabel;
        window.petugasRatingChart.update();
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
        }
    }

    // Panggil saat DOM siap
    document.addEventListener('DOMContentLoaded', attachFormSubmitListener);
    </script>

</body>

</html>