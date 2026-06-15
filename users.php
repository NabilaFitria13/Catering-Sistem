<?php
if($_SESSION['role'] != 'admin') { 
    echo "<div class='alert alert-danger'>Akses ditolak! Halaman ini hanya untuk Admin!</div>"; 
    exit(); 
}

// PROSES TAMBAH USER
if(isset($_POST['tambah'])) {
    $username = $_POST['username'];
    $password = md5($_POST['password']);
    $role = $_POST['role'];
    $nama_lengkap = $_POST['nama_lengkap'];
    $email = $_POST['email'];
    $no_telepon = $_POST['no_telepon'];
    $status = $_POST['status'];
    
    // Cek username sudah ada atau belum
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $cek = $stmt->fetch()['total'];
    
    if($cek > 0) {
        echo "<script>alert('Username sudah digunakan!'); window.location.href='?page=users';</script>";
        exit();
    }
    
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role, nama_lengkap, email, no_telepon, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$username, $password, $role, $nama_lengkap, $email, $no_telepon, $status]);
    
    echo "<script>alert('Staff berhasil ditambahkan!'); window.location.href='?page=users';</script>";
    exit();
}

// PROSES EDIT USER
if(isset($_POST['edit'])) {
    $id = $_POST['id'];
    $username = $_POST['username'];
    $role = $_POST['role'];
    $nama_lengkap = $_POST['nama_lengkap'];
    $email = $_POST['email'];
    $no_telepon = $_POST['no_telepon'];
    $status = $_POST['status'];
    
    if(!empty($_POST['password'])) {
        $password = md5($_POST['password']);
        $stmt = $pdo->prepare("UPDATE users SET username=?, password=?, role=?, nama_lengkap=?, email=?, no_telepon=?, status=? WHERE id=?");
        $stmt->execute([$username, $password, $role, $nama_lengkap, $email, $no_telepon, $status, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET username=?, role=?, nama_lengkap=?, email=?, no_telepon=?, status=? WHERE id=?");
        $stmt->execute([$username, $role, $nama_lengkap, $email, $no_telepon, $status, $id]);
    }
    
    echo "<script>alert('Staff berhasil diupdate!'); window.location.href='?page=users';</script>";
    exit();
}

// PROSES HAPUS USER (NONAKTIFKAN)
if(isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    
    $stmt = $pdo->prepare("UPDATE users SET status = 'nonaktif' WHERE id=?");
    $stmt->execute([$id]);
    
    echo "<script>alert('Staff berhasil dinonaktifkan!'); window.location.href='?page=users';</script>";
    exit();
}

// PROSES AKTIFKAN USER
if(isset($_GET['aktifkan'])) {
    $id = $_GET['aktifkan'];
    
    $stmt = $pdo->prepare("UPDATE users SET status = 'aktif' WHERE id=?");
    $stmt->execute([$id]);
    
    echo "<script>alert('Staff berhasil diaktifkan!'); window.location.href='?page=users';</script>";
    exit();
}

// Ambil semua data user
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Kelola Akun Staff</h2>
        <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus-circle"></i> Tambah Staff
        </button>
    </div>
    
    <!-- TABEL DATA STAFF -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered datatable">
                    <thead class="table-dark">
                        <tr class="text-center">
                            <th>Username</th>
                            <th>Nama Lengkap</th>
                            <th width="80">Role</th>
                            <th>Email</th>
                            <th>No. Telepon</th>
                            <th width="80">Status</th>
                            <th>Last Login</th>
                            <th width="160">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($users)): ?>
                        <tr>
                            <td colspan="8" class="text-center">Belum ada data staff</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach($users as $user): ?>
                        <tr>
                            <td><?php echo $user['username']; ?></td>
                            <td><?php echo $user['nama_lengkap']; ?></td>
                            <td class="text-center">
                                <?php if($user['role'] == 'admin'): ?>
                                    <span class="badge bg-danger">Admin</span>
                                <?php else: ?>
                                    <span class="badge bg-info">Kasir</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $user['email']; ?></td>
                            <td><?php echo $user['no_telepon']; ?></td>
                            <td class="text-center">
                                <?php if($user['status'] == 'aktif'): ?>
                                    <span class="badge bg-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><?php echo $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : '-'; ?></td>
                            <td class="text-center">
                                <button class="btn btn-warning btn-sm" onclick="editUser(<?php echo $user['id']; ?>, '<?php echo $user['username']; ?>', '<?php echo $user['role']; ?>', '<?php echo addslashes($user['nama_lengkap']); ?>', '<?php echo $user['email']; ?>', '<?php echo $user['no_telepon']; ?>', '<?php echo $user['status']; ?>')">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                                <?php if($user['status'] == 'aktif'): ?>
                                    <button class="btn btn-danger btn-sm" onclick="nonaktifkanUser(<?php echo $user['id']; ?>, '<?php echo $user['username']; ?>')">
                                        <i class="bi bi-person-x"></i> Nonaktif
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-success btn-sm" onclick="aktifkanUser(<?php echo $user['id']; ?>, '<?php echo $user['username']; ?>')">
                                        <i class="bi bi-person-check"></i> Aktifkan
                                    </button>
                                <?php endif; ?>
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

<!-- MODAL TAMBAH STAFF -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Tambah Staff</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label>Username</label>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label>Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label>Nama Lengkap</label>
                                <input type="text" name="nama_lengkap" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label>Role / Jabatan</label>
                                <select name="role" class="form-control">
                                    <option value="admin">Admin</option>
                                    <option value="kasir">Kasir</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label>No. Telepon</label>
                                <input type="text" name="no_telepon" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="aktif">Aktif</option>
                                    <option value="nonaktif">Nonaktif</option>
                                </select>
                            </div>
                        </div>
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

<!-- MODAL EDIT STAFF -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Edit Staff</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label>Username</label>
                                <input type="text" name="username" id="edit_username" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label>Password (Kosongkan jika tidak diubah)</label>
                                <input type="password" name="password" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label>Nama Lengkap</label>
                                <input type="text" name="nama_lengkap" id="edit_nama" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label>Role / Jabatan</label>
                                <select name="role" id="edit_role" class="form-control">
                                    <option value="admin">Admin</option>
                                    <option value="kasir">Kasir</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label>Email</label>
                                <input type="email" name="email" id="edit_email" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label>No. Telepon</label>
                                <input type="text" name="no_telepon" id="edit_no_telepon" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label>Status</label>
                                <select name="status" id="edit_status" class="form-control">
                                    <option value="aktif">Aktif</option>
                                    <option value="nonaktif">Nonaktif</option>
                                </select>
                            </div>
                        </div>
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
function editUser(id, username, role, nama_lengkap, email, no_telepon, status) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_username').value = username;
    document.getElementById('edit_role').value = role;
    document.getElementById('edit_nama').value = nama_lengkap;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_no_telepon').value = no_telepon;
    document.getElementById('edit_status').value = status;
    
    var editModal = new bootstrap.Modal(document.getElementById('editModal'));
    editModal.show();
}

function nonaktifkanUser(id, username) {
    if(confirm('Yakin ingin menonaktifkan staff "' + username + '"?')) {
        window.location.href = '?page=users&hapus=' + id;
    }
}

function aktifkanUser(id, username) {
    if(confirm('Yakin ingin mengaktifkan staff "' + username + '"?')) {
        window.location.href = '?page=users&aktifkan=' + id;
    }
}
</script>