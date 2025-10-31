<?php
// ===============================================
// PROTEKSI HALAMAN DAN FUNGSI PHP DASHBOARD
// ===============================================
session_start();

// Panggil file koneksi database (lokasi: ../config/koneksi.php)
require_once '../config/koneksi.php'; 

// Cek apakah pengguna sudah login dan perannya adalah 'admin'
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    header("location: ../index.php");
    exit;
}

$nama_admin = htmlspecialchars($_SESSION['nama_lengkap'] ?? $_SESSION['username']);

/**
 * Fungsi untuk mengambil jumlah data berdasarkan kondisi waktu.
 */
function getCountByPeriod($pdo, $tabel, $kolom_waktu, $periode = null, $filter_dates = null, $kondisi_tambahan = "") {
    
    // --- Logika Filter Tanggal Spesifik ---
    if (!empty($filter_dates)) {
        if (is_array($filter_dates)) {
            $dates_list = "'" . implode("','", array_map(function($date) {
                return date('Y-m-d', strtotime($date));
            }, $filter_dates)) . "'";
            $where_clause = "WHERE DATE({$kolom_waktu}) IN ({$dates_list})";
        } else {
            $date = date('Y-m-d', strtotime($filter_dates));
            $where_clause = "WHERE DATE({$kolom_waktu}) = '{$date}'";
        }
    // --- Logika Filter Periodik ---
    } elseif ($periode) {
        $timezone = new DateTimeZone('Asia/Jakarta');
        $now = new DateTime('now', $timezone);
        $where_clause = "";

        switch ($periode) {
            case 'today':
                $start = $now->format('Y-m-d 00:00:00');
                $end = $now->format('Y-m-d 23:59:59');
                $where_clause = "WHERE {$kolom_waktu} BETWEEN '{$start}' AND '{$end}'";
                break;
            case 'week':
                $start = $now->modify('this week')->format('Y-m-d 00:00:00');
                $end = $now->modify('+6 days')->format('Y-m-d 23:59:59');
                $where_clause = "WHERE {$kolom_waktu} BETWEEN '{$start}' AND '{$end}'";
                break;
            case 'month':
                $start = $now->format('Y-m-01 00:00:00');
                $end = $now->format('Y-m-t 23:59:59');
                $where_clause = "WHERE {$kolom_waktu} BETWEEN '{$start}' AND '{$end}'";
                break;
        }
    } else {
        $where_clause = "";
    }
    
    $sql = "SELECT COUNT(*) FROM {$tabel} {$where_clause} {$kondisi_tambahan}";
    
    try {
        $stmt = $pdo->query($sql);
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

// PENGATURAN FILTER KALENDER (Default: Null)
$filter_dates = null; 
// Jika Anda ingin mengimplementasikan filter AJAX, Anda akan memproses $_GET/$_POST di sini

// DATA RANGKUMAN DASHBOARD
$data_terdaftar = [
    'hari_ini' => getCountByPeriod($pdo, 'kendaraan', 'tanggal_pendaftaran', 'today', $filter_dates),
    'minggu_ini' => getCountByPeriod($pdo, 'kendaraan', 'tanggal_pendaftaran', 'week', $filter_dates),
    'bulan_ini' => getCountByPeriod($pdo, 'kendaraan', 'tanggal_pendaftaran', 'month', $filter_dates),
];

$data_lulus_uji = [
    'hari_ini' => getCountByPeriod($pdo, 'kendaraan', 'tanggal_uji', 'today', $filter_dates, "AND status_uji = 'Lulus'"),
    'minggu_ini' => getCountByPeriod($pdo, 'kendaraan', 'tanggal_uji', 'week', $filter_dates, "AND status_uji = 'Lulus'"),
    'bulan_ini' => getCountByPeriod($pdo, 'kendaraan', 'tanggal_uji', 'month', $filter_dates, "AND status_uji = 'Lulus'"),
];

// DATA RATING DAN CHART
$ratings_labels = ['pelayanan', 'fasilitas', 'kecepatan'];
$ratings_data = [];
$total_survei = getCountByPeriod($pdo, 'survei', 'tanggal_survei', 'month', $filter_dates); // Survei bulan ini

$timezone = new DateTimeZone('Asia/Jakarta');
$now = new DateTime('now', $timezone);
$start_month = $now->format('Y-m-01 00:00:00');
$end_month = $now->format('Y-m-t 23:59:59');

foreach ($ratings_labels as $label) {
    $sql_avg = "SELECT AVG(rating_{$label}) FROM survei WHERE tanggal_survei BETWEEN ? AND ?";
    try {
        $stmt = $pdo->prepare($sql_avg);
        $stmt->execute([$start_month, $end_month]);
        $avg_rating = round($stmt->fetchColumn() ?? 0, 2);
        $ratings_data[$label] = $avg_rating;
    } catch (PDOException $e) {
        $ratings_data[$label] = 0;
    }
}

$total_ratings_count = count($ratings_data);
$avg_all = ($total_ratings_count > 0) ? round(array_sum($ratings_data) / $total_ratings_count, 2) : 0;
$ratings_data['keseluruhan'] = $avg_all;

$chart_data = [
    'labels' => array_map('ucwords', array_keys($ratings_data)),
    'datasets' => [
        [
            'label' => 'Rata-rata Rating (Skala 5)',
            'data' => array_values($ratings_data),
            'backgroundColor' => ['#4CAF50', '#2196F3', '#FFC107', '#E91E63'],
            'borderColor' => 'rgba(0,0,0,0.1)',
            'borderWidth' => 1
        ]
    ]
];

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - UPT PKB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Ubuntu:wght@500;700&display=swap"
        rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>

    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales-all.min.js'></script>

    <style>
    body {
        font-family: 'Open Sans', sans-serif;
        background-color: #f8f9fa;
    }

    .sidebar {
        width: 280px;
        min-height: 100vh;
        background-color: #343a40;
        color: white;
        position: fixed;
        z-index: 1000;
        padding-top: 20px;
        font-family: 'Ubuntu', sans-serif;
        display: flex;
        flex-direction: column;
    }

    .sidebar .nav {
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding-bottom: 20px;
    }

    .sidebar .nav-item-list {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .sidebar .nav-link {
        color: rgba(255, 255, 255, 0.8);
        padding: 15px 20px;
        transition: background-color 0.2s;
    }

    .sidebar .nav-link:hover {
        background-color: #495057;
        color: white;
    }

    .sidebar .nav-link.active {
        background-color: #007bff;
        color: white;
        border-radius: 5px;
    }

    .main-content {
        margin-left: 280px;
        padding: 20px;
    }

    .card-summary {
        border-left: 5px solid;
    }

    .card-summary.primary {
        border-color: #007bff;
    }

    .card-summary.success {
        border-color: #28a745;
    }

    .card-summary.warning {
        border-color: #ffc107;
    }

    .card-summary.info {
        border-color: #17a2b8;
    }

    .header-admin {
        font-family: 'Ubuntu', sans-serif;
        font-weight: 700;
    }

    /* Kalender */
    #calendar {
        max-width: 50%;
        margin: 0 auto;
        background-color: white;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
    }

    .fc-toolbar-title {
        font-size: 1.25rem !important;
    }

    .fc-daygrid-day.fc-day-selected {
        background-color: #d0f0ff !important;
        border: 2px solid #007bff !important;
    }
    </style>
</head>

<body>

    <div class="sidebar">
        <div class="text-center pt-3 pb-3">
            <h4 class="text-center text-primary header-admin">UPT PKB DASHBOARD</h4>
            <small class="text-white-50">Administrator Mode</small>
        </div>

        <a href="tambah_kendaraan.php" class="btn btn-success btn-lg mx-3 mb-4 fw-bold">
            <i class="bi bi-plus-circle"></i> TAMBAH KENDARAAN
        </a>

        <nav class="nav flex-column">
            <div class="nav-item-list">
                <a class="nav-link active" href="index.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                <a class="nav-link" href="kelola_kendaraan.php"><i class="bi bi-truck"></i> Kelola Kendaraan</a>
                <a class="nav-link" href="kelola_petugas.php"><i class="bi bi-people"></i> Kelola Petugas & User</a>
                <a class="nav-link" href="laporan_survei.php"><i class="bi bi-bar-chart-line"></i> Laporan Survei</a>
                <a class="nav-link" href="master_data.php"><i class="bi bi-database"></i> Master Data</a>
            </div>

            <div class="nav-item-list">
                <hr class="mx-3 text-white-50">
                <a class="nav-link" href="../proses/logout.php"><i class="bi bi-box-arrow-right"></i> Logout
                    (<?php echo $nama_admin; ?>)</a>
            </div>
        </nav>
    </div>

    <div class="main-content">
        <header class="mb-4">
            <h1 class="header-admin text-secondary">Dashboard Evaluasi Layanan</h1>
            <p class="text-muted">Ringkasan cepat performa layanan dan data kendaraan UPT PKB.</p>
        </header>

        <div class="row mb-5">
            <div class="col-12">
                <h4 class="mb-3 text-info">📅 Filter Data Berdasarkan Tanggal</h4>
                <div id='calendar'></div>
                <div class="mt-2" id="selectedDatesDisplay">
                    <span class="badge bg-secondary">Filter Aktif: Default (Hari Ini)</span>
                </div>
            </div>
        </div>

        <h4 class="mb-3 text-primary">📊 Data Kendaraan dan Survei</h4>
        <div class="row mb-5">
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card card-summary primary h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-list-columns me-3 display-6 text-primary"></i>
                            <div>
                                <h5 class="card-title text-muted text-uppercase small">Terdaftar Hari Ini</h5>
                                <h3 class="fw-bold"><?php echo number_format($data_terdaftar['hari_ini']); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card card-summary success h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-check-circle me-3 display-6 text-success"></i>
                            <div>
                                <h5 class="card-title text-muted text-uppercase small">Lulus Uji Hari Ini</h5>
                                <h3 class="fw-bold"><?php echo number_format($data_lulus_uji['hari_ini']); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card card-summary warning h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-chat-dots me-3 display-6 text-warning"></i>
                            <div>
                                <h5 class="card-title text-muted text-uppercase small">Total Survei (Bulan Ini)</h5>
                                <h3 class="fw-bold"><?php echo number_format($total_survei); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card card-summary info h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-star-fill me-3 display-6 text-info"></i>
                            <div>
                                <h5 class="card-title text-muted text-uppercase small">Rata-rata Rating Keseluruhan</h5>
                                <h3 class="fw-bold"><?php echo number_format($ratings_data['keseluruhan'], 2); ?> / 5.0
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-white text-dark fw-bold">
                        Ringkasan Periodik Kendaraan
                    </div>
                    <div class="card-body">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Kategori</th>
                                    <th>Minggu Ini</th>
                                    <th>Bulan Ini</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Kendaraan Terdaftar</td>
                                    <td><?php echo number_format($data_terdaftar['minggu_ini']); ?></td>
                                    <td><?php echo number_format($data_terdaftar['bulan_ini']); ?></td>
                                </tr>
                                <tr>
                                    <td>Kendaraan Lulus Uji</td>
                                    <td><?php echo number_format($data_lulus_uji['minggu_ini']); ?></td>
                                    <td><?php echo number_format($data_lulus_uji['bulan_ini']); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-white text-dark fw-bold">
                        Rata-rata Rating Kategori (Bulan Ini)
                    </div>
                    <div class="card-body">
                        <canvas id="ratingChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    let selectedDates = []; // Array untuk menyimpan tanggal yang dipilih

    document.addEventListener('DOMContentLoaded', function() {
        // --- Logika Kalender Filter ---
        const calendarEl = document.getElementById('calendar');
        const datesDisplay = document.getElementById('selectedDatesDisplay');

        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'id', // Set bahasa ke Indonesia
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: ''
            },
            dateClick: function(info) {
                const dateStr = info.dateStr;
                const dateCell = info.dayEl;

                const index = selectedDates.indexOf(dateStr);

                if (index > -1) {
                    selectedDates.splice(index, 1);
                    dateCell.classList.remove('fc-day-selected');
                } else {
                    selectedDates.push(dateStr);
                    dateCell.classList.add('fc-day-selected');
                }

                updateFilterDisplay(selectedDates);

                // TODO: Ganti ini dengan Panggilan AJAX ke server untuk memuat ulang data dashboard
                // Contoh: loadDashboardData(selectedDates); 
                console.log('Tanggal dipilih:', selectedDates);
            }
        });

        calendar.render();

        function updateFilterDisplay(dates) {
            if (dates.length === 0) {
                datesDisplay.innerHTML =
                    '<span class="badge bg-secondary">Filter Aktif: Default (Hari Ini)</span>';
            } else if (dates.length === 1) {
                datesDisplay.innerHTML = `<span class="badge bg-primary">Filter Aktif: ${dates[0]}</span>`;
            } else {
                datesDisplay.innerHTML =
                    `<span class="badge bg-success">Filter Aktif: ${dates.length} Tanggal dipilih</span>`;
            }
        }

        // --- Logika Chart.js ---
        const chartData = <?php echo json_encode($chart_data); ?>;

        const ctx = document.getElementById('ratingChart').getContext('2d');
        const ratingChart = new Chart(ctx, {
            type: 'bar',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 5,
                        title: {
                            display: true,
                            text: 'Rata-rata Rating (Skala 5)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Rata-rata Kepuasan Pelanggan (Bulan Berjalan)'
                    }
                }
            }
        });
    });
    </script>
</body>

</html>