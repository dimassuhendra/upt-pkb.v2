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
</style>

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
            <a href="kelola-kendaraan.php" class="nav-link">
                <i class="bi bi-truck me-2"></i> Kelola Kendaraan
            </a>
        </li>
        <li>
            <a href="kelola-petugas.php" class="nav-link">
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