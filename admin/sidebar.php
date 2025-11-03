<?php
// Ambil nama berkas (file) yang sedang dibuka
// Contoh: Jika URL adalah http://.../admin/kelola-petugas.php, maka $current_page akan bernilai 'kelola-petugas.php'
$current_page = basename($_SERVER['PHP_SELF']);

// Definisikan daftar menu sidebar dalam bentuk array
$menu_items = [
    ['href' => 'index.php', 'icon' => 'bi-speedometer2', 'label' => 'Dashboard'],
    ['href' => 'kelola-kendaraan.php', 'icon' => 'bi-truck', 'label' => 'Kelola Kendaraan'],
    ['href' => 'kelola-petugas.php', 'icon' => 'bi-person-badge', 'label' => 'Kelola Petugas'],
    ['href' => 'laporan-survey.php', 'icon' => 'bi-bar-chart-line', 'label' => 'Laporan Survei'],
    // ['href' => 'master-data.php', 'icon' => 'bi-database', 'label' => 'Master Data'],
];
?>

<style>
/* CSS Anda */
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

.sidebar a {
    color: #adb5bd;
    padding: 10px 15px;
    text-decoration: none;
    display: block;
    transition: all 0.2s;
    /* Tambahkan transisi agar hover lebih halus */
}

/* Aturan CSS untuk link aktif dan hover */
.sidebar a:hover,
.sidebar .active {
    background-color: #495057;
    color: white;
}

/* Pastikan nav-link di ul juga mengikuti properti hover/active */
.nav-pills .nav-link.active,
.nav-pills .nav-link:hover {
    color: white;
    background-color: #495057;
}
</style>

<div class="sidebar d-flex flex-column">
    <div class="p-3 text-center">
        <h5 class="text-white mb-0">UPT PKB DASHBOARD</h5>
        <small class="text-secondary">Administrator Mode</small>
    </div>
    <ul class="nav nav-pills flex-column mb-auto">
        <?php foreach ($menu_items as $item): ?>
        <?php 
            // Cek apakah href item sama dengan halaman yang sedang dibuka
            $is_active = ($item['href'] === $current_page) ? 'active' : ''; 
            $aria_current = ($is_active) ? 'aria-current="page"' : '';
        ?>
        <li class="nav-item">
            <a href="<?php echo $item['href']; ?>" class="nav-link <?php echo $is_active; ?>"
                <?php echo $aria_current; ?>>
                <i class="bi <?php echo $item['icon']; ?> me-2"></i> <?php echo $item['label']; ?>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
    <div class="mt-auto p-3 border-top">
        <a href="../logout.php" class="btn btn-outline-danger w-100">
            <i class="bi bi-box-arrow-right me-2"></i> Logout
        </a>
    </div>
</div>