<?php
if($_SESSION['role'] != 'admin') { echo "<div class='alert alert-danger'>Akses ditolak!</div>"; exit(); }

$stmt = $pdo->query("SELECT * FROM pengaturan_toko WHERE id = 1");
$setting = $stmt->fetch();

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $stmt = $pdo->prepare("UPDATE pengaturan_toko SET nama_toko=?, deskripsi=?, tentang=?, alamat=?, no_telepon=?, no_whatsapp=?, email=?, rekening_bca=?, rekening_mandiri=?, ewallet_ovo=?, ewallet_gopay=?, ewallet_dana=?, jam_operasional=?, metode_pembayaran=? WHERE id=1");
    $stmt->execute([
        $_POST['nama_toko'], $_POST['deskripsi'], $_POST['tentang'], 
        $_POST['alamat'], $_POST['no_telepon'], $_POST['no_whatsapp'], 
        $_POST['email'], $_POST['rekening_bca'], $_POST['rekening_mandiri'],
        $_POST['ewallet_ovo'], $_POST['ewallet_gopay'], $_POST['ewallet_dana'],
        $_POST['jam_operasional'], $_POST['metode_pembayaran']
    ]);
    echo "<script>Swal.fire('Berhasil!', 'Pengaturan disimpan', 'success').then(() => { window.location.href='?page=pengaturan'; });</script>";
}
?>

<div class="container-fluid">
    <h2 class="mb-4">Pengaturan Sistem</h2>
    
    <div class="card">
        <div class="card-header">
            <h5>Informasi Toko</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6"><div class="mb-3"><label>Nama Toko</label><input type="text" name="nama_toko" class="form-control" value="<?php echo $setting['nama_toko']; ?>" required></div></div>
                    <div class="col-md-6"><div class="mb-3"><label>No. WhatsApp</label><input type="text" name="no_whatsapp" class="form-control" value="<?php echo $setting['no_whatsapp']; ?>"></div></div>
                    <div class="col-md-6"><div class="mb-3"><label>No. Telepon</label><input type="text" name="no_telepon" class="form-control" value="<?php echo $setting['no_telepon']; ?>"></div></div>
                    <div class="col-md-6"><div class="mb-3"><label>Email</label><input type="email" name="email" class="form-control" value="<?php echo $setting['email']; ?>"></div></div>
                    <div class="col-12"><div class="mb-3"><label>Deskripsi Singkat</label><textarea name="deskripsi" class="form-control" rows="2"><?php echo htmlspecialchars($setting['deskripsi']); ?></textarea></div></div>
                    <div class="col-12"><div class="mb-3"><label>Tentang Toko</label><textarea name="tentang" class="form-control" rows="4"><?php echo htmlspecialchars($setting['tentang']); ?></textarea></div></div>
                    <div class="col-12"><div class="mb-3"><label>Alamat</label><textarea name="alamat" class="form-control" rows="2"><?php echo htmlspecialchars($setting['alamat']); ?></textarea></div></div>
                    
                    <div class="col-12"><h6 class="mt-3">Rekening Bank</h6></div>
                    <div class="col-md-6"><div class="mb-3"><label>BCA</label><input type="text" name="rekening_bca" class="form-control" value="<?php echo $setting['rekening_bca']; ?>"></div></div>
                    <div class="col-md-6"><div class="mb-3"><label>Mandiri</label><input type="text" name="rekening_mandiri" class="form-control" value="<?php echo $setting['rekening_mandiri']; ?>"></div></div>
                    
                    <div class="col-12"><h6 class="mt-3">E-Wallet</h6></div>
                    <div class="col-md-4"><div class="mb-3"><label>OVO</label><input type="text" name="ewallet_ovo" class="form-control" value="<?php echo $setting['ewallet_ovo']; ?>"></div></div>
                    <div class="col-md-4"><div class="mb-3"><label>GoPay</label><input type="text" name="ewallet_gopay" class="form-control" value="<?php echo $setting['ewallet_gopay']; ?>"></div></div>
                    <div class="col-md-4"><div class="mb-3"><label>Dana</label><input type="text" name="ewallet_dana" class="form-control" value="<?php echo $setting['ewallet_dana']; ?>"></div></div>
                    
                    <div class="col-12"><div class="mb-3"><label>Jam Operasional (JSON)</label><textarea name="jam_operasional" class="form-control" rows="3"><?php echo htmlspecialchars($setting['jam_operasional']); ?></textarea></div></div>
                    <div class="col-12"><div class="mb-3"><label>Metode Pembayaran (JSON)</label><textarea name="metode_pembayaran" class="form-control" rows="3"><?php echo htmlspecialchars($setting['metode_pembayaran']); ?></textarea></div></div>
                </div>
                <button type="submit" class="btn btn-primary-custom">Simpan Pengaturan</button>
            </form>
        </div>
    </div>
    
    <div class="card mt-4">
        <div class="card-header">
            <h5>Informasi Sistem</h5>
        </div>
        <div class="card-body">
            <table class="table table-sm">
                <tr><th>Versi Sistem</th><td>2.0.0</div></tr>
                <tr><th>Terakhir Update</th><td><?php echo $setting['updated_at']; ?></div></tr>
                <tr><th>Database</th><td>MySQL</div></tr>
                <tr><th>PHP Version</th><td><?php echo phpversion(); ?></div></tr>
            </table>
        </div>
    </div>
</div>