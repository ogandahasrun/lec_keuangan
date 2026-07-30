<?php
session_start();
require_once 'koneksi.php';

// Redirect if not logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$nama_lengkap = $_SESSION['nama_lengkap'] ?? $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-chart-pie" style="font-size: 24px; color: var(--primary);"></i>
            <h2>Lec Keuangan</h2>
        </div>
        
        <div class="sidebar-nav">
            <a href="?page=dashboard" class="nav-item <?= $page === 'dashboard' ? 'active' : '' ?>">
                <i class="fas fa-home"></i> Dashboard
            </a>
            
            <div style="padding: 15px 24px 5px; font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">
                Laporan Ralan
            </div>
            
            <a href="?page=ppn_obat" class="nav-item <?= $page === 'ppn_obat' ? 'active' : '' ?>">
                <i class="fas fa-file-invoice"></i> PPN Obat
            </a>
            
            <a href="?page=hutang_medis" class="nav-item <?= $page === 'hutang_medis' ? 'active' : '' ?>">
                <i class="fas fa-hand-holding-usd"></i> Hutang Medis
            </a>
            
            <a href="?page=penjualan_bebas" class="nav-item <?= $page === 'penjualan_bebas' ? 'active' : '' ?>">
                <i class="fas fa-store"></i> Penjualan Bebas
            </a>
            
            <div style="padding: 15px 24px 5px; font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;">
                Pendapatan & Jasa
            </div>
            
            <a href="?page=hitung_jasa" class="nav-item <?= $page === 'hitung_jasa' ? 'active' : '' ?>">
                <i class="fas fa-calculator"></i> Hitung Jasa Umum
            </a>
            
            <a href="?page=pendapatan_billing" class="nav-item <?= $page === 'pendapatan_billing' ? 'active' : '' ?>">
                <i class="fas fa-file-invoice-dollar"></i> Pendapatan Billing
            </a>
            
            <a href="?page=umbal" class="nav-item <?= $page === 'umbal' ? 'active' : '' ?>">
                <i class="fas fa-sync-alt"></i> Umbal
            </a>
        </div>
        
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">
                    <?= substr(htmlspecialchars($nama_lengkap), 0, 1) ?>
                </div>
                <div class="user-details">
                    <span class="user-name" title="<?= htmlspecialchars($nama_lengkap) ?>"><?= htmlspecialchars($nama_lengkap) ?></span>
                    <span class="user-role">User</span>
                </div>
            </div>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <?php
            // Router sederhana
            $pages_dir = 'pages';
            $allowed_pages = ['dashboard', 'ppn_obat', 'hutang_medis', 'penjualan_bebas', 'hitung_jasa', 'pendapatan_billing', 'umbal'];
            
            if (in_array($page, $allowed_pages)) {
                if ($page === 'dashboard') {
                    echo '<div class="page-header">';
                    echo '<h1 class="page-title">Dashboard</h1>';
                    echo '<p class="page-subtitle">Selamat datang di aplikasi laporan keuangan.</p>';
                    echo '</div>';
                    echo '<div class="content-card">Silakan pilih menu di sidebar untuk melihat laporan.</div>';
                } else {
                    $file = $pages_dir . '/' . $page . '.php';
                    if (file_exists($file)) {
                        include $file;
                    } else {
                        echo '<div class="content-card" style="color:var(--danger)">File ' . htmlspecialchars($file) . ' belum dibuat.</div>';
                    }
                }
            } else {
                echo '<div class="content-card">Halaman tidak ditemukan.</div>';
            }
        ?>
    </main>
    
</body>
</html>
