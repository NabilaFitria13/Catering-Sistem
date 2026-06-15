<?php
// Hanya Admin yang bisa mengakses halaman pengeluaran
if($_SESSION['role'] != 'admin') { 
    echo "<div class='alert alert-danger'>Akses ditolak! Halaman ini hanya untuk Admin!</div>"; 
    exit(); 
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['action'])) {
        if($_POST['action'] == 'add') {
            $stmt = $pdo->prepare("INSERT INTO pengeluaran (tanggal, deskripsi, kategori, jumlah, created_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$_POST['tanggal'], $_POST['deskripsi'], $_POST['kategori'], $_POST['jumlah'], $_SESSION['user_id']]);
            echo "<script>Swal.fire('Berhasil!', 'Pengeluaran ditambahkan', 'success').then(() => { window.location.href='?page=pengeluaran'; });</script>";
            exit();
        }
        if($_POST['action'] == 'edit') {
            $stmt = $pdo->prepare("UPDATE pengeluaran SET tanggal=?, deskripsi=?, kategori=?, jumlah=? WHERE id=?");
            $stmt->execute([$_POST['tanggal'], $_POST['deskripsi'], $_POST['kategori'], $_POST['jumlah'], $_POST['id']]);
            echo "<script>Swal.fire('Berhasil!', 'Pengeluaran diupdate', 'success').then(() => { window.location.href='?page=pengeluaran'; });</script>";
            exit();
        }
        if($_POST['action'] == 'delete') {
            $stmt = $pdo->prepare("DELETE FROM pengeluaran WHERE id=?");
            $stmt->execute([$_POST['id']]);
            echo "<script>Swal.fire('Berhasil!', 'Pengeluaran dihapus', 'success').then(() => { window.location.href='?page=pengeluaran'; });</script>";
            exit();
        }
    }
}

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

$stmt = $pdo->prepare("SELECT * FROM pengeluaran WHERE tanggal BETWEEN ? AND ? ORDER BY tanggal DESC");
$stmt->execute([$start_date, $end_date]);
$expenses = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT kategori, SUM(jumlah) as total FROM pengeluaran WHERE tanggal BETWEEN ? AND ? GROUP BY kategori");
$stmt->execute([$start_date, $end_date]);
$category_totals = $stmt->fetchAll();

$total_expense = array_sum(array_column($expenses, 'jumlah'));

$edit_expense = null;
if(isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM pengeluaran WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_expense = $stmt->fetch();
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between mb-4">
        <h2>Manajemen Pengeluaran</h2>
        <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-circle"></i> Tambah Pengeluaran</button>
    </div>
    
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <input type="hidden" name="page" value="pengeluaran">
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
    
    <div class="row">
        <div class="col-12 col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h5>Daftar Pengeluaran</h5>
                    <button class="btn btn-sm btn-success" onclick="exportToExcel('expenseTable', 'pengeluaran_<?php echo $start_date; ?>_<?php echo $end_date; ?>')">
                        <i class="bi bi-file-excel"></i> Export Excel
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover datatable" id="expenseTable">
                            <thead>
                                <tr><th>Tanggal</th><th>Deskripsi</th><th>Kategori</th><th class="text-end">Jumlah</th><th>Aksi</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach($expenses as $expense): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($expense['tanggal'])); ?></td>
                                    <td><?php echo substr($expense['deskripsi'], 0, 40); ?>...</div>
                                    <td><?php echo $expense['kategori']; ?></div>
                                    <td class="text-end">Rp <?php echo number_format($expense['jumlah'], 0, ',', '.'); ?></div>
                                    <td>
                                        <a href="?page=pengeluaran&edit=<?php echo $expense['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                        <button class="btn btn-danger btn-sm" onclick="deleteExpense(<?php echo $expense['id']; ?>)">Hapus</button>
                                     </div>
                                  </div>
                                </div>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td colspan="3" class="text-end">Total: </div>
                                    <td class="text-end">Rp <?php echo number_format($total_expense, 0, ',', '.'); ?></div>
                                    <td></div>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>Ringkasan per Kategori</h5>
                </div>
                <div class="card-body">
                    <?php foreach($category_totals as $cat): ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span><?php echo $cat['kategori']; ?></span>
                        <span class="fw-bold">Rp <?php echo number_format($cat['total'], 0, ',', '.'); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Pengeluaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3"><label>Tanggal</label><input type="date" name="tanggal" class="form-control" value="<?php echo date('Y-m-d'); ?>" required></div>
                    <div class="mb-3"><label>Deskripsi</label><textarea name="deskripsi" class="form-control" rows="2" required></textarea></div>
                    <div class="mb-3"><label>Kategori</label>
                        <select name="kategori" class="form-control">
                            <option value="Bahan Baku">Bahan Baku</option>
                            <option value="Kemasan">Kemasan</option>
                            <option value="Transportasi">Transportasi</option>
                            <option value="Gaji Karyawan">Gaji Karyawan</option>
                            <option value="Operasional">Operasional</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div class="mb-3"><label>Jumlah (Rp)</label><input type="number" name="jumlah" class="form-control" required></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary-custom">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit -->
<?php if($edit_expense): ?>
<div class="modal fade" id="editModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Pengeluaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="window.location.href='?page=pengeluaran'"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" value="<?php echo $edit_expense['id']; ?>">
                    <div class="mb-3"><label>Tanggal</label><input type="date" name="tanggal" class="form-control" value="<?php echo $edit_expense['tanggal']; ?>" required></div>
                    <div class="mb-3"><label>Deskripsi</label><textarea name="deskripsi" class="form-control" rows="2" required><?php echo htmlspecialchars($edit_expense['deskripsi']); ?></textarea></div>
                    <div class="mb-3"><label>Kategori</label>
                        <select name="kategori" class="form-control">
                            <option value="Bahan Baku" <?php echo $edit_expense['kategori'] == 'Bahan Baku' ? 'selected' : ''; ?>>Bahan Baku</option>
                            <option value="Kemasan" <?php echo $edit_expense['kategori'] == 'Kemasan' ? 'selected' : ''; ?>>Kemasan</option>
                            <option value="Transportasi" <?php echo $edit_expense['kategori'] == 'Transportasi' ? 'selected' : ''; ?>>Transportasi</option>
                            <option value="Gaji Karyawan" <?php echo $edit_expense['kategori'] == 'Gaji Karyawan' ? 'selected' : ''; ?>>Gaji Karyawan</option>
                            <option value="Operasional" <?php echo $edit_expense['kategori'] == 'Operasional' ? 'selected' : ''; ?>>Operasional</option>
                            <option value="Lainnya" <?php echo $edit_expense['kategori'] == 'Lainnya' ? 'selected' : ''; ?>>Lainnya</option>
                        </select>
                    </div>
                    <div class="mb-3"><label>Jumlah (Rp)</label><input type="number" name="jumlah" class="form-control" value="<?php echo $edit_expense['jumlah']; ?>" required></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='?page=pengeluaran'">Batal</button>
                    <button type="submit" class="btn btn-primary-custom">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>new bootstrap.Modal(document.getElementById('editModal')).show();</script>
<?php endif; ?>

<script>
function deleteExpense(id) {
    Swal.fire({
        title: 'Hapus Pengeluaran?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Ya, hapus!'
    }).then((result) => {
        if(result.isConfirmed) {
            $('<form method="POST"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="'+id+'"></form>').appendTo('body').submit();
        }
    });
}
</script>