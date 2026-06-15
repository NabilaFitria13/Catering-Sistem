<?php
require_once 'config/database.php';

if(isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = md5($_POST['password']);
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND password = ? AND status = 'aktif'");
    $stmt->execute([$username, $password]);
    $user = $stmt->fetch();
    
    if($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['nama'] = $user['nama_lengkap'];
        
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        logActivity($pdo, $user['id'], "Login ke sistem");
        
        header('Location: index.php');
        exit();
    } else {
        $error = 'Username atau password salah!';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Manajemen Catering</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .login-card { background: white; border-radius: 15px; padding: 30px; width: 100%; max-width: 400px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
        .btn-login { background: #2c7da0; border: none; color: white; width: 100%; padding: 12px; border-radius: 8px; font-weight: bold; }
        .btn-login:hover { background: #1f5e7a; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center mb-4">
            <h3>🍽️ Dapur Ibu Lala</h3>
            <p class="text-muted">Sistem Manajemen Pesanan & Keuangan</p>
        </div>
        
        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn-login">Login</button>
        </form>
        
        <div class="text-center mt-3">
            <a href="landing.php" class="text-decoration-none">← Kembali ke Beranda</a>
        </div>
        
        <div class="text-center mt-2">
            <small class="text-muted">Demo: admin/admin123 | kasir/kasir123</small>
        </div>
    </div>
</body>
</html>