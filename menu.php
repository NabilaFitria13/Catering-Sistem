<?php
if($_SESSION['role'] != 'admin') { 
    echo "<div class='alert alert-danger'>Akses ditolak! Halaman ini hanya untuk Admin!</div>"; 
    exit(); 
}

// Ambil kategori untuk dropdown
$stmt = $pdo->query("SELECT * FROM kategori_menu WHERE status = 'aktif'");
$kategori_list = $stmt->fetchAll();

// PROSES TAMBAH MENU
if(isset($_POST['tambah'])) {
    $nama_menu = $_POST['nama_menu'];
    $kategori_id = $_POST['kategori_id'];
    $harga = $_POST['harga'];
    $deskripsi = $_POST['deskripsi'];
    $stok = $_POST['stok'];
    $status = $_POST['status'];
    
    // Ambil nama kategori
    $stmt = $pdo->prepare("SELECT nama_kategori FROM kategori_menu WHERE id = ?");
    $stmt->execute([$kategori_id]);
    $kategori = $stmt->fetch();
    $kategori_nama = $kategori ? $kategori['nama_kategori'] : '';
    
    $stmt = $pdo->prepare("INSERT INTO menu (nama_menu, kategori_id, kategori, harga, deskripsi, stok, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$nama_menu, $kategori_id, $kategori_nama, $harga, $deskripsi, $stok, $status]);
    
    echo "<script>alert('Menu berhasil ditambahkan!'); window.location.href='?page=menu';</script>";
    exit();
}

// PROSES EDIT MENU
if(isset($_POST['edit'])) {
    $id = $_POST['id'];
    $nama_menu = $_POST['nama_menu'];
    $kategori_id = $_POST['kategori_id'];
    $harga = $_POST['harga'];
    $deskripsi = $_POST['deskripsi'];
    $stok = $_POST['stok'];
    $status = $_POST['status'];
    
    // Ambil nama kategori
    $stmt = $pdo->prepare("SELECT nama_kategori FROM kategori_menu WHERE id = ?");
    $stmt->execute([$kategori_id]);
    $kategori = $stmt->fetch();
    $kategori_nama = $kategori ? $kategori['nama_kategori'] : '';
    
    $stmt = $pdo->prepare("UPDATE menu SET nama_menu=?, kategori_id=?, kategori=?, harga=?, deskripsi=?, stok=?, status=? WHERE id=?");
    $stmt->execute([$nama_menu, $kategori_id, $kategori_nama, $harga, $deskripsi, $stok, $status, $id]);
    
    echo "<script>alert('Menu berhasil diupdate!'); window.location.href='?page=menu';</script>";
    exit();
}

// PROSES HAPUS MENU
if(isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    
    $stmt = $pdo->prepare("DELETE FROM menu WHERE id=?");
    $stmt->execute([$id]);
    
    echo "<script>alert('Menu berhasil dihapus!'); window.location.href='?page=menu';</script>";
    exit();
}

// Ambil semua data menu
$stmt = $pdo->query("SELECT m.*, k.nama_kategori as kategori_nama FROM menu m LEFT JOIN kategori_menu k ON m.kategori_id = k.id ORDER BY k.nama_kategori, m.nama_menu");
$menus = $stmt->fetchAll();

// Data untuk edit
$edit_menu = null;
if(isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM menu WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_menu = $stmt->fetch();
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Kelola Menu</h2>
        <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus-circle"></i> Tambah Menu
        </button>
    </div>
    
    <!-- TABEL DATA MENU -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered datatable">
                    <thead class="table-dark">
                        <tr class="text-center">
                            <th>Kode</th>
                            <th>Nama Menu</th>
                            <th>Kategori</th>
                            <th>Harga</th>
                            <th>Stok</th>
                            <th>Status</th>
                            <th width="150">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($menus)): ?>
                        <tr>
                            <td colspan="7" class="text-center">Belum ada data menu</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach($menus as $menu): ?>
                        <tr>
                            <td><?php echo $menu['kode_menu']; ?></td>
                            <td><?php echo htmlspecialchars($menu['nama_menu']); ?></td>
                            <td><?php echo $menu['kategori_nama']; ?></td>
                            <td>Rp <?php echo number_format($menu['harga'], 0, ',', '.'); ?></td>
                            <td><?php echo $menu['stok']; ?></td>
                            <td>
                                <?php if($menu['status'] == 'aktif'): ?>
                                    <span class="badge bg-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Nonaktif</span>
                                <?php endif; ?>
                             </td>
                             <td class="text-center">
                                <button class="btn btn-warning btn-sm" onclick="editMenu(<?php echo $menu['id']; ?>, '<?php echo htmlspecialchars($menu['nama_menu']); ?>', <?php echo $menu['kategori_id']; ?>, <?php echo $menu['harga']; ?>, <?php echo $menu['stok']; ?>, '<?php echo htmlspecialchars($menu['deskripsi']); ?>', '<?php echo $menu['status']; ?>')">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="hapusMenu(<?php echo $menu['id']; ?>, '<?php echo htmlspecialchars($menu['nama_menu']); ?>')">
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

<!-- MODAL TAMBAH MENU -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Tambah Menu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Nama Menu</label>
                        <input type="text" name="nama_menu" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Kategori</label>
                        <select name="kategori_id" class="form-control" required>
                            <option value="">Pilih Kategori</option>
                            <?php foreach($kategori_list as $k): ?>
                                <option value="<?php echo $k['id']; ?>"><?php echo $k['nama_kategori']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Harga (Rp)</label>
                        <input type="number" name="harga" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Stok</label>
                        <input type="number" name="stok" class="form-control" value="0">
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

<!-- MODAL EDIT MENU -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Edit Menu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">
                    <input type="hidden" name="edit" value="1">
                    <div class="mb-3">
                        <label>Nama Menu</label>
                        <input type="text" name="nama_menu" id="edit_nama_menu" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Kategori</label>
                        <select name="kategori_id" id="edit_kategori_id" class="form-control" required>
                            <option value="">Pilih Kategori</option>
                            <?php foreach($kategori_list as $k): ?>
                                <option value="<?php echo $k['id']; ?>"><?php echo $k['nama_kategori']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Harga (Rp)</label>
                        <input type="number" name="harga" id="edit_harga" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Stok</label>
                        <input type="number" name="stok" id="edit_stok" class="form-control">
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
                    <button type="submit" class="btn btn-primary-custom">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editMenu(id, nama, kategori_id, harga, stok, deskripsi, status) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nama_menu').value = nama;
    document.getElementById('edit_kategori_id').value = kategori_id;
    document.getElementById('edit_harga').value = harga;
    document.getElementById('edit_stok').value = stok;
    document.getElementById('edit_deskripsi').value = deskripsi;
    document.getElementById('edit_status').value = status;
    
    var editModal = new bootstrap.Modal(document.getElementById('editModal'));
    editModal.show();
}

function hapusMenu(id, nama) {
    if(confirm('Yakin ingin menghapus menu "' + nama + '"?')) {
        window.location.href = '?page=menu&hapus=' + id;
    }
}
</script>