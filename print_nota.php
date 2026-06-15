<?php
require_once 'config/database.php';

if(!isset($_GET['id'])) {
    die("ID tidak ditemukan");
}

$stmt = $pdo->prepare("SELECT p.*, u.nama_lengkap as kasir FROM pesanan p LEFT JOIN users u ON p.user_id = u.id WHERE p.id = ?");
$stmt->execute([$_GET['id']]);
$order = $stmt->fetch();

if(!$order) {
    die("Pesanan tidak ditemukan");
}

$stmt = $pdo->prepare("SELECT dp.*, m.nama_menu FROM detail_pesanan dp JOIN menu m ON dp.menu_id = m.id WHERE dp.pesanan_id = ?");
$stmt->execute([$_GET['id']]);
$details = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota - <?php echo $order['no_pesanan']; ?></title>
    <style>
        body { font-family: 'Courier New', monospace; padding: 20px; }
        .nota { max-width: 300px; margin: 0 auto; border: 1px solid #ccc; padding: 15px; }
        .header { text-align: center; border-bottom: 1px dashed #000; margin-bottom: 10px; }
        .title { font-size: 18px; font-weight: bold; }
        .subtitle { font-size: 12px; }
        .divider { border-top: 1px dashed #000; margin: 10px 0; }
        .item { margin-bottom: 5px; }
        .item-name { font-size: 12px; }
        .item-price { text-align: right; font-size: 12px; }
        .total { font-weight: bold; border-top: 1px solid #000; padding-top: 5px; margin-top: 5px; }
        .footer { text-align: center; font-size: 10px; margin-top: 15px; }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
        .btn-print { background: #3498db; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="nota">
        <button class="btn-print no-print" onclick="window.print()"><i class="bi bi-printer"></i> Cetak Nota</button>
        
        <div class="header">
            <div class="title">DAPUR IBU LALA</div>
            <div class="subtitle">Catering Rumahan</div>
            <div class="subtitle"><?php echo date('d/m/Y H:i', strtotime($order['tanggal_pemesanan'])); ?></div>
            <div class="subtitle">No: <?php echo $order['no_pesanan']; ?></div>
        </div>
        
        <div class="customer">
            <div><strong>Pemesan:</strong> <?php echo $order['nama_pemesan']; ?></div>
            <div><strong>WA:</strong> <?php echo $order['no_whatsapp']; ?></div>
            <div><strong>Alamat:</strong> <?php echo $order['alamat_pengiriman']; ?></div>
            <div><strong>Tanggal Kirim:</strong> <?php echo date('d/m/Y', strtotime($order['tanggal_pengiriman'])); ?></div>
        </div>
        
        <div class="divider"></div>
        
        <div><strong>Detail Pesanan:</strong></div>
        <?php foreach($details as $item): ?>
        <div class="item d-flex">
            <div class="item-name" style="float:left; width:70%"><?php echo $item['nama_menu']; ?> x<?php echo $item['quantity']; ?></div>
            <div class="item-price" style="float:right; width:30%">Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?></div>
            <div style="clear:both"></div>
        </div>
        <?php endforeach; ?>
        
        <div class="divider"></div>
        
        <div class="total">
            <div class="d-flex" style="display:flex; justify-content:space-between">
                <span>Total:</span>
                <span>Rp <?php echo number_format($order['total_harga'], 0, ',', '.'); ?></span>
            </div>
            <div class="d-flex" style="display:flex; justify-content:space-between">
                <span>Metode Bayar:</span>
                <span><?php echo strtoupper($order['metode_pembayaran_dipilih']); ?></span>
            </div>
            <div class="d-flex" style="display:flex; justify-content:space-between">
                <span>Status Bayar:</span>
                <span><?php echo strtoupper($order['payment_status']); ?></span>
            </div>
        </div>
        
        <div class="divider"></div>
        
        <div class="footer">
            Terima kasih atas pesanan Anda!<br>
            <?php if($order['catatan']): ?>
            Catatan: <?php echo $order['catatan']; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        window.print();
    </script>
</body>
</html>