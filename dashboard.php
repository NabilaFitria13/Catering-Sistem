<?php
$stmt = $pdo->query("SELECT COUNT(*) as total FROM pelanggan");
$total_pelanggan = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM pesanan WHERE status != 'batal'");
$total_pesanan = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM pesanan WHERE status = 'baru'");
$pesanan_baru = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM pesanan WHERE status = 'diproses'");
$pesanan_diproses = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT SUM(jumlah_dibayar) as total FROM pembayaran WHERE status = 'lunas' AND MONTH(tanggal_pembayaran) = MONTH(CURRENT_DATE())");
$pendapatan_bulanan = $stmt->fetch()['total'] ?? 0;

$stmt = $pdo->query("SELECT SUM(jumlah) as total FROM pengeluaran WHERE MONTH(tanggal) = MONTH(CURRENT_DATE())");
$pengeluaran_bulanan = $stmt->fetch()['total'] ?? 0;

$laba = $pendapatan_bulanan - $pengeluaran_bulanan;

$stmt = $pdo->query("SELECT DATE_FORMAT(tanggal_pembayaran, '%Y-%m') as bulan, SUM(jumlah_dibayar) as total FROM pembayaran WHERE status = 'lunas' AND tanggal_pembayaran >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY DATE_FORMAT(tanggal_pembayaran, '%Y-%m') ORDER BY bulan ASC");
$monthly_data = $stmt->fetchAll();

$months = []; $incomes = [];
foreach($monthly_data as $data) {
    $months[] = date('M Y', strtotime($data['bulan'] . '-01'));
    $incomes[] = $data['total'];
}

$stmt = $pdo->query("SELECT m.nama_menu, SUM(dp.quantity) as total_terjual FROM detail_pesanan dp JOIN menu m ON dp.menu_id = m.id JOIN pesanan p ON dp.pesanan_id = p.id WHERE p.status = 'selesai' GROUP BY m.id ORDER BY total_terjual DESC LIMIT 5");
$top_menus = $stmt->fetchAll();

$stmt = $pdo->query("SELECT p.*, u.nama_lengkap as kasir FROM pesanan p LEFT JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC LIMIT 10");
$recent_orders = $stmt->fetchAll();
?>

<div class="container-fluid">
    <h2 class="mb-4">Dashboard</h2>
    
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6>Total Pelanggan</h6>
                    <h3><?php echo number_format($total_pelanggan); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6>Pesanan Baru</h6>
                    <h3><?php echo number_format($pesanan_baru); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6>Diproses</h6>
                    <h3><?php echo number_format($pesanan_diproses); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6>Pendapatan Bulan Ini</h6>
                    <h3>Rp <?php echo number_format($pendapatan_bulanan, 0, ',', '.'); ?></h3>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-7">
            <div class="card">
                <div class="card-header">Grafik Pendapatan 6 Bulan</div>
                <div class="card-body">
                    <canvas id="incomeChart" height="250"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-5">
            <div class="card">
                <div class="card-header">Menu Terlaris</div>
                <div class="card-body">
                    <?php foreach($top_menus as $index => $menu): ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span><?php echo $index+1; ?>. <?php echo $menu['nama_menu']; ?></span>
                            <span><?php echo number_format($menu['total_terjual']); ?> pcs</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-primary" style="width: <?php echo min(100, $menu['total_terjual'] / $top_menus[0]['total_terjual'] * 100); ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">Pesanan Terbaru</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr><th>No. Pesanan</th><th>Pemesan</th><th>No. WA</th><th>Tanggal</th><th>Total</th><th>Sumber</th><th>Status</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($recent_orders as $order): ?>
                        <tr>
                            <td><?php echo $order['no_pesanan']; ?></td>
                            <td><?php echo substr($order['nama_pemesan'], 0, 20); ?></td>
                            <td><?php echo $order['no_whatsapp']; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($order['tanggal_pemesanan'])); ?></td>
                            <td>Rp <?php echo number_format($order['total_harga'], 0, ',', '.'); ?></td>
                            <td><span class="badge bg-<?php echo $order['source'] == 'online' ? 'info' : 'secondary'; ?>"><?php echo $order['source']; ?></span></td>
                            <td><span class="badge bg-<?php echo $order['status'] == 'baru' ? 'warning' : ($order['status'] == 'diproses' ? 'info' : 'success'); ?>"><?php echo ucfirst($order['status']); ?></span></td>
                            <td><a href="?page=pesanan&edit=<?php echo $order['id']; ?>" class="btn btn-sm btn-warning">Edit</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('incomeChart'), {
    type: 'line',
    data: { labels: <?php echo json_encode($months); ?>, datasets: [{ label: 'Pendapatan', data: <?php echo json_encode($incomes); ?>, borderColor: '#3498db', fill: true, backgroundColor: 'rgba(52,152,219,0.1)', tension: 0.4 }] },
    options: { responsive: true, scales: { y: { ticks: { callback: v => 'Rp ' + v.toLocaleString('id-ID') } } } }
});
</script>