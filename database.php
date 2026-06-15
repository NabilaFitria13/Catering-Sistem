<?php
$host = 'localhost';
$dbname = 'catering_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    function logActivity($pdo, $user_id, $aktivitas) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $stmt = $pdo->prepare("INSERT INTO log_aktivitas (user_id, aktivitas, ip_address) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $aktivitas, $ip]);
    }
    
    function hasPermission($allowed_roles) {
        if(!isset($_SESSION['user_id'])) return false;
        return in_array($_SESSION['role'], $allowed_roles);
    }
    
} catch(PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}
?>