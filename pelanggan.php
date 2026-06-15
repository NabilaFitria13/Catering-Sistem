<?php
if(!hasPermission(['admin', 'kasir'])) { 
    echo "<div class='alert alert-danger'>Akses ditolak!</div>"; 
    exit(); 
}

// PROSES TAMBAH PELANGGAN
if(isset($_POST['tambah'])) {
    $nama = $_POST['nama'];
    $no_telepon = $_POST['no_telepon'];
    $alamat = $_POST['alamat'];
    $email = $_POST['email'];
    
    $stmt = $pdo->prepare("INSERT INTO pelanggan (nama, no_telepon, alamat, email) VALUES (?, ?, ?, ?)");
    $stmt->execute([$nama, $no_telepon, $alamat, $email]);
    
    echo "<script>alert('Pelanggan berhasil ditambahkan!'); window.location.href='?page=pelanggan';</script>";
    exit();
}

// PROSES EDIT PELANGGAN
if(isset($_POST['edit'])) {
    $id = $_POST['id'];
    $nama = $_POST['nama'];
    $no_telepon = $_POST['no_telepon'];
    $alamat = $_POST['alamat'];
    $email = $_POST['email'];
    
    $stmt = $pdo->prepare("UPDATE pelanggan SET nama=?, no_telepon=?, alamat=?, email=? WHERE id=?");
    $stmt->execute([$nama, $no_telepon, $alamat, $email, $id]);
    
    echo "<script>alert('Pelanggan berhasil diupdate!'); window.location.href='?page=pelanggan';</script>";
    exit();
}

// PROSES HAPUS PELANGGAN
if(isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    
    $stmt = $pdo->prepare("DELETE FROM pelanggan WHERE id=?");
    $stmt->execute([$id]);
    
    echo "<script>alert('Pelanggan berhasil dihapus!'); window.location.href='?page=pelanggan';</script>";
    exit();
}

// Ambil semua data pelanggan
$stmt = $pdo->query("SELECT * FROM pelanggan ORDER BY created_at DESC");
$pelanggan = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Kelola Pelanggan</h2>
        <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus-circle"></i> Tambah Pelanggan
        </button>
    </div>
    
    <!-- TABEL DATA PELANGGAN -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered datatable">
                    <thead class="table-dark">
                        <tr class="text-center">
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>No. Telepon</th>
                            <th>Email</th>
                            <th>Alamat</th>
                            <th width="100">Total Transaksi</th>
                            <th width="120">Total Belanja</th>
                            <th width="150">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($pelanggan)): ?>
                        <tr>
                            <td colspan="8" class="text-center">Belum ada data pelanggan</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach($pelanggan as $p): ?>
                        <tr>
                            <td class="text-center"><?php echo $p['kode_pelanggan']; ?></td>
                            <td><?php echo htmlspecialchars($p['nama']); ?></td>
                            <td><?php echo $p['no_telepon']; ?></td>
                            <td><?php echo $p['email']; ?></td>
                            <td><?php echo substr($p['alamat'], 0, 50); ?>...</td>
                            <td class="text-center"><?php echo $p['total_transaksi']; ?> x</td>
                            <td class="text-end">Rp <?php echo number_format($p['total_belanja'], 0, ',', '.'); ?></td>
                            <td class="text-center">
                                <button class="btn btn-warning btn-sm" onclick="editPelanggan(<?php echo $p['id']; ?>, '<?php echo addslashes($p['nama']); ?>', '<?php echo $p['no_telepon']; ?>', '<?php echo addslashes($p['alamat']); ?>', '<?php echo $p['email']; ?>')">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="hapusPelanggan(<?php echo $p['id']; ?>, '<?php echo addslashes($p['nama']); ?>')">
                                    <i class="bi bi-trash"></i> Hapus
                                </button>
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

<!-- MODAL TAMBAH PELANGGAN -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Tambah Pelanggan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>No. Telepon</label>
                        <input type="text" name="no_telepon" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>Alamat</label>
                        <textarea name="alamat" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah" class="btn btn-primary-custom">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL EDIT PELANGGAN -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Edit Pelanggan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label>Nama Lengkap</label>
                        <input type="text" name="nama" id="edit_nama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>No. Telepon</label>
                        <input type="text" name="no_telepon" id="edit_no_telepon" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" name="email" id="edit_email" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>Alamat</label>
                        <textarea name="alamat" id="edit_alamat" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="edit" class="btn btn-primary-custom">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editPelanggan(id, nama, no_telepon, alamat, email) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nama').value = nama;
    document.getElementById('edit_no_telepon').value = no_telepon;
    document.getElementById('edit_alamat').value = alamat;
    document.getElementById('edit_email').value = email;
    
    var editModal = new bootstrap.Modal(document.getElementById('editModal'));
    editModal.show();
}

function hapusPelanggan(id, nama) {
    if(confirm('Yakin ingin menghapus pelanggan "' + nama + '"?')) {
        window.location.href = '?page=pelanggan&hapus=' + id;
    }
}
</script>