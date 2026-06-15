<?php
require_once 'config/database.php';

$stmt = $pdo->query("SELECT * FROM pengaturan_toko WHERE id = 1");
$toko = $stmt->fetch();

$stmt = $pdo->query("SELECT * FROM menu WHERE status = 'aktif' ORDER BY kategori, nama_menu");
$all_menus = $stmt->fetchAll();

$menus_by_category = [];
foreach($all_menus as $menu) {
    $menus_by_category[$menu['kategori']][] = $menu;
}

// Inisialisasi cart
if(!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Add to cart
if(isset($_GET['add_to_cart'])) {
    $menu_id = $_GET['add_to_cart'];
    $quantity = isset($_GET['qty']) ? (int)$_GET['qty'] : 1;
    
    if(isset($_SESSION['cart'][$menu_id])) {
        $_SESSION['cart'][$menu_id] += $quantity;
    } else {
        $_SESSION['cart'][$menu_id] = $quantity;
    }
    header('Location: landing.php#menu');
    exit();
}

// Remove from cart
if(isset($_GET['remove'])) {
    unset($_SESSION['cart'][$_GET['remove']]);
    header('Location: landing.php');
    exit();
}

// Update cart
if(isset($_GET['update_qty'])) {
    $menu_id = $_GET['menu_id'];
    $new_qty = (int)$_GET['qty'];
    
    if($new_qty <= 0) {
        unset($_SESSION['cart'][$menu_id]);
    } else {
        $_SESSION['cart'][$menu_id] = $new_qty;
    }
    header('Location: landing.php');
    exit();
}

// Get cart items
$cart_items = [];
$cart_total = 0;
foreach($_SESSION['cart'] as $menu_id => $quantity) {
    $stmt = $pdo->prepare("SELECT * FROM menu WHERE id = ?");
    $stmt->execute([$menu_id]);
    $menu = $stmt->fetch();
    if($menu) {
        $menu['quantity'] = $quantity;
        $menu['subtotal'] = $menu['harga'] * $quantity;
        $cart_items[] = $menu;
        $cart_total += $menu['subtotal'];
    }
}

// Fungsi generate nomor pesanan
function generateOrderNumber($pdo) {
    $date = date('Ymd');
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM pesanan WHERE no_pesanan LIKE ?");
    $stmt->execute(["ORD{$date}%"]);
    $count = $stmt->fetch()['total'];
    $new_number = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    return "ORD{$date}-{$new_number}";
}

// Checkout
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['checkout'])) {
    $nama = $_POST['nama'];
    $wa = $_POST['wa'];
    $alamat = $_POST['alamat'];
    $tanggal_kirim = $_POST['tanggal_kirim'];
    $catatan = $_POST['catatan'];
    $metode_pembayaran = $_POST['metode_pembayaran'];
    
    // Tentukan status pembayaran berdasarkan metode
    // COD dan TUNAI = Belum Lunas (karena bayar nanti)
    // Transfer, E-Wallet, QRIS = Lunas (karena sudah transfer)
    if($metode_pembayaran == 'cod' || $metode_pembayaran == 'tunai') {
        $payment_status = 'belum_bayar';
        $whatsapp_message = "Pesanan akan diproses, pembayaran dilakukan saat pesanan diterima.";
    } else {
        $payment_status = 'lunas';
        $whatsapp_message = "Pembayaran telah diterima, pesanan akan segera diproses.";
    }
    
    // Buat message WhatsApp
    $message = "*PESANAN BARU*%0A";
    $message .= "--------------------%0A";
    $message .= "*Nama:* $nama%0A";
    $message .= "*No. WhatsApp:* $wa%0A";
    $message .= "*Alamat:* $alamat%0A";
    $message .= "*Tanggal Kirim:* $tanggal_kirim%0A";
    $message .= "*Metode Bayar:* " . strtoupper($metode_pembayaran) . "%0A";
    $message .= "*Status Bayar:* " . ($payment_status == 'lunas' ? 'LUNAS' : 'BELUM LUNAS (COD/TUNAI)') . "%0A";
    $message .= "*Catatan:* $catatan%0A";
    $message .= "--------------------%0A";
    $message .= "*DETAIL PESANAN:*%0A";
    
    foreach($cart_items as $item) {
        $message .= "• {$item['nama_menu']} x{$item['quantity']} = Rp " . number_format($item['subtotal'], 0, ',', '.') . "%0A";
    }
    
    $message .= "--------------------%0A";
    $message .= "*TOTAL: Rp " . number_format($cart_total, 0, ',', '.') . "*%0A";
    $message .= "--------------------%0A";
    $message .= $whatsapp_message;
    
    // Simpan ke database
    $no_pesanan = generateOrderNumber($pdo);
    $stmt = $pdo->prepare("INSERT INTO pesanan (no_pesanan, nama_pemesan, no_whatsapp, alamat_pengiriman, tanggal_pemesanan, tanggal_pengiriman, catatan, total_harga, status, payment_status, metode_pembayaran_dipilih, source) VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, 'baru', ?, ?, 'online')");
    $stmt->execute([$no_pesanan, $nama, $wa, $alamat, $tanggal_kirim, $catatan, $cart_total, $payment_status, $metode_pembayaran]);
    $pesanan_id = $pdo->lastInsertId();
    
    foreach($cart_items as $item) {
        $stmt = $pdo->prepare("INSERT INTO detail_pesanan (pesanan_id, menu_id, quantity, harga_satuan, subtotal) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$pesanan_id, $item['id'], $item['quantity'], $item['harga'], $item['subtotal']]);
    }
    
    // Clear cart
    $_SESSION['cart'] = [];
    
    // Redirect ke WhatsApp
    $whatsapp_url = "https://wa.me/" . $toko['no_whatsapp'] . "?text=" . $message;
    echo "<script>window.location.href='$whatsapp_url';</script>";
    exit();
}

$jam_operasional = json_decode($toko['jam_operasional'], true);
$metode_pembayaran = json_decode($toko['metode_pembayaran'], true);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title><?php echo $toko['nama_toko']; ?> - Catering Rumahan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root { --primary: #e67e22; }
        .navbar { background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1); position: fixed; width: 100%; top: 0; z-index: 1000; padding: 12px 0; }
        .navbar-brand { font-weight: 700; font-size: 1.3rem; color: var(--primary) !important; }
        .hero { background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%); min-height: 100vh; display: flex; align-items: center; padding-top: 80px; }
        .btn-order { background: var(--primary); color: white; border-radius: 50px; padding: 10px 25px; border: none; transition: 0.3s; }
        .btn-order:hover { background: #d35400; transform: translateY(-2px); }
        .menu-card { border: none; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); margin-bottom: 20px; transition: 0.3s; }
        .menu-card:hover { transform: translateY(-5px); }
        .menu-price { color: var(--primary); font-size: 1.3rem; font-weight: bold; }
        
        .cart-sidebar { position: fixed; right: -380px; top: 0; width: 380px; height: 100vh; background: white; box-shadow: -5px 0 30px rgba(0,0,0,0.15); z-index: 1050; transition: right 0.3s ease; overflow-y: auto; }
        .cart-sidebar.show { right: 0; }
        .cart-header { background: var(--primary); color: white; padding: 15px 20px; position: sticky; top: 0; }
        .cart-toggle { position: fixed; right: 20px; bottom: 20px; width: 55px; height: 55px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 22px; cursor: pointer; z-index: 1000; box-shadow: 0 3px 12px rgba(0,0,0,0.15); transition: 0.3s; }
        .cart-toggle:hover { transform: scale(1.05); background: #d35400; }
        .cart-badge { position: absolute; top: -5px; right: -5px; background: #e74c3c; color: white; border-radius: 50%; width: 22px; height: 22px; font-size: 11px; display: flex; align-items: center; justify-content: center; }
        .quantity-input { width: 65px; text-align: center; }
        .menu-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
        @media (max-width: 576px) { .cart-sidebar { width: 100%; right: -100%; } .menu-grid { grid-template-columns: 1fr; } .cart-toggle { width: 48px; height: 48px; right: 15px; bottom: 15px; font-size: 18px; } }
        section { padding: 60px 0; }
        .bg-light-custom { background-color: #f8f9fa; }
        .footer { background: #2c3e50; color: white; padding: 30px 0 20px; margin-top: 40px; }
        .cart-item { border-bottom: 1px solid #eee; padding-bottom: 12px; margin-bottom: 12px; }
        .rekening-card { background: #f8f9fa; border-radius: 10px; padding: 12px; margin-bottom: 10px; border-left: 4px solid #e67e22; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="#"><i class="bi bi-shop"></i> <?php echo $toko['nama_toko']; ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <i class="bi bi-list"></i>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="#home">Beranda</a></li>
                <li class="nav-item"><a class="nav-link" href="#about">Tentang</a></li>
                <li class="nav-item"><a class="nav-link" href="#menu">Menu</a></li>
                <li class="nav-item"><a class="nav-link" href="#contact">Kontak</a></li>
                <li class="nav-item"><a class="nav-link btn btn-order text-white px-4 ms-2" href="login.php">Login Staff</a></li>
            </ul>
        </div>
    </div>
</nav>

<section id="home" class="hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 text-white">
                <h1><?php echo $toko['nama_toko']; ?></h1>
                <p class="lead mt-2"><?php echo $toko['deskripsi']; ?></p>
                <div class="mt-4">
                    <a href="#menu" class="btn btn-order">Lihat Menu <i class="bi bi-arrow-right"></i></a>
                    <button class="btn btn-outline-light ms-2" id="viewCartBtn"><i class="bi bi-cart"></i> Keranjang <span class="badge bg-danger" id="cartCount"><?php echo array_sum($_SESSION['cart']); ?></span></button>
                </div>
            </div>
            <div class="col-lg-6 mt-4 mt-lg-0">
                <img src="https://images.unsplash.com/photo-1556911220-bff31c812dba?w=500" class="img-fluid rounded-3 shadow">
            </div>
        </div>
    </div>
</section>

<section id="about" class="bg-light-custom">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <img src="https://images.unsplash.com/photo-1466637574441-749b8f19452f?w=500" class="img-fluid rounded-3 shadow">
            </div>
            <div class="col-lg-6 mt-4 mt-lg-0">
                <h2>Tentang Kami</h2>
                <p><?php echo $toko['tentang']; ?></p>
                <div class="row mt-4">
                    <div class="col-md-6 mb-3">
                        <div class="text-center p-3 bg-white rounded-3 shadow-sm">
                            <i class="bi bi-star-fill fs-2 text-primary"></i>
                            <h5 class="mt-2">Berkualitas</h5>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="text-center p-3 bg-white rounded-3 shadow-sm">
                            <i class="bi bi-clock-history fs-2 text-primary"></i>
                            <h5 class="mt-2">Berpengalaman</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="menu">
    <div class="container">
        <h2 class="text-center mb-4">Menu Pilihan Kami</h2>
        <div class="divider mx-auto mb-4" style="width: 60px; height: 3px; background: var(--primary);"></div>
        
        <?php foreach($menus_by_category as $kategori => $menus): ?>
        <h3 class="mb-3"><?php echo $kategori; ?></h3>
        <div class="menu-grid mb-4">
            <?php foreach($menus as $menu): ?>
            <div class="card menu-card">
                <div class="card-body">
                    <h5><?php echo $menu['nama_menu']; ?></h5>
                    <p class="small text-muted"><?php echo substr($menu['deskripsi'], 0, 70); ?></p>
                    <div class="menu-price">Rp <?php echo number_format($menu['harga'], 0, ',', '.'); ?></div>
                    <div class="mt-3 d-flex gap-2">
                        <input type="number" id="qty_<?php echo $menu['id']; ?>" class="form-control quantity-input" value="1" min="1">
                        <button class="btn btn-order flex-grow-1" onclick="addToCart(<?php echo $menu['id']; ?>)"><i class="bi bi-cart-plus"></i> Pesan</button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<section id="contact" class="bg-light-custom">
    <div class="container">
        <h2 class="text-center mb-4">Informasi Kontak</h2>
        <div class="divider mx-auto mb-4" style="width: 60px; height: 3px; background: var(--primary);"></div>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 text-center p-4">
                    <i class="bi bi-geo-alt fs-2 text-primary"></i>
                    <h5 class="mt-3">Alamat</h5>
                    <p class="mb-0 small"><?php echo nl2br($toko['alamat']); ?></p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 text-center p-4">
                    <i class="bi bi-telephone fs-2 text-primary"></i>
                    <h5 class="mt-3">Telepon / WhatsApp</h5>
                    <p class="mb-0 small"><?php echo $toko['no_telepon']; ?></p>
                    <hr class="my-2">
                    <p class="mb-0 small text-success">WA: <?php echo $toko['no_whatsapp']; ?></p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 text-center p-4">
                    <i class="bi bi-envelope fs-2 text-primary"></i>
                    <h5 class="mt-3">Email</h5>
                    <p class="mb-0 small"><?php echo $toko['email']; ?></p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Cart Sidebar -->
<div class="cart-sidebar" id="cartSidebar">
    <div class="cart-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-cart"></i> Keranjang Belanja</h5>
            <button class="btn-close btn-close-white" id="closeCart"></button>
        </div>
    </div>
    <div class="p-3" id="cartContent">
        <?php if(empty($cart_items)): ?>
        <div class="text-center py-5">
            <i class="bi bi-cart-x fs-1 text-muted"></i>
            <p class="mt-2 text-muted">Keranjang kosong</p>
        </div>
        <?php else: ?>
        <div class="cart-items-list">
            <?php foreach($cart_items as $item): ?>
            <div class="cart-item d-flex justify-content-between align-items-center">
                <div class="flex-grow-1">
                    <h6 class="mb-0"><?php echo $item['nama_menu']; ?></h6>
                    <small class="text-muted">Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?></small>
                </div>
                <div class="d-flex align-items-center">
                    <a href="?update_qty=1&menu_id=<?php echo $item['id']; ?>&qty=<?php echo $item['quantity'] - 1; ?>" class="btn btn-sm btn-outline-secondary" <?php echo $item['quantity'] <= 1 ? 'style="visibility:hidden"' : ''; ?>><i class="bi bi-dash"></i></a>
                    <span class="mx-2" style="min-width: 30px; text-align: center;"><?php echo $item['quantity']; ?></span>
                    <a href="?update_qty=1&menu_id=<?php echo $item['id']; ?>&qty=<?php echo $item['quantity'] + 1; ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-plus"></i></a>
                    <a href="?remove=<?php echo $item['id']; ?>" class="btn btn-sm btn-danger ms-2"><i class="bi bi-trash"></i></a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="mt-3 pt-3 border-top">
            <div class="d-flex justify-content-between mb-3">
                <strong>Total Belanja:</strong>
                <strong class="text-primary">Rp <?php echo number_format($cart_total, 0, ',', '.'); ?></strong>
            </div>
            <button class="btn btn-order w-100" data-bs-toggle="modal" data-bs-target="#checkoutModal">
                <i class="bi bi-whatsapp"></i> Checkout
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="cart-toggle" id="cartToggle">
    <i class="bi bi-cart"></i>
    <span class="cart-badge"><?php echo array_sum($_SESSION['cart']); ?></span>
</div>

<!-- Checkout Modal -->
<div class="modal fade" id="checkoutModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-whatsapp text-success"></i> Data Pemesan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>No. WhatsApp</label>
                        <input type="text" name="wa" class="form-control" placeholder="081234567890" required>
                    </div>
                    <div class="mb-3">
                        <label>Alamat Pengiriman</label>
                        <textarea name="alamat" class="form-control" rows="2" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label>Tanggal Pengiriman</label>
                        <input type="date" name="tanggal_kirim" class="form-control" value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                    </div>
                    <div class="mb-3">
                        <label>Pilih Metode Pembayaran</label>
                        <select name="metode_pembayaran" id="metodeBayar" class="form-control" required>
                            <option value="tunai">💰 Tunai (Bayar di Tempat)</option>
                            <option value="cod">🚚 COD (Bayar di Tempat)</option>
                            <option value="transfer">🏦 Transfer Bank</option>
                            <option value="e_wallet">📱 E-Wallet (OVO, GoPay, Dana)</option>
                            <option value="qris">📱 QRIS</option>
                        </select>
                    </div>
                    <div id="infoTransfer" class="alert alert-info mt-2" style="display: none;">
                        <i class="bi bi-info-circle"></i> 
                        <strong>Informasi Pembayaran:</strong><br>
                        Silakan transfer ke rekening berikut. Pembayaran akan dikonfirmasi otomatis.<br>
                        <?php if($toko['rekening_bca']): ?>
                        <div class="rekening-card mt-2">
                            <strong>🏦 BCA</strong><br>
                            <?php echo $toko['rekening_bca']; ?>
                        </div>
                        <?php endif; ?>
                        <?php if($toko['rekening_mandiri']): ?>
                        <div class="rekening-card">
                            <strong>🏦 Mandiri</strong><br>
                            <?php echo $toko['rekening_mandiri']; ?>
                        </div>
                        <?php endif; ?>
                        <div class="rekening-card">
                            <strong>📱 E-Wallet</strong><br>
                            OVO: <?php echo $toko['ewallet_ovo']; ?><br>
                            GoPay: <?php echo $toko['ewallet_gopay']; ?><br>
                            Dana: <?php echo $toko['ewallet_dana']; ?>
                        </div>
                    </div>
                    <div id="infoCOD" class="alert alert-warning mt-2" style="display: none;">
                        <i class="bi bi-info-circle"></i> 
                        <strong>Pembayaran COD/Tunai</strong><br>
                        Anda akan membayar saat pesanan diterima. Status pembayaran akan "Belum Lunas" hingga pesanan tiba.
                    </div>
                    <div class="mb-3 mt-3">
                        <label>Catatan</label>
                        <textarea name="catatan" class="form-control" rows="2" placeholder="Contoh: pedas level 3, jangan pakai bawang"></textarea>
                    </div>
                    <div class="alert alert-info">
                        <strong>Total: Rp <?php echo number_format($cart_total, 0, ',', '.'); ?></strong>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="checkout" class="btn btn-success"><i class="bi bi-whatsapp"></i> Konfirmasi Pesanan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<footer class="footer text-center">
    <div class="container">
        <p>&copy; 2024 <?php echo $toko['nama_toko']; ?>. All rights reserved.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function addToCart(menuId) { 
    var qty = $('#qty_' + menuId).val(); 
    window.location.href = '?add_to_cart=' + menuId + '&qty=' + qty; 
}

$('#cartToggle, #viewCartBtn').click(function(e) { 
    e.preventDefault(); 
    $('#cartSidebar').addClass('show'); 
});

$('#closeCart').click(function() { 
    $('#cartSidebar').removeClass('show'); 
});

$(document).click(function(event) { 
    if (!$(event.target).closest('#cartSidebar, .cart-toggle, #viewCartBtn').length) { 
        $('#cartSidebar').removeClass('show'); 
    } 
});

$('a[href^="#"]').click(function(e) { 
    e.preventDefault(); 
    var target = $(this.getAttribute('href')); 
    if(target.length) { 
        $('html, body').animate({ scrollTop: target.offset().top - 70 }, 500); 
    } 
});

// Tampilkan info berdasarkan pilihan metode bayar
$(document).ready(function() {
    $('#metodeBayar').change(function() {
        var metode = $(this).val();
        if(metode == 'transfer' || metode == 'e_wallet' || metode == 'qris') {
            $('#infoTransfer').show();
            $('#infoCOD').hide();
        } else if(metode == 'cod' || metode == 'tunai') {
            $('#infoTransfer').hide();
            $('#infoCOD').show();
        } else {
            $('#infoTransfer').hide();
            $('#infoCOD').hide();
        }
    });
    $('#metodeBayar').trigger('change');
});
</script>
</body>
</html>