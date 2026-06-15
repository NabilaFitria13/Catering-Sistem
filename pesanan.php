<?php
if(!hasPermission(['admin', 'kasir'])) { echo "<div class='alert alert-danger'>Akses ditolak!</div>"; exit(); }

// UPDATE STATUS PESANAN
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if($_POST['action'] == 'update_status') {
        $stmt = $pdo->prepare("UPDATE pesanan SET status = ? WHERE id = ?");
        $stmt->execute([$_POST['status'], $_POST['id']]);
        echo "success";
        exit();
    }
    
    // TAMBAH PESANAN OFFLINE
    if($_POST['action'] == 'add_offline' && $_SESSION['role'] == 'kasir') {
        $nama_pemesan = $_POST['nama_pemesan'];
        $no_whatsapp = $_POST['no_whatsapp'];
        $alamat = $_POST['alamat'];
        $tanggal_kirim = $_POST['tanggal_kirim'];
        $catatan = $_POST['catatan'];
        $metode_pembayaran = $_POST['metode_pembayaran'];
        
        $menu_ids = $_POST['menu_id'];
        $quantities = $_POST['quantity'];
        
        // Tentukan status pembayaran berdasarkan metode
        if($metode_pembayaran == 'cod' || $metode_pembayaran == 'tunai') {
            $payment_status = 'belum_bayar';
        } else {
            $payment_status = 'lunas';
        }
        
        $total_harga = 0;
        $detail_items = array();
        
        for($i = 0; $i < count($menu_ids); $i++) {
            $menu_id = $menu_ids[$i];
            $qty = $quantities[$i];
            
            if(!empty($menu_id) && $qty > 0) {
                $stmt = $pdo->prepare("SELECT * FROM menu WHERE id = ?");
                $stmt->execute([$menu_id]);
                $menu = $stmt->fetch();
                
                if($menu) {
                    $subtotal = $menu['harga'] * $qty;
                    $total_harga += $subtotal;
                    $detail_items[] = array(
                        'menu_id' => $menu_id,
                        'quantity' => $qty,
                        'harga' => $menu['harga'],
                        'subtotal' => $subtotal
                    );
                }
            }
        }
        
        // Insert ke tabel pesanan
        $stmt = $pdo->prepare("INSERT INTO pesanan (nama_pemesan, no_whatsapp, alamat_pengiriman, tanggal_pemesanan, tanggal_pengiriman, catatan, total_harga, status, payment_status, metode_pembayaran_dipilih, source, user_id) VALUES (?, ?, ?, NOW(), ?, ?, ?, 'baru', ?, ?, 'offline', ?)");
        $stmt->execute([$nama_pemesan, $no_whatsapp, $alamat, $tanggal_kirim, $catatan, $total_harga, $payment_status, $metode_pembayaran, $_SESSION['user_id']]);
        $pesanan_id = $pdo->lastInsertId();
        
        // Insert detail pesanan
        foreach($detail_items as $item) {
            $stmt = $pdo->prepare("INSERT INTO detail_pesanan (pesanan_id, menu_id, quantity, harga_satuan, subtotal) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$pesanan_id, $item['menu_id'], $item['quantity'], $item['harga'], $item['subtotal']]);
        }
        
        echo "<script>alert('Pesanan offline berhasil ditambahkan!'); window.location.href='?page=pesanan';</script>";
        exit();
    }
}

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'semua';
$sql = "SELECT p.* FROM pesanan p";
if($filter != 'semua') { $sql .= " WHERE p.status = '$filter'"; }
$sql .= " ORDER BY p.created_at DESC";
$stmt = $pdo->query($sql);
$pesanan = $stmt->fetchAll();

// Ambil daftar menu untuk dropdown pesanan offline
$menu_list = [];
if($_SESSION['role'] == 'kasir') {
    $stmt = $pdo->query("SELECT * FROM menu WHERE status = 'aktif' ORDER BY nama_menu");
    $menu_list = $stmt->fetchAll();
}

$status_counts = [];
$stmt = $pdo->query("SELECT status, COUNT(*) as total FROM pesanan GROUP BY status");
while($row = $stmt->fetch()) { $status_counts[$row['status']] = $row['total']; }
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pesanan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- DataTables CSS dan JS -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <style>
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; }
        .status-baru { background: #ffc107; color: #856404; }
        .status-diproses { background: #17a2b8; color: white; }
        .status-selesai { background: #28a745; color: white; }
        .status-batal { background: #dc3545; color: white; }
        .btn-primary-custom { background: #e67e22; border: none; border-radius: 8px; padding: 8px 20px; color: white; }
        .btn-primary-custom:hover { background: #d35400; }
        .btn-group .btn { margin: 0 2px; }
        .badge.bg-secondary { background-color: #6c757d !important; }
        .badge.bg-warning { background-color: #ffc107 !important; color: #856404; }
        .badge.bg-info { background-color: #17a2b8 !important; }
        .badge.bg-success { background-color: #28a745 !important; }
        .badge.bg-danger { background-color: #dc3545 !important; }
        .modal-lg-custom { max-width: 800px; }
        .info-row { display: flex; padding: 8px 0; border-bottom: 1px solid #eee; }
        .info-label { width: 140px; font-weight: 600; color: #555; }
        .info-value { flex: 1; color: #333; }
        .total-row { background: #f8f9fa; font-weight: bold; }
        table.dataTable { margin-top: 0 !important; margin-bottom: 0 !important; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Kelola Pesanan</h2>
        <?php if($_SESSION['role'] == 'kasir'): ?>
        <button class="btn btn-primary-custom" onclick="openOfflineModal()">
            <i class="bi bi-plus-circle"></i> Pesanan Offline
        </button>
        <?php endif; ?>
    </div>
    
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2">
                <a href="?page=pesanan&filter=semua" class="btn btn-sm btn-outline-primary">Semua (<?php echo array_sum($status_counts); ?>)</a>
                <a href="?page=pesanan&filter=baru" class="btn btn-sm btn-outline-warning">Baru (<?php echo $status_counts['baru'] ?? 0; ?>)</a>
                <a href="?page=pesanan&filter=diproses" class="btn btn-sm btn-outline-info">Diproses (<?php echo $status_counts['diproses'] ?? 0; ?>)</a>
                <a href="?page=pesanan&filter=selesai" class="btn btn-sm btn-outline-success">Selesai (<?php echo $status_counts['selesai'] ?? 0; ?>)</a>
                <a href="?page=pesanan&filter=batal" class="btn btn-sm btn-outline-danger">Batal (<?php echo $status_counts['batal'] ?? 0; ?>)</a>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="pesananTable" width="100%">
                    <thead class="table-dark">
                        <tr class="text-center">
                            <th width="120">No. Pesanan</th>
                            <th width="150">Pemesan</th>
                            <th width="120">No. WA</th>
                            <th width="100">Tanggal</th>
                            <th width="120">Total</th>
                            <th width="120">Metode Bayar</th>
                            <th width="120">Status Pesanan</th>
                            <th width="120">Status Bayar</th>
                            <th width="150">Aksi</th>
                         </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($pesanan)): ?>
                         <tr>
                            <td colspan="9" class="text-center">Belum ada data pesanan</td>
                         </tr>
                        <?php else: ?>
                        <?php foreach($pesanan as $order): ?>
                         <tr>
                            <td class="text-center"><?php echo $order['no_pesanan']; ?></td>
                            <td><?php echo htmlspecialchars($order['nama_pemesan']); ?></td>
                            <td class="text-center"><?php echo $order['no_whatsapp']; ?></td>
                            <td class="text-center"><?php echo date('d/m/Y', strtotime($order['tanggal_pemesanan'])); ?></td>
                            <td class="text-end">Rp <?php echo number_format($order['total_harga'], 0, ',', '.'); ?></td>
                            <td class="text-center">
                                <?php
                                $metode_label = '';
                                switch($order['metode_pembayaran_dipilih']) {
                                    case 'tunai': $metode_label = '💰 Tunai'; break;
                                    case 'cod': $metode_label = '🚚 COD'; break;
                                    case 'transfer': $metode_label = '🏦 Transfer'; break;
                                    case 'e_wallet': $metode_label = '📱 E-Wallet'; break;
                                    case 'qris': $metode_label = '📱 QRIS'; break;
                                    default: $metode_label = '-';
                                }
                                ?>
                                <span class="badge bg-secondary"><?php echo $metode_label; ?></span>
                            </td>
                            <td class="text-center">
                                <select class="form-select form-select-sm status-select" data-id="<?php echo $order['id']; ?>" style="width: 100px;">
                                    <option value="baru" <?php echo $order['status'] == 'baru' ? 'selected' : ''; ?>>Baru</option>
                                    <option value="diproses" <?php echo $order['status'] == 'diproses' ? 'selected' : ''; ?>>Diproses</option>
                                    <option value="selesai" <?php echo $order['status'] == 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                                    <option value="batal" <?php echo $order['status'] == 'batal' ? 'selected' : ''; ?>>Batal</option>
                                </select>
                            </td>
                            <td class="text-center">
                                <?php if($order['payment_status'] == 'lunas'): ?>
                                    <span class="badge bg-success">Lunas</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Belum Lunas</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-info" onclick="viewDetail(<?php echo $order['id']; ?>)">
                                        <i class="bi bi-eye"></i> Detail
                                    </button>
                                    <?php if($_SESSION['role'] == 'kasir'): ?>
                                        <button class="btn btn-sm btn-primary" onclick="printNota(<?php echo $order['id']; ?>)">
                                            <i class="bi bi-printer"></i> Cetak
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                         </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL TAMBAH PESANAN OFFLINE (SAMA SEPERTI SEBELUMNYA) -->
<div id="offlineModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="offlineForm">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-cart-plus"></i> Tambah Pesanan Offline</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_offline">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label>Nama Pemesan</label>
                                <input type="text" name="nama_pemesan" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label>No. WhatsApp</label>
                                <input type="text" name="no_whatsapp" class="form-control">
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <label>Alamat Pengiriman</label>
                                <textarea name="alamat" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label>Tanggal Pengiriman</label>
                                <input type="date" name="tanggal_kirim" class="form-control" value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label>Metode Pembayaran</label>
                                <select name="metode_pembayaran" id="metodeBayarOffline" class="form-control" required>
                                    <option value="tunai">💰 Tunai (Bayar di Tempat)</option>
                                    <option value="cod">🚚 COD (Bayar di Tempat)</option>
                                    <option value="transfer">🏦 Transfer Bank</option>
                                    <option value="e_wallet">📱 E-Wallet</option>
                                    <option value="qris">📱 QRIS</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <label>Catatan</label>
                                <textarea name="catatan" class="form-control" rows="2" placeholder="Contoh: pedas level 3, jangan pakai bawang"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    <h6 class="fw-bold">Detail Pesanan</h6>
                    <div id="items-list">
                        <div class="row mb-2 item-row">
                            <div class="col-md-6">
                                <select name="menu_id[]" class="form-control" required>
                                    <option value="">Pilih Menu</option>
                                    <?php foreach($menu_list as $menu): ?>
                                        <option value="<?php echo $menu['id']; ?>"><?php echo $menu['nama_menu']; ?> - Rp <?php echo number_format($menu['harga'], 0, ',', '.'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="number" name="quantity[]" class="form-control" placeholder="Jumlah" value="1" min="1" required>
                            </div>
                            <div class="col-md-3">
                                <button type="button" class="btn btn-danger btn-sm w-100" onclick="removeItem(this)">Hapus</button>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-secondary mt-2" onclick="addItem()">
                        <i class="bi bi-plus"></i> Tambah Item
                    </button>
                    <div id="infoStatusOffline" class="alert alert-info mt-2" style="display: none;">
                        <i class="bi bi-info-circle"></i> 
                        <strong>Informasi Status Pembayaran:</strong><br>
                        <span id="statusInfoText">-</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary-custom">Simpan Pesanan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL DETAIL PESANAN -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-info-circle"></i> Detail Pesanan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary"></div> Loading...
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Fungsi untuk membuka modal offline
function openOfflineModal() {
    var container = document.getElementById('items-list');
    container.innerHTML = '';
    addItem();
    updateStatusInfo();
    var modal = new bootstrap.Modal(document.getElementById('offlineModal'));
    modal.show();
}

// Update info status berdasarkan metode pembayaran
function updateStatusInfo() {
    var metode = document.getElementById('metodeBayarOffline').value;
    var infoDiv = document.getElementById('infoStatusOffline');
    var infoText = document.getElementById('statusInfoText');
    
    if(metode == 'cod' || metode == 'tunai') {
        infoDiv.style.display = 'block';
        infoDiv.className = 'alert alert-warning mt-2';
        infoText.innerHTML = 'Metode ' + (metode == 'cod' ? 'COD' : 'Tunai') + ' akan mengakibatkan status pembayaran <strong>BELUM LUNAS</strong>. Pelanggan akan membayar saat pesanan diterima.';
    } else {
        infoDiv.style.display = 'block';
        infoDiv.className = 'alert alert-success mt-2';
        infoText.innerHTML = 'Metode ' + (metode == 'transfer' ? 'Transfer Bank' : (metode == 'e_wallet' ? 'E-Wallet' : 'QRIS')) + ' akan mengakibatkan status pembayaran <strong>LUNAS</strong>. Pembayaran sudah dikonfirmasi.';
    }
}

// Event listener untuk perubahan metode pembayaran
if(document.getElementById('metodeBayarOffline')) {
    document.getElementById('metodeBayarOffline').addEventListener('change', updateStatusInfo);
}

// Fungsi untuk menambah item
function addItem() {
    var container = document.getElementById('items-list');
    var newItem = document.createElement('div');
    newItem.className = 'row mb-2 item-row';
    newItem.innerHTML = `
        <div class="col-md-6">
            <select name="menu_id[]" class="form-control" required>
                <option value="">Pilih Menu</option>
                <?php foreach($menu_list as $menu): ?>
                    <option value="<?php echo $menu['id']; ?>"><?php echo $menu['nama_menu']; ?> - Rp <?php echo number_format($menu['harga'], 0, ',', '.'); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <input type="number" name="quantity[]" class="form-control" placeholder="Jumlah" value="1" min="1" required>
        </div>
        <div class="col-md-3">
            <button type="button" class="btn btn-danger btn-sm w-100" onclick="removeItem(this)">Hapus</button>
        </div>
    `;
    container.appendChild(newItem);
}

// Fungsi untuk menghapus item
function removeItem(btn) {
    var items = document.querySelectorAll('#items-list .item-row');
    if(items.length > 1) {
        btn.closest('.item-row').remove();
    } else {
        alert('Minimal satu item pesanan!');
    }
}

// Fungsi untuk melihat detail pesanan via AJAX
function viewDetail(id) {
    var modal = new bootstrap.Modal(document.getElementById('detailModal'));
    document.getElementById('detailContent').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div> Loading...</div>';
    modal.show();
    
    $.ajax({
        url: 'ajax_detail_pesanan.php?id=' + id,
        method: 'GET',
        success: function(data) {
            document.getElementById('detailContent').innerHTML = data;
        },
        error: function() {
            document.getElementById('detailContent').innerHTML = '<div class="alert alert-danger">Gagal memuat detail pesanan</div>';
        }
    });
}

// Update status pesanan
$(document).ready(function() {
    $('.status-select').change(function() {
        var id = $(this).data('id');
        var status = $(this).val();
        $.post('', {action: 'update_status', id: id, status: status}, function(response) {
            if(response == 'success') {
                Swal.fire('Berhasil!', 'Status pesanan diupdate', 'success').then(() => {
                    location.reload();
                });
            }
        });
    });
});

// Cetak nota
function printNota(id) {
    window.open('print_nota.php?id=' + id, '_blank', 'width=500,height=600');
}

// Validasi form sebelum submit
document.getElementById('offlineForm')?.addEventListener('submit', function(e) {
    var selects = document.querySelectorAll('#items-list select');
    var itemCount = 0;
    for(var i = 0; i < selects.length; i++) {
        if(selects[i].value) {
            itemCount++;
        }
    }
    if(itemCount === 0) {
        e.preventDefault();
        alert('Silakan pilih minimal satu menu!');
    }
});

// Inisialisasi DataTable dengan pengecekan dan opsi retrieve untuk mencegah error re-initialization
$(document).ready(function() {
    if ($.fn.dataTable.isDataTable('#pesananTable')) {
        // Jika DataTable sudah terinisialisasi, hancurkan terlebih dahulu
        $('#pesananTable').DataTable().destroy();
    }
    
    // Inisialisasi DataTable
    $('#pesananTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
        },
        responsive: true,
        scrollX: true,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Semua"]],
        columnDefs: [
            { orderable: false, targets: [8] } // Kolom Aksi tidak bisa diurutkan
        ]
    });
});
</script>

</body>
</html>