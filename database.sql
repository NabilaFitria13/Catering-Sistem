DROP DATABASE IF EXISTS catering_system;
CREATE DATABASE catering_system;
USE catering_system;

-- =====================================================
-- 1. TABEL USERS (Admin & Kasir)
-- =====================================================
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'kasir') DEFAULT 'kasir',
    nama_lengkap VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    no_telepon VARCHAR(20),
    status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- 2. TABEL PENGATURAN TOKO
-- =====================================================
CREATE TABLE pengaturan_toko (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_toko VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    tentang TEXT,
    alamat TEXT,
    no_telepon VARCHAR(20),
    no_whatsapp VARCHAR(20),
    email VARCHAR(100),
    rekening_bca VARCHAR(100),
    rekening_mandiri VARCHAR(100),
    rekening_bri VARCHAR(100),
    ewallet_ovo VARCHAR(50),
    ewallet_gopay VARCHAR(50),
    ewallet_dana VARCHAR(50),
    qris_image VARCHAR(255),
    jam_operasional TEXT,
    metode_pembayaran TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =====================================================
-- 3. TABEL KATEGORI MENU
-- =====================================================
CREATE TABLE kategori_menu (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_kategori VARCHAR(50) NOT NULL,
    deskripsi TEXT,
    status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- 4. TABEL MENU
-- =====================================================
CREATE TABLE menu (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kode_menu VARCHAR(20) UNIQUE,
    nama_menu VARCHAR(100) NOT NULL,
    kategori_id INT,
    kategori VARCHAR(50),
    harga DECIMAL(10,2) NOT NULL,
    harga_diskon DECIMAL(10,2) DEFAULT NULL,
    deskripsi TEXT,
    gambar VARCHAR(255),
    stok INT DEFAULT 0,
    status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kategori_id) REFERENCES kategori_menu(id) ON DELETE SET NULL
);

-- =====================================================
-- 5. TABEL PELANGGAN
-- =====================================================
CREATE TABLE pelanggan (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kode_pelanggan VARCHAR(20) UNIQUE,
    nama VARCHAR(100) NOT NULL,
    no_telepon VARCHAR(20),
    alamat TEXT,
    email VARCHAR(100),
    total_transaksi INT DEFAULT 0,
    total_belanja DECIMAL(15,2) DEFAULT 0,
    terakhir_transaksi DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- 6. TABEL PESANAN
-- =====================================================
CREATE TABLE pesanan (
    id INT PRIMARY KEY AUTO_INCREMENT,
    no_pesanan VARCHAR(20) UNIQUE NOT NULL,
    pelanggan_id INT,
    user_id INT,
    nama_pemesan VARCHAR(100) NOT NULL,
    no_whatsapp VARCHAR(20) NOT NULL,
    alamat_pengiriman TEXT,
    tanggal_pemesanan DATETIME NOT NULL,
    tanggal_pengiriman DATE,
    catatan TEXT,
    total_harga DECIMAL(15,2) NOT NULL,
    diskon DECIMAL(10,2) DEFAULT 0,
    biaya_pengiriman DECIMAL(10,2) DEFAULT 0,
    grand_total DECIMAL(15,2) NOT NULL,
    status ENUM('baru', 'diproses', 'dikirim', 'selesai', 'batal') DEFAULT 'baru',
    payment_status ENUM('belum_bayar', 'menunggu_verifikasi', 'lunas') DEFAULT 'belum_bayar',
    metode_pembayaran_dipilih ENUM('cod', 'transfer', 'e_wallet', 'qris') DEFAULT 'transfer',
    bukti_pembayaran VARCHAR(255),
    source ENUM('online', 'offline') DEFAULT 'online',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pelanggan_id) REFERENCES pelanggan(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- =====================================================
-- 7. TABEL DETAIL PESANAN (DIPERBAIKI)
-- =====================================================
CREATE TABLE detail_pesanan (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pesanan_id INT NOT NULL,
    menu_id INT NOT NULL,
    quantity INT NOT NULL,
    harga_satuan DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(15,2) NOT NULL,
    FOREIGN KEY (pesanan_id) REFERENCES pesanan(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_id) REFERENCES menu(id) ON DELETE CASCADE
);

-- =====================================================
-- 8. TABEL PEMBAYARAN
-- =====================================================
CREATE TABLE pembayaran (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pesanan_id INT NOT NULL,
    no_invoice VARCHAR(20) UNIQUE,
    tanggal_pembayaran DATETIME NOT NULL,
    jumlah_dibayar DECIMAL(15,2) NOT NULL,
    metode_pembayaran ENUM('tunai', 'transfer_bank', 'e_wallet', 'qris') DEFAULT 'tunai',
    bukti_pembayaran VARCHAR(255),
    status ENUM('pending', 'lunas', 'gagal') DEFAULT 'pending',
    catatan TEXT,
    verified_by INT,
    verified_at DATETIME,
    FOREIGN KEY (pesanan_id) REFERENCES pesanan(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- 9. TABEL PENGELUARAN
-- =====================================================
CREATE TABLE pengeluaran (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tanggal DATE NOT NULL,
    deskripsi TEXT NOT NULL,
    kategori VARCHAR(50),
    jumlah DECIMAL(15,2) NOT NULL,
    bukti VARCHAR(255),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- 10. TABEL LOG AKTIVITAS
-- =====================================================
CREATE TABLE log_aktivitas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    aktivitas TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- 11. TABEL NOTIFIKASI
-- =====================================================
CREATE TABLE notifikasi (
    id INT PRIMARY KEY AUTO_INCREMENT,
    judul VARCHAR(100) NOT NULL,
    pesan TEXT NOT NULL,
    link VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- TRIGGERS
-- =====================================================

-- Trigger untuk nomor pesanan otomatis
DELIMITER //
CREATE TRIGGER generate_no_pesanan
BEFORE INSERT ON pesanan
FOR EACH ROW
BEGIN
    DECLARE new_no VARCHAR(20);
    DECLARE last_number INT DEFAULT 0;
    DECLARE today_date VARCHAR(8);
    
    SET today_date = DATE_FORMAT(NOW(), '%Y%m%d');
    
    SELECT IFNULL(MAX(CAST(SUBSTRING_INDEX(no_pesanan, '-', -1) AS UNSIGNED)), 0) INTO last_number
    FROM pesanan 
    WHERE no_pesanan LIKE CONCAT('ORD', today_date, '%');
    
    SET new_no = CONCAT('ORD', today_date, '-', LPAD(last_number + 1, 4, '0'));
    SET NEW.no_pesanan = new_no;
END//
DELIMITER ;

-- Trigger untuk invoice pembayaran otomatis
DELIMITER //
CREATE TRIGGER generate_no_invoice
BEFORE INSERT ON pembayaran
FOR EACH ROW
BEGIN
    DECLARE new_invoice VARCHAR(20);
    DECLARE last_number INT DEFAULT 0;
    DECLARE today_date VARCHAR(8);
    
    SET today_date = DATE_FORMAT(NOW(), '%Y%m%d');
    
    SELECT IFNULL(MAX(CAST(SUBSTRING_INDEX(no_invoice, '-', -1) AS UNSIGNED)), 0) INTO last_number
    FROM pembayaran 
    WHERE no_invoice LIKE CONCAT('INV', today_date, '%');
    
    SET new_invoice = CONCAT('INV', today_date, '-', LPAD(last_number + 1, 4, '0'));
    SET NEW.no_invoice = new_invoice;
END//
DELIMITER ;

-- Trigger untuk kode menu otomatis
DELIMITER //
CREATE TRIGGER generate_kode_menu
BEFORE INSERT ON menu
FOR EACH ROW
BEGIN
    DECLARE new_kode VARCHAR(20);
    DECLARE last_number INT DEFAULT 0;
    
    SELECT IFNULL(MAX(CAST(SUBSTRING_INDEX(kode_menu, '-', -1) AS UNSIGNED)), 0) INTO last_number
    FROM menu 
    WHERE kode_menu LIKE CONCAT('MN', DATE_FORMAT(NOW(), '%Y%m'), '%');
    
    SET new_kode = CONCAT('MN', DATE_FORMAT(NOW(), '%Y%m'), '-', LPAD(last_number + 1, 4, '0'));
    SET NEW.kode_menu = new_kode;
END//
DELIMITER ;

-- Trigger untuk kode pelanggan otomatis
DELIMITER //
CREATE TRIGGER generate_kode_pelanggan
BEFORE INSERT ON pelanggan
FOR EACH ROW
BEGIN
    DECLARE new_kode VARCHAR(20);
    DECLARE last_number INT DEFAULT 0;
    
    SELECT IFNULL(MAX(CAST(SUBSTRING_INDEX(kode_pelanggan, '-', -1) AS UNSIGNED)), 0) INTO last_number
    FROM pelanggan 
    WHERE kode_pelanggan LIKE CONCAT('PEL', DATE_FORMAT(NOW(), '%Y%m'), '%');
    
    SET new_kode = CONCAT('PEL', DATE_FORMAT(NOW(), '%Y%m'), '-', LPAD(last_number + 1, 4, '0'));
    SET NEW.kode_pelanggan = new_kode;
END//
DELIMITER ;

-- Trigger untuk update grand_total
DELIMITER //
CREATE TRIGGER update_grand_total
BEFORE INSERT ON pesanan
FOR EACH ROW
BEGIN
    SET NEW.grand_total = NEW.total_harga - NEW.diskon + NEW.biaya_pengiriman;
END//
DELIMITER ;

-- Trigger notifikasi pesanan baru
DELIMITER //
CREATE TRIGGER notifikasi_pesanan_baru
AFTER INSERT ON pesanan
FOR EACH ROW
BEGIN
    INSERT INTO notifikasi (judul, pesan, link) 
    VALUES ('Pesanan Baru', CONCAT('Pesanan baru dari ', NEW.nama_pemesan, ' dengan total Rp ', FORMAT(NEW.total_harga, 0)), '?page=pesanan&filter=baru');
END//
DELIMITER ;

-- Trigger notifikasi menunggu verifikasi
DELIMITER //
CREATE TRIGGER notifikasi_menunggu_verifikasi
AFTER UPDATE ON pesanan
FOR EACH ROW
BEGIN
    IF NEW.payment_status = 'menunggu_verifikasi' AND OLD.payment_status != 'menunggu_verifikasi' THEN
        INSERT INTO notifikasi (judul, pesan, link) 
        VALUES ('Menunggu Verifikasi', CONCAT('Pesanan ', NEW.no_pesanan, ' sudah upload bukti pembayaran'), '?page=pesanan');
    END IF;
END//
DELIMITER ;

-- =====================================================
-- VIEW
-- =====================================================

-- View Laporan Penjualan Harian
CREATE VIEW v_laporan_penjualan_harian AS
SELECT 
    DATE(tanggal_pemesanan) as tanggal,
    COUNT(*) as jumlah_pesanan,
    SUM(total_harga) as total_penjualan,
    SUM(CASE WHEN source = 'online' THEN 1 ELSE 0 END) as online_count,
    SUM(CASE WHEN source = 'offline' THEN 1 ELSE 0 END) as offline_count,
    SUM(CASE WHEN payment_status = 'lunas' THEN total_harga ELSE 0 END) as pendapatan_terkonfirmasi
FROM pesanan 
WHERE status != 'batal'
GROUP BY DATE(tanggal_pemesanan)
ORDER BY tanggal DESC;

-- View Menu Terlaris
CREATE VIEW v_menu_terlaris AS
SELECT 
    m.id,
    m.nama_menu,
    k.nama_kategori as kategori,
    SUM(dp.quantity) as total_terjual,
    SUM(dp.subtotal) as total_penjualan
FROM detail_pesanan dp
JOIN menu m ON dp.menu_id = m.id
LEFT JOIN kategori_menu k ON m.kategori_id = k.id
JOIN pesanan p ON dp.pesanan_id = p.id
WHERE p.status = 'selesai'
GROUP BY m.id
ORDER BY total_terjual DESC
LIMIT 10;

-- View Rekap Keuangan Bulanan
CREATE VIEW v_rekap_keuangan_bulanan AS
SELECT 
    DATE_FORMAT(tanggal_pembayaran, '%Y-%m') as bulan,
    COUNT(*) as jumlah_transaksi,
    SUM(jumlah_dibayar) as total_pendapatan,
    COUNT(DISTINCT pesanan_id) as jumlah_pesanan
FROM pembayaran 
WHERE status = 'lunas'
GROUP BY DATE_FORMAT(tanggal_pembayaran, '%Y-%m')
ORDER BY bulan DESC;

-- =====================================================
-- STORED PROCEDURE
-- =====================================================

-- Stored Procedure Laporan Penjualan
DELIMITER //
CREATE PROCEDURE sp_laporan_penjualan(IN start_date DATE, IN end_date DATE)
BEGIN
    SELECT 
        DATE(tanggal_pemesanan) as tanggal,
        COUNT(*) as jumlah_pesanan,
        SUM(total_harga) as total_penjualan,
        SUM(CASE WHEN source = 'online' THEN total_harga ELSE 0 END) as online_sales,
        SUM(CASE WHEN source = 'offline' THEN total_harga ELSE 0 END) as offline_sales
    FROM pesanan 
    WHERE status = 'selesai' 
        AND DATE(tanggal_pemesanan) BETWEEN start_date AND end_date
    GROUP BY DATE(tanggal_pemesanan)
    ORDER BY tanggal;
END//
DELIMITER ;

-- =====================================================
-- INDEX UNTUK OPTIMASI
-- =====================================================

CREATE INDEX idx_pesanan_status ON pesanan(status);
CREATE INDEX idx_pesanan_tanggal ON pesanan(tanggal_pemesanan);
CREATE INDEX idx_pesanan_payment ON pesanan(payment_status);
CREATE INDEX idx_pesanan_no ON pesanan(no_pesanan);
CREATE INDEX idx_pembayaran_tanggal ON pembayaran(tanggal_pembayaran);
CREATE INDEX idx_pembayaran_invoice ON pembayaran(no_invoice);
CREATE INDEX idx_detail_pesanan ON detail_pesanan(pesanan_id, menu_id);
CREATE INDEX idx_log_aktivitas ON log_aktivitas(user_id, created_at);
CREATE INDEX idx_menu_kategori ON menu(kategori_id);
CREATE INDEX idx_pesanan_pelanggan ON pesanan(pelanggan_id);

-- =====================================================
-- DATA WAJIB (Minimal untuk sistem berjalan)
-- =====================================================

-- 1. Users (Admin & Kasir)
INSERT INTO users (username, password, role, nama_lengkap, email, status) VALUES
('admin', MD5('admin123'), 'admin', 'Administrator', 'admin@catering.com', 'aktif'),
('kasir', MD5('kasir123'), 'kasir', 'Kasir', 'kasir@catering.com', 'aktif');

-- 2. Pengaturan Toko (Default)
INSERT INTO pengaturan_toko (nama_toko, deskripsi, tentang, alamat, no_telepon, no_whatsapp, email, rekening_bca, rekening_mandiri, ewallet_ovo, ewallet_gopay, ewallet_dana, jam_operasional, metode_pembayaran) VALUES
('Dapur Ibu Lala', 
 'Catering rumahan dengan cita rasa terbaik', 
 'Dapur Ibu Lala berdiri sejak 2015, berkomitmen menyajikan masakan berkualitas dengan bahan-bahan segar.', 
 'Jl. Makan Enak No. 123, Kota Kuliner', 
 '081234567890', 
 '6281234567890', 
 'info@dapuribulala.com',
 'BCA: 1234567890 a/n Dapur Ibu Lala',
 'Mandiri: 0987654321 a/n Dapur Ibu Lala',
 '081234567890',
 '081234567890',
 '081234567890',
 '{"senin_jumat":"08:00-20:00","sabtu":"08:00-17:00","minggu":"Libur"}', 
 '{"tunai":true,"transfer_bank":["BCA","Mandiri","BRI"],"e_wallet":["OVO","GoPay","Dana"],"qris":true}');

-- 3. Kategori Menu Dasar
INSERT INTO kategori_menu (nama_kategori, deskripsi, status) VALUES
('Nasi Box', 'Paket nasi box lengkap dengan lauk pauk', 'aktif'),
('Prasmanan', 'Paket prasmanan untuk acara', 'aktif'),
('Snack', 'Snack ringan dan minuman', 'aktif');

-- 4. Menu Dasar
INSERT INTO menu (nama_menu, kategori_id, kategori, harga, deskripsi, stok, status) VALUES
('Nasi Box Ayam Goreng', 1, 'Nasi Box', 25000, 'Nasi + Ayam Goreng + Sayur + Sambal', 100, 'aktif'),
('Nasi Box Rendang', 1, 'Nasi Box', 35000, 'Nasi + Rendang + Sayur + Kerupuk', 100, 'aktif'),
('Paket Prasmanan Mini', 2, 'Prasmanan', 500000, 'Untuk 10 orang dengan 3 menu', 50, 'aktif'),
('Paket Prasmanan Besar', 2, 'Prasmanan', 1000000, 'Untuk 25 orang dengan 5 menu', 30, 'aktif'),
('Snack Box', 3, 'Snack', 15000, 'Snack ringan + minuman', 200, 'aktif');

-- =====================================================
-- QUERY FINAL CHECK
-- =====================================================
SELECT 'Database catering_system berhasil dibuat!' as 'Status';