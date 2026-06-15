<?php
if(!hasPermission(['admin', 'kasir'])) { echo "<div class='alert alert-danger'>Akses ditolak!</div>"; exit(); }

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

$stmt = $pdo->prepare("
    SELECT DATE(tanggal_pembayaran) as tanggal, SUM(jumlah_dibayar) as total 
    FROM pembayaran 
    WHERE status = 'lunas' AND DATE(tanggal_pembayaran) BETWEEN ? AND ? 
    GROUP BY DATE(tanggal_pembayaran) 
    ORDER BY tanggal
");
$stmt->execute([$start_date, $end_date]);
$pendapatan_harian = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT tanggal, SUM(jumlah) as total 
    FROM pengeluaran 
    WHERE tanggal BETWEEN ? AND ? 
    GROUP BY tanggal 
    ORDER BY tanggal
");
$stmt->execute([$start_date, $end_date]);
$pengeluaran_harian = $stmt->fetchAll();

$total_pendapatan = array_sum(array_column($pendapatan_harian, 'total'));
$total_pengeluaran = array_sum(array_column($pengeluaran_harian, 'total'));
$laba = $total_pendapatan - $total_pengeluaran;
?>

<div class="container-fluid">
    <h2 class="mb-4">Laporan Keuangan</h2>
    
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <input type="hidden" name="page" value="keuangan">
                <div class="col-12 col-md-5">
                    <label>Dari Tanggal</label>
                    <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-12 col-md-5">
                    <label>Sampai Tanggal</label>
                    <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                </div>
                <div class="col-12 col-md-2">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary-custom d-block w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6>Total Pendapatan</h6>
                    <h4>Rp <?php echo number_format($total_pendapatan, 0, ',', '.'); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h6>Total Pengeluaran</h6>
                    <h4>Rp <?php echo number_format($total_pengeluaran, 0, ',', '.'); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6>Laba Bersih</h6>
                    <h4>Rp <?php echo number_format($laba, 0, ',', '.'); ?></h4>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <h5>Detail Transaksi</h5>
            <button class="btn btn-sm btn-success" onclick="exportToExcel('keuanganTable', 'keuangan_<?php echo $start_date; ?>_<?php echo $end_date; ?>')">
                <i class="bi bi-file-excel"></i> Export Excel
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="keuanganTable">
                    <thead>
                        <tr><th>Tanggal</th><th>Pemasukan</th><th>Pengeluaran</th><th>Saldo</th></tr>
                    </thead>
                    <tbody>
                        <?php
                        $saldo = 0;
                        $all_dates = array_unique(array_merge(
                            array_column($pendapatan_harian, 'tanggal'),
                            array_column($pengeluaran_harian, 'tanggal')
                        ));
                        sort($all_dates);
                        
                        foreach($all_dates as $date):
                            $income = 0;
                            $expense = 0;
                            foreach($pendapatan_harian as $p) { if($p['tanggal'] == $date) $income = $p['total']; }
                            foreach($pengeluaran_harian as $e) { if($e['tanggal'] == $date) $expense = $e['total']; }
                            $saldo += $income - $expense;
                        ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($date)); ?></td>
                            <td class="text-success">Rp <?php echo number_format($income, 0, ',', '.'); ?></td>
                            <td class="text-danger">Rp <?php echo number_format($expense, 0, ',', '.'); ?></td>
                            <td class="fw-bold">Rp <?php echo number_format($saldo, 0, ',', '.'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td>Total</div>
                            <td class="text-success">Rp <?php echo number_format($total_pendapatan, 0, ',', '.'); ?></div>
                            <td class="text-danger">Rp <?php echo number_format($total_pengeluaran, 0, ',', '.'); ?></div>
                            <td>Rp <?php echo number_format($laba, 0, ',', '.'); ?></div>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>