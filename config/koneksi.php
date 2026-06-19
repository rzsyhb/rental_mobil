<?php
date_default_timezone_set('Asia/Jakarta');
$host = "localhost";
$user = "root";
$pass = "";
$db   = "rental_mobil";

mysqli_report(MYSQLI_REPORT_OFF);

$server = @mysqli_connect($host, $user, $pass);
if (!$server) {
    die("Koneksi MySQL gagal: " . mysqli_connect_error());
}

@mysqli_set_charset($server, 'utf8mb4');

function app_run_query($conn, $sql)
{
    $ok = @mysqli_query($conn, $sql);
    if (!$ok) {
        die("Gagal menjalankan query database: " . mysqli_error($conn));
    }
    return $ok;
}

function app_bootstrap_database($conn, $dbName)
{
    $dbNameSafe = '`' . str_replace('`', '``', $dbName) . '`';
    app_run_query($conn, "CREATE DATABASE IF NOT EXISTS {$dbNameSafe} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    app_run_query($conn, "USE {$dbNameSafe}");

    app_run_query($conn, "
        CREATE TABLE IF NOT EXISTS admin (
            id_admin INT UNSIGNED NOT NULL AUTO_INCREMENT,
            nama_admin VARCHAR(100) NOT NULL,
            username VARCHAR(50) NOT NULL,
            password VARCHAR(255) NOT NULL,
            PRIMARY KEY (id_admin),
            UNIQUE KEY uniq_admin_username (username)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    app_run_query($conn, "
        CREATE TABLE IF NOT EXISTS customer (
            id_customer INT UNSIGNED NOT NULL AUTO_INCREMENT,
            nama VARCHAR(100) NOT NULL,
            alamat TEXT NOT NULL,
            jenis_kelamin ENUM('L','P') NOT NULL,
            no_hp VARCHAR(255) NOT NULL,
            username VARCHAR(50) NOT NULL,
            password VARCHAR(255) NOT NULL,
            foto VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY (id_customer),
            UNIQUE KEY uniq_customer_username (username)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    app_run_query($conn, "
        CREATE TABLE IF NOT EXISTS mobil (
            id_mobil INT UNSIGNED NOT NULL AUTO_INCREMENT,
            id_admin INT UNSIGNED DEFAULT NULL,
            no_plat VARCHAR(30) NOT NULL,
            nama_mobil VARCHAR(100) NOT NULL,
            merk VARCHAR(100) NOT NULL,
            tahun VARCHAR(10) NOT NULL,
            harga_12jam DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            harga_24jam DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            deskripsi TEXT DEFAULT NULL,
            foto VARCHAR(255) DEFAULT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'tersedia',
            PRIMARY KEY (id_mobil),
            KEY idx_mobil_admin (id_admin),
            UNIQUE KEY uniq_mobil_no_plat (no_plat),
            CONSTRAINT fk_mobil_admin FOREIGN KEY (id_admin) REFERENCES admin(id_admin) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    app_run_query($conn, "
        CREATE TABLE IF NOT EXISTS transaksi (
            id_transaksi INT UNSIGNED NOT NULL AUTO_INCREMENT,
            id_customer INT UNSIGNED NOT NULL,
            id_mobil INT UNSIGNED NOT NULL,
            id_admin INT UNSIGNED DEFAULT NULL,
            lama_sewa INT NOT NULL,
            tanggal_sewa DATETIME NOT NULL,
            tanggal_kembali DATETIME NOT NULL,
            total_harga DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            bukti_pembayaran VARCHAR(255) DEFAULT NULL,
            sim_a VARCHAR(255) DEFAULT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'menunggu pembayaran',
            catatan_admin TEXT DEFAULT NULL,
            overtime_jam INT NOT NULL DEFAULT 0,
            overtime_biaya DECIMAL(15,2) NOT NULL DEFAULT 0,
            total_akhir DECIMAL(15,2) DEFAULT NULL,
            selesai_admin_at DATETIME DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id_transaksi),
            KEY idx_transaksi_customer (id_customer),
            KEY idx_transaksi_mobil (id_mobil),
            KEY idx_transaksi_admin (id_admin),
            CONSTRAINT fk_transaksi_customer FOREIGN KEY (id_customer) REFERENCES customer(id_customer) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT fk_transaksi_mobil FOREIGN KEY (id_mobil) REFERENCES mobil(id_mobil) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT fk_transaksi_admin FOREIGN KEY (id_admin) REFERENCES admin(id_admin) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    @mysqli_query($conn, "ALTER TABLE mobil ADD COLUMN id_admin INT UNSIGNED NULL AFTER id_mobil");
    @mysqli_query($conn, "ALTER TABLE mobil ADD KEY idx_mobil_admin (id_admin)");
    @mysqli_query($conn, "ALTER TABLE mobil ADD CONSTRAINT fk_mobil_admin FOREIGN KEY (id_admin) REFERENCES admin(id_admin) ON DELETE SET NULL ON UPDATE CASCADE");
    @mysqli_query($conn, "ALTER TABLE mobil ADD COLUMN status VARCHAR(30) NOT NULL DEFAULT 'tersedia'");
    @mysqli_query($conn, "ALTER TABLE mobil MODIFY COLUMN status VARCHAR(30) NOT NULL DEFAULT 'tersedia'");
    @mysqli_query($conn, "ALTER TABLE customer MODIFY COLUMN no_hp VARCHAR(255) NOT NULL");
    @mysqli_query($conn, "ALTER TABLE customer MODIFY COLUMN foto VARCHAR(255) DEFAULT NULL");
    @mysqli_query($conn, "ALTER TABLE transaksi ADD COLUMN id_admin INT UNSIGNED NULL AFTER id_mobil");
    @mysqli_query($conn, "ALTER TABLE transaksi ADD KEY idx_transaksi_admin (id_admin)");
    @mysqli_query($conn, "ALTER TABLE transaksi ADD CONSTRAINT fk_transaksi_admin FOREIGN KEY (id_admin) REFERENCES admin(id_admin) ON DELETE SET NULL ON UPDATE CASCADE");
    @mysqli_query($conn, "ALTER TABLE transaksi MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'menunggu pembayaran'");
    @mysqli_query($conn, "ALTER TABLE transaksi ADD COLUMN bukti_pembayaran VARCHAR(255) NULL AFTER total_harga");
    @mysqli_query($conn, "ALTER TABLE transaksi ADD COLUMN sim_a VARCHAR(255) NULL AFTER bukti_pembayaran");
    @mysqli_query($conn, "ALTER TABLE transaksi ADD COLUMN catatan_admin TEXT NULL AFTER status");
    @mysqli_query($conn, "ALTER TABLE transaksi ADD COLUMN overtime_jam INT NOT NULL DEFAULT 0 AFTER catatan_admin");
    @mysqli_query($conn, "ALTER TABLE transaksi ADD COLUMN overtime_biaya DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER overtime_jam");
    @mysqli_query($conn, "ALTER TABLE transaksi ADD COLUMN total_akhir DECIMAL(15,2) NULL AFTER overtime_biaya");
    @mysqli_query($conn, "ALTER TABLE transaksi ADD COLUMN selesai_admin_at DATETIME NULL AFTER total_akhir");
    @mysqli_query($conn, "ALTER TABLE transaksi ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");

    $adminExists = app_run_query($conn, "SELECT COUNT(*) AS total FROM admin");
    $adminRow = mysqli_fetch_assoc($adminExists);
    if ((int) $adminRow['total'] === 0) {
        $namaAdmin = mysqli_real_escape_string($conn, 'Admin1');
        $usernameAdmin = mysqli_real_escape_string($conn, 'admin');
        $passwordAdmin = mysqli_real_escape_string($conn, password_hash('admin123', PASSWORD_ARGON2ID));
        app_run_query($conn, "INSERT INTO admin (nama_admin, username, password) VALUES ('$namaAdmin', '$usernameAdmin', '$passwordAdmin')");
    }
}

function app_ensure_directories()
{
    $dirs = [
        dirname(__DIR__) . '/uploads',
        dirname(__DIR__) . '/uploads/mobil',
        dirname(__DIR__) . '/uploads/customer',
        dirname(__DIR__) . '/uploads/bukti',
        dirname(__DIR__) . '/uploads/sim',
        dirname(__DIR__) . '/storage',
        dirname(__DIR__) . '/storage/private',
        dirname(__DIR__) . '/storage/private/customer_ktp',
        dirname(__DIR__) . '/storage/private/sim_a',
    ];

    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
    }
}

app_bootstrap_database($server, $db);
app_ensure_directories();
$conn = $server;
?>
