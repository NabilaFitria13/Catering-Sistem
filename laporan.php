<?php
if(!hasPermission(['admin', 'kasir'])) { echo "<div class='alert alert-danger'>Akses ditolak!</div>"; exit(); }

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'sales';

// Laporan Penjualan (SEMUA PESANAN YANG TIDAK BATAL)
$stmt = $pdo->prepare("
    SELECT DATE(tanggal_pemesanan) as tanggal, 
           COUNT(*) as total_pesanan, 
           SUM(total_harga) as total_penjualan,
           SUM(CASE WHEN source = 'online' THEN 1 ELSE 0 END) as online_count,
           SUM(CASE WHEN source = 'offline' THEN 1 ELSE 0 END) as offline_count,
           SUM(CASE WHEN source = 'online' THEN total_harga ELSE 0 END) as online_total,
           SUM(CASE WHEN source = 'offline' THEN total_harga ELSE 0 END) as offline_total,
           SUM(CASE WHEN payment_status = 'lunas' THEN total_harga ELSE 0 END) as pendapatan_lunas,
           SUM(CASE WHEN payment_status = 'belum_bayar' THEN total_harga ELSE 0 END) as pendapatan_belum_bayar,
           SUM(CASE WHEN payment_status = 'menunggu_verifikasi' THEN total_harga ELSE 0 END) as pendapatan_menunggu
    FROM pesanan 
    WHERE status != 'batal' AND DATE(tanggal_pemesanan) BETWEEN ? AND ? 
    GROUP BY DATE(tanggal_pemesanan) 
    ORDER BY tanggal
");
$stmt->execute([$start_date, $end_date]);
$sales_report = $stmt->fetchAll();

// Total keseluruhan
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_pesanan,
        SUM(total_harga) as total_penjualan,
        SUM(CASE WHEN source = 'online' THEN total_harga ELSE 0 END) as online_sales,
        SUM(CASE WHEN source = 'offline' THEN total_harga ELSE 0 END) as offline_sales,
        SUM(CASE WHEN payment_status = 'lunas' THEN total_harga ELSE 0 END) as total_lunas,
        SUM(CASE WHEN payment_status != 'lunas' THEN total_harga ELSE 0 END) as total_belum_lunas
    FROM pesanan 
    WHERE status != 'batal' AND DATE(tanggal_pemesanan) BETWEEN ? AND ?
");
$stmt->execute([$start_date, $end_date]);
$total_all = $stmt->fetch();

// Menu Terlaris
$stmt = $pdo->prepare("
    SELECT m.nama_menu, m.kategori, SUM(dp.quantity) as total_terjual, SUM(dp.subtotal) as total_penjualan 
    FROM detail_pesanan dp 
    JOIN menu m ON dp.menu_id = m.id 
    JOIN pesanan p ON dp.pesanan_id = p.id 
    WHERE p.status != 'batal' AND DATE(p.tanggal_pemesanan) BETWEEN ? AND ? 
    GROUP BY m.id 
    ORDER BY total_terjual DESC 
    LIMIT 10
");
$stmt->execute([$start_date, $end_date]);
$top_menus = $stmt->fetchAll();

// Total Pendapatan dari pembayaran lunas
$stmt = $pdo->prepare("SELECT SUM(jumlah_dibayar) as total FROM pembayaran WHERE status = 'lunas' AND DATE(tanggal_pembayaran) BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$total_pendapatan = $stmt->fetch()['total'] ?? 0;

// Total Pengeluaran
$stmt = $pdo->prepare("SELECT SUM(jumlah) as total FROM pengeluaran WHERE tanggal BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$total_pengeluaran = $stmt->fetch()['total'] ?? 0;

// Hitung semua pesanan (untuk debugging)
$stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(total_harga) as nominal FROM pesanan WHERE DATE(tanggal_pemesanan) BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$semua_pesanan = $stmt->fetch();

// Pesanan selesai
$stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(total_harga) as nominal FROM pesanan WHERE status = 'selesai' AND DATE(tanggal_pemesanan) BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$pesanan_selesai = $stmt->fetch();
?>

<div class="container-fluid">
    <h2 class="mb-4">Laporan & Analisis</h2>
    
    <!-- Info Ringkas -->
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> 
        <strong>Info Data:</strong> 
        Total semua pesanan (periode): <?php echo $semua_pesanan['total']; ?> pesanan (Rp <?php echo number_format($semua_pesanan['nominal'], 0, ',', '.'); ?>)
        | Pesanan selesai: <?php echo $pesanan_selesai['total']; ?> pesanan (Rp <?php echo number_format($pesanan_selesai['nominal'], 0, ',', '.'); ?>)
    </div>
    
    <!-- Form Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <input type="hidden" name="page" value="laporan">
                <div class="col-12 col-md-3">
                    <label class="form-label">Dari Tanggal</label>
                    <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Sampai Tanggal</label>
                    <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Tipe Laporan</label>
                    <select name="report_type" class="form-control">
                        <option value="sales" <?php echo $report_type == 'sales' ? 'selected' : ''; ?>>Penjualan Harian</option>
                        <option value="menu" <?php echo $report_type == 'menu' ? 'selected' : ''; ?>>Menu Terlaris</option>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary-custom d-block w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6>Pendapatan Lunas</h6>
                    <h4>Rp <?php echo number_format($total_all['total_lunas'] ?? 0, 0, ',', '.'); ?></h4>
                    <small>Dari pembayaran terverifikasi</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6>Total Pesanan</h6>
                    <h4><?php echo number_format($total_all['total_pesanan'] ?? 0); ?> pesanan</h4>
                    <small>Online: <?php echo number_format($total_all['online_sales'] ?? 0); ?> | Offline: <?php echo number_format($total_all['offline_sales'] ?? 0); ?></small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h6>Total Pengeluaran</h6>
                    <h4>Rp <?php echo number_format($total_pengeluaran, 0, ',', '.'); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6>Laba Bersih</h6>
                    <h4>Rp <?php echo number_format(($total_all['total_lunas'] ?? 0) - $total_pengeluaran, 0, ',', '.'); ?></h4>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Detail Laporan -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><?php echo $report_type == 'sales' ? 'Laporan Penjualan Harian' : 'Menu Terlaris'; ?></h5>
            <button class="btn btn-sm btn-success" onclick="exportToExcel('reportTable', 'laporan_<?php echo $start_date; ?>_<?php echo $end_date; ?>')">
                <i class="bi bi-file-excel"></i> Export Excel
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="reportTable">
                    <thead>
                        <?php if($report_type == 'sales'): ?>
                        <tr>
                            <th>Tanggal</th>
                            <th>Jumlah Pesanan</th>
                            <th>Online</th>
                            <th>Offline</th>
                            <th>Total Penjualan</th>
                            <th>Lunas</th>
                            <th>Belum Lunas</th>
                        </tr>
                        <?php else: ?>
                        <tr>
                            <th>No</th>
                            <th>Nama Menu</th>
                            <th>Kategori</th>
                            <th>Jumlah Terjual</th>
                            <th>Total Penjualan</th>
                        </tr>
                        <?php endif; ?>
                    </thead>
                    <tbody>
                        <?php if($report_type == 'sales'): ?>
                            <?php foreach($sales_report as $report): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($report['tanggal'])); ?></td>
                                <td><?php echo number_format($report['total_pesanan']); ?></td>
                                <td><?php echo number_format($report['online_count']); ?></div>
                                <td><?php echo number_format($report['offline_count']); ?></div>
                                <td class="text-end">Rp <?php echo number_format($report['total_penjualan'], 0, ',', '.'); ?></div>
                                <td class="text-end text-success">Rp <?php echo number_format($report['pendapatan_lunas'], 0, ',', '.'); ?></div>
                                <td class="text-end text-warning">Rp <?php echo number_format($report['pendapatan_belum_bayar'] + $report['pendapatan_menunggu'], 0, ',', '.'); ?></div>
                             </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <?php foreach($top_menus as $index => $menu): ?>
                            <tr>
                                <td><?php echo $index+1; ?></div>
                                <td><?php echo $menu['nama_menu']; ?></div>
                                <td><?php echo $menu['kategori']; ?></div>
                                <td class="text-end"><?php echo number_format($menu['total_terjual']); ?> pcs</div>
                                <td class="text-end">Rp <?php echo number_format($menu['total_penjualan'], 0, ',', '.'); ?></div>
                             </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <?php if($report_type == 'sales'): ?>
                        <tr class="fw-bold bg-light">
                            <td>Total</div>
                            <td><?php echo number_format(array_sum(array_column($sales_report, 'total_pesanan'))); ?></div>
                            <td><?php echo number_format(array_sum(array_column($sales_report, 'online_count'))); ?></div>
                            <td><?php echo number_format(array_sum(array_column($sales_report, 'offline_count'))); ?></div>
                            <td class="text-end">Rp <?php echo number_format(array_sum(array_column($sales_report, 'total_penjualan')), 0, ',', '.'); ?></div>
                            <td class="text-end">Rp <?php echo number_format(array_sum(array_column($sales_report, 'pendapatan_lunas')), 0, ',', '.'); ?></div>
                            <td class="text-end">Rp <?php echo number_format(array_sum(array_column($sales_report, 'pendapatan_belum_bayar')), 0, ',', '.'); ?></div>
                         </div>
                        <?php endif; ?>
                    </tfoot>
                 </div>
            </div>
        </div>
    </div>
</div>