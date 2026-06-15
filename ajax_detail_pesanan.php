<?php
require_once 'config/database.php';

if(!isset($_GET['id'])) {
    echo "ID tidak ditemukan";
    exit();
}

$stmt = $pdo->prepare("
    SELECT p.*, u.nama_lengkap as kasir 
    FROM pesanan p 
    LEFT JOIN users u ON p.user_id = u.id 
    WHERE p.id = ?
");
$stmt->execute([$_GET['id']]);
$order = $stmt->fetch();

if(!$order) {
    echo "Pesanan tidak ditemukan";
    exit();
}

$stmt = $pdo->prepare("
    SELECT dp.*, m.nama_menu, m.harga as menu_harga
    FROM detail_pesanan dp 
    JOIN menu m ON dp.menu_id = m.id 
    WHERE dp.pesanan_id = ?
");
$stmt->execute([$_GET['id']]);
$details = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <title>Detail Pesanan</title>
    <style>
        body { background: #f8f9fa; padding: 20px; }
        .detail-card { background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; overflow: hidden; }
        .detail-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 20px; }
        .detail-body { padding: 20px; }
        .info-row { display: flex; padding: 8px 0; border-bottom: 1px solid #eee; }
        .info-label { width: 140px; font-weight: 600; color: #555; }
        .info-value { flex: 1; color: #333; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; }
        .status-baru { background: #ffc107; color: #856404; }
        .status-diproses { background: #17a2b8; color: white; }
        .status-selesai { background: #28a745; color: white; }
        .status-batal { background: #dc3545; color: white; }
        .payment-lunas { background: #28a745; color: white; }
        .payment-belum { background: #dc3545; color: white; }
        .table-detail { margin-bottom: 0; }
        .table-detail th { background: #f8f9fa; }
        .total-row { background: #f8f9fa; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <div class="detail-card">
        <div class="detail-header">
            <h5 class="mb-0"><i class="bi bi-receipt"></i> Detail Pesanan</h5>
            <small>No. Pesanan: <?php echo $order['no_pesanan']; ?></small>
        </div>
        <div class="detail-body">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="mb-3"><i class="bi bi-person-circle"></i> Informasi Pemesan</h6>
                    <div class="info-row">
                        <div class="info-label">Nama Pemesan</div>
                        <div class="info-value"><?php echo htmlspecialchars($order['nama_pemesan']); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">No. WhatsApp</div>
                        <div class="info-value"><?php echo $order['no_whatsapp'] ?: '-'; ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Alamat Pengiriman</div>
                        <div class="info-value"><?php echo nl2br(htmlspecialchars($order['alamat_pengiriman'])) ?: '-'; ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Tanggal Pesan</div>
                        <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($order['tanggal_pemesanan'])); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Tanggal Kirim</div>
                        <div class="info-value"><?php echo $order['tanggal_pengiriman'] ? date('d/m/Y', strtotime($order['tanggal_pengiriman'])) : '-'; ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Catatan</div>
                        <div class="info-value"><?php echo htmlspecialchars($order['catatan']) ?: '-'; ?></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <h6 class="mb-3"><i class="bi bi-credit-card"></i> Informasi Pembayaran</h6>
                    <div class="info-row">
                        <div class="info-label">Metode Pembayaran</div>
                        <div class="info-value">
                            <?php
                            $metode_label = '';
                            switch($order['metode_pembayaran_dipilih']) {
                                case 'tunai': $metode_label = '💰 Tunai'; break;
                                case 'cod': $metode_label = '🚚 COD'; break;
                                case 'transfer': $metode_label = '🏦 Transfer Bank'; break;
                                case 'e_wallet': $metode_label = '📱 E-Wallet'; break;
                                case 'qris': $metode_label = '📱 QRIS'; break;
                                default: $metode_label = '-';
                            }
                            echo $metode_label;
                            ?>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Status Pembayaran</div>
                        <div class="info-value">
                            <?php if($order['payment_status'] == 'lunas'): ?>
                                <span class="badge bg-success">✅ Lunas</span>
                            <?php else: ?>
                                <span class="badge bg-danger">⚠️ Belum Lunas</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Status Pesanan</div>
                        <div class="info-value">
                            <?php
                            $status_class = '';
                            switch($order['status']) {
                                case 'baru': $status_class = 'status-baru'; break;
                                case 'diproses': $status_class = 'status-diproses'; break;
                                case 'selesai': $status_class = 'status-selesai'; break;
                                case 'batal': $status_class = 'status-batal'; break;
                                default: $status_class = '';
                            }
                            ?>
                            <span class="status-badge <?php echo $status_class; ?>"><?php echo ucfirst($order['status']); ?></span>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Sumber Pesanan</div>
                        <div class="info-value">
                            <?php if($order['source'] == 'online'): ?>
                                <span class="badge bg-info">🌐 Online</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">📱 Offline (Kasir)</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if($order['user_id']): ?>
                    <div class="info-row">
                        <div class="info-label">Diproses Oleh</div>
                        <div class="info-value"><?php echo $order['kasir']; ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <hr>
            
            <h6 class="mb-3"><i class="bi bi-cart"></i> Daftar Menu yang Dipesan</h6>
            <div class="table-responsive">
                <table class="table table-bordered table-detail">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Nama Menu</th>
                            <th>Harga Satuan</th>
                            <th>Jumlah</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        $grand_total = 0;
                        foreach($details as $item): 
                            $subtotal = $item['quantity'] * $item['harga_satuan'];
                            $grand_total += $subtotal;
                        ?>
                        <tr>
                            <td class="text-center"><?php echo $no++; ?></td>
                            <td><?php echo htmlspecialchars($item['nama_menu']); ?></td>
                            <td class="text-end">Rp <?php echo number_format($item['harga_satuan'], 0, ',', '.'); ?></td>
                            <td class="text-center"><?php echo $item['quantity']; ?></td>
                            <td class="text-end">Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($details)): ?>
                        <tr>
                            <td colspan="5" class="text-center">Tidak ada detail pesanan</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td colspan="4" class="text-end fw-bold">Grand Total:</td>
                            <td class="text-end fw-bold text-primary">Rp <?php echo number_format($grand_total, 0, ',', '.'); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <div class="mt-3 text-muted small">
                <i class="bi bi-clock"></i> Pesanan dibuat pada: <?php echo date('d/m/Y H:i:s', strtotime($order['created_at'])); ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>