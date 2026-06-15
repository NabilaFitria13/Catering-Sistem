<?php
if($_SESSION['role'] != 'admin') { 
    echo "<div class='alert alert-danger'>Akses ditolak! Halaman ini hanya untuk Admin!</div>"; 
    exit(); 
}

// PROSES TAMBAH KATEGORI
if(isset($_POST['tambah'])) {
    $nama = $_POST['nama_kategori'];
    $deskripsi = $_POST['deskripsi'];
    $status = $_POST['status'];
    
    $stmt = $pdo->prepare("INSERT INTO kategori_menu (nama_kategori, deskripsi, status) VALUES (?, ?, ?)");
    $stmt->execute([$nama, $deskripsi, $status]);
    
    echo "<script>alert('Kategori berhasil ditambahkan!'); window.location.href='?page=kategori_menu';</script>";
    exit();
}

// PROSES EDIT KATEGORI
if(isset($_POST['edit'])) {
    $id = $_POST['id'];
    $nama = $_POST['nama_kategori'];
    $deskripsi = $_POST['deskripsi'];
    $status = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE kategori_menu SET nama_kategori=?, deskripsi=?, status=? WHERE id=?");
    $stmt->execute([$nama, $deskripsi, $status, $id]);
    
    echo "<script>alert('Kategori berhasil diupdate!'); window.location.href='?page=kategori_menu';</script>";
    exit();
}

// PROSES HAPUS KATEGORI
if(isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    
    // Cek apakah kategori digunakan di menu
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM menu WHERE kategori_id = ?");
    $stmt->execute([$id]);
    $used = $stmt->fetch()['total'];
    
    if($used > 0) {
        echo "<script>alert('Kategori tidak bisa dihapus karena masih digunakan di menu!'); window.location.href='?page=kategori_menu';</script>";
        exit();
    }
    
    $stmt = $pdo->prepare("DELETE FROM kategori_menu WHERE id=?");
    $stmt->execute([$id]);
    
    echo "<script>alert('Kategori berhasil dihapus!'); window.location.href='?page=kategori_menu';</script>";
    exit();
}

// Ambil semua data kategori
$stmt = $pdo->query("SELECT * FROM kategori_menu ORDER BY id DESC");
$kategori = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Kelola Kategori Menu</h2>
        <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus-circle"></i> Tambah Kategori
        </button>
    </div>
    
    <!-- TABEL DATA KATEGORI -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered datatable">
                    <thead class="table-dark">
                        <tr class="text-center">
                            <th width="50">ID</th>
                            <th>Nama Kategori</th>
                            <th>Deskripsi</th>
                            <th width="100">Status</th>
                            <th width="150">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($kategori)): ?>
                        <tr>
                            <td colspan="5" class="text-center">Belum ada data kategori</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach($kategori as $k): ?>
                        <tr>
                            <td class="text-center"><?php echo $k['id']; ?></td>
                            <td><?php echo htmlspecialchars($k['nama_kategori']); ?></td>
                            <td><?php echo htmlspecialchars($k['deskripsi']); ?></td>
                            <td class="text-center">
                                <?php if($k['status'] == 'aktif'): ?>
                                    <span class="badge bg-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-warning btn-sm" onclick="editKategori(<?php echo $k['id']; ?>, '<?php echo htmlspecialchars($k['nama_kategori']); ?>', '<?php echo htmlspecialchars($k['deskripsi']); ?>', '<?php echo $k['status']; ?>')">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="hapusKategori(<?php echo $k['id']; ?>, '<?php echo htmlspecialchars($k['nama_kategori']); ?>')">
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

<!-- MODAL TAMBAH KATEGORI -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Tambah Kategori</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Nama Kategori</label>
                        <input type="text" name="nama_kategori" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Deskripsi</label>
                        <textarea name="deskripsi" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
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

<!-- MODAL EDIT KATEGORI -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Edit Kategori</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label>Nama Kategori</label>
                        <input type="text" name="nama_kategori" id="edit_nama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Deskripsi</label>
                        <textarea name="deskripsi" id="edit_deskripsi" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label>Status</label>
                        <select name="status" id="edit_status" class="form-control">
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
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
function editKategori(id, nama, deskripsi, status) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nama').value = nama;
    document.getElementById('edit_deskripsi').value = deskripsi;
    document.getElementById('edit_status').value = status;
    
    var editModal = new bootstrap.Modal(document.getElementById('editModal'));
    editModal.show();
}

function hapusKategori(id, nama) {
    if(confirm('Yakin ingin menghapus kategori "' + nama + '"?')) {
        window.location.href = '?page=kategori_menu&hapus=' + id;
    }
}
</script>