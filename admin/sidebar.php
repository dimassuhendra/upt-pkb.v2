<?php
// ===============================================
// SIDEBAR UPT PKB (Refaktor Tampilan Modern)
// ===============================================

// Ambil nama berkas (file) yang sedang dibuka
// Contoh: Jika URL adalah http://.../admin/kelola-petugas.php, maka $current_page akan bernilai 'kelola-petugas.php'
$current_page = basename($_SERVER['PHP_SELF']);

// Definisikan daftar menu sidebar dalam bentuk array
$menu_items = [
    ['href' => 'index.php', 'icon' => 'bi-speedometer2', 'label' => 'Dashboard'],
    ['href' => 'kelola-kendaraan.php', 'icon' => 'bi-truck', 'label' => 'Kelola Kendaraan'],
    ['href' => 'kelola-petugas.php', 'icon' => 'bi-person-badge', 'label' => 'Kelola Petugas'],
    ['href' => 'laporan-survey.php', 'icon' => 'bi-bar-chart-line-fill', 'label' => 'Laporan Survei'],
    // ['href' => 'master-data.php', 'icon' => 'bi-database', 'label' => 'Master Data'],
];
?>

<style>
/* * CATATAN: Karena ini adalah komponen terpisah yang di-include, 
 * saya akan menggunakan style yang terpisah dari file utama.
 * Style modern dark mode untuk tampilan sidebar.
 */
:root {
    --sidebar-bg: #212529;
    --sidebar-width: 280px;
    --primary-color: #0d6efd;
    --text-color: #adb5bd;
    --active-bg: rgba(13, 110, 253, 0.15);
    --hover-bg: #343a40;
    --header-text: #fff;
}

.sidebar {
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    width: var(--sidebar-width);
    background-color: var(--sidebar-bg);
    color: var(--text-color);
    display: flex;
    flex-direction: column;
    box-shadow: 2px 0 5px rgba(0, 0, 0, 0.2);
    z-index: 1030;
    /* Di atas navbar */
}

/* Penyesuaian agar konten di file utama tidak tertutup sidebar */
.content {
    margin-left: var(--sidebar-width);
    padding: 20px;
}

/* Header/Logo Section */
.sidebar-header {
    padding: 20px 15px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    text-align: center;
}

.sidebar-header h5 {
    color: var(--header-text);
    font-weight: 700;
    letter-spacing: 0.5px;
}

.sidebar-header small {
    color: var(--text-color);
    font-size: 0.8rem;
}

/* Menu Items Styling */
.sidebar .nav-item {
    padding: 0 15px;
    /* Padding samping untuk list item */
}

.sidebar .nav-link {
    color: var(--text-color);
    padding: 12px 15px;
    margin-bottom: 5px;
    border-radius: 8px;
    /* Sudut melengkung */
    transition: background-color 0.2s, color 0.2s;
    font-size: 1rem;
    display: flex;
    align-items: center;
}

/* Hover State */
.sidebar .nav-link:hover {
    background-color: var(--hover-bg);
    color: #fff;
    /* Teks putih saat hover */
}

/* Active State */
.sidebar .nav-link.active {
    background-color: var(--primary-color);
    color: #fff;
    /* Teks putih */
    font-weight: 600;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
}

/* Icon Styling */
.sidebar .nav-link i {
    font-size: 1.1rem;
    margin-right: 10px;
}

/* Logout Section */
.sidebar-footer {
    padding: 15px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}
</style>

<div class="sidebar">
    <div class="sidebar-header">
        <h5 class="mb-0">UPT PKB DASHBOARD</h5>
        <small>Administrator Mode</small>
    </div>

    <nav class="flex-grow-1 p-3">
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
                    <i class="bi <?php echo $item['icon']; ?>"></i> <span><?php echo $item['label']; ?></span>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <a href="../logout.php" class="btn btn-outline-light w-100">
            <i class="bi bi-box-arrow-right me-2"></i> Logout
        </a>
    </div>
</div>