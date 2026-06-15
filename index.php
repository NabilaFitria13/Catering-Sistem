<?php
require_once 'config/database.php';

if(!isset($_SESSION['user_id'])) {
    header('Location: landing.php');
    exit();
}

$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$role = $_SESSION['role'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Sistem Manajemen Catering</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        .sidebar { height: 100vh; background: #2c3e50; position: fixed; width: 260px; top: 0; left: 0; overflow-y: auto; }
        .sidebar-header { padding: 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header h4 { color: white; }
        .sidebar-header small { color: rgba(255,255,255,0.7); }
        .sidebar a { color: rgba(255,255,255,0.8); text-decoration: none; padding: 12px 20px; display: block; font-size: 14px; transition: 0.3s; }
        .sidebar a:hover { background: rgba(255,255,255,0.1); padding-left: 25px; }
        .sidebar a.active { background: rgba(255,255,255,0.2); border-left: 3px solid #e67e22; }
        .sidebar .nav-header { padding: 10px 20px; color: rgba(255,255,255,0.5); font-size: 11px; text-transform: uppercase; letter-spacing: 1px; }
        .content { margin-left: 260px; padding: 20px; background: #f5f7fa; min-height: 100vh; }
        .top-navbar { background: white; padding: 12px 20px; border-radius: 10px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
        .btn-primary-custom { background: #e67e22; border: none; border-radius: 8px; padding: 8px 20px; color: white; }
        .btn-primary-custom:hover { background: #d35400; }
        .btn-print { background: #3498db; border: none; border-radius: 8px; padding: 5px 12px; color: white; }
        .menu-toggle { display: none; position: fixed; top: 15px; left: 15px; z-index: 1001; background: #e67e22; color: white; border: none; border-radius: 8px; padding: 10px 15px; cursor: pointer; }
        @media (max-width: 768px) { .sidebar { transform: translateX(-100%); width: 280px; } .sidebar.show { transform: translateX(0); } .content { margin-left: 0; padding: 70px 15px 20px; } .menu-toggle { display: block; } }
        .btn-logout-sidebar { color: #e74c3c !important; }
    </style>
</head>
<body>

<button class="menu-toggle" id="menuToggle"><i class="bi bi-list"></i></button>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h4>🍽️ Dapur Ibu Lala</h4>
        <small><?php echo ucfirst($role); ?> Panel</small>
    </div>
    
    <!-- MENU UNTUK SEMUA USER (Admin & Kasir) -->
    <div class="nav-header">📋 ORDER MANAGEMENT</div>
    <a href="?page=dashboard" class="<?php echo $page == 'dashboard' ? 'active' : ''; ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="?page=pesanan" class="<?php echo $page == 'pesanan' ? 'active' : ''; ?>"><i class="bi bi-cart"></i> Kelola Pesanan</a>
    
    <div class="nav-header">📊 REPORT</div>
    <a href="?page=laporan" class="<?php echo $page == 'laporan' ? 'active' : ''; ?>"><i class="bi bi-graph-up"></i> Laporan Transaksi</a>
    
    <!-- LAPORAN KEUANGAN HANYA UNTUK ADMIN -->
    <?php if($role == 'admin'): ?>
    <a href="?page=keuangan" class="<?php echo $page == 'keuangan' ? 'active' : ''; ?>"><i class="bi bi-wallet2"></i> Laporan Keuangan</a>
    <?php endif; ?>
    
    <!-- MENU PENGELUARAN HANYA UNTUK ADMIN -->
    <?php if($role == 'admin'): ?>
    <a href="?page=pengeluaran" class="<?php echo $page == 'pengeluaran' ? 'active' : ''; ?>"><i class="bi bi-box-seam"></i> Pengeluaran</a>
    <?php endif; ?>
    
    <!-- MENU KHUSUS ADMIN -->
    <?php if($role == 'admin'): ?>
    <div class="nav-header">🍕 MENU MANAGEMENT</div>
    <a href="?page=menu" class="<?php echo $page == 'menu' ? 'active' : ''; ?>"><i class="bi bi-menu-app"></i> Kelola Menu</a>
    <a href="?page=kategori_menu" class="<?php echo $page == 'kategori_menu' ? 'active' : ''; ?>"><i class="bi bi-tags"></i> Kelola Kategori Menu</a>
    
    <div class="nav-header">👥 STAFF MANAGEMENT</div>
    <a href="?page=pelanggan" class="<?php echo $page == 'pelanggan' ? 'active' : ''; ?>"><i class="bi bi-people"></i> Kelola Pelanggan</a>
    <a href="?page=users" class="<?php echo $page == 'users' ? 'active' : ''; ?>"><i class="bi bi-person-badge"></i> Kelola Akun Staff</a>
    
    <div class="nav-header">⚙️ SYSTEM</div>
    <a href="?page=pengaturan" class="<?php echo $page == 'pengaturan' ? 'active' : ''; ?>"><i class="bi bi-gear"></i> Pengaturan Sistem</a>
    <?php endif; ?>
    
    <div class="nav-header">👤 ACCOUNT</div>
    <a href="logout.php" class="btn-logout-sidebar"><i class="bi bi-box-arrow-right"></i> Logout</a>
</div>

<div class="content">
    <div class="top-navbar">
        <h4 class="mb-0">
            <?php 
                $titles = [
                    'dashboard' => 'Dashboard',
                    'pesanan' => 'Kelola Pesanan',
                    'laporan' => 'Laporan Transaksi',
                    'keuangan' => 'Laporan Keuangan',
                    'pengeluaran' => 'Pengeluaran',
                    'menu' => 'Kelola Menu',
                    'kategori_menu' => 'Kelola Kategori Menu',
                    'pelanggan' => 'Kelola Pelanggan',
                    'users' => 'Kelola Akun Staff',
                    'pengaturan' => 'Pengaturan Sistem'
                ];
                echo isset($titles[$page]) ? $titles[$page] : 'Dashboard';
            ?>
        </h4>
        <div>
            <span><i class="bi bi-person-circle"></i> <?php echo $_SESSION['nama']; ?> (<?php echo ucfirst($role); ?>)</span>
        </div>
    </div>
    
    <?php 
    $file = "pages/$page.php";
    if(file_exists($file)) {
        include $file;
    } else {
        include "pages/dashboard.php";
    }
    ?>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script>
$('#menuToggle').click(function() { $('#sidebar').toggleClass('show'); });
$(document).click(function(event) { if ($(window).width() <= 768) { if (!$(event.target).closest('#sidebar, #menuToggle').length) { $('#sidebar').removeClass('show'); } } });
$(document).ready(function() { if($('.datatable').length) { $('.datatable').DataTable({ language: { url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/id.json' }, responsive: true, scrollX: true }); } });
function exportToExcel(tableId, filename) { var table = document.getElementById(tableId); if(table) { var ws = XLSX.utils.table_to_sheet(table); var wb = XLSX.utils.book_new(); XLSX.utils.book_append_sheet(wb, ws, 'Sheet1'); XLSX.writeFile(wb, filename + '.xlsx'); } }
function printNota(id) { window.open('print_nota.php?id=' + id, '_blank', 'width=500,height=600'); }
</script>
</body>
</html>