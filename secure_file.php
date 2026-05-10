<?php
session_start();
include __DIR__ . '/config/koneksi.php';
include __DIR__ . '/config/helpers.php';
bootstrap_app($conn);

if (!isset($_SESSION['login'])) {
    http_response_code(403);
    exit('Akses ditolak.');
}

$entity = trim($_GET['entity'] ?? '');
$id = (int) ($_GET['id'] ?? 0);
$role = $_SESSION['role'] ?? '';
$idUser = (int) ($_SESSION['id_user'] ?? 0);

$dbValue = '';

if ($entity === 'customer_ktp') {
    if ($role === 'admin') {
        $sql = "SELECT foto FROM customer WHERE id_customer='$id' LIMIT 1";
    } elseif ($role === 'customer' && $idUser === $id) {
        $sql = "SELECT foto FROM customer WHERE id_customer='$id' LIMIT 1";
    } else {
        http_response_code(403);
        exit('Akses ditolak.');
    }

    $result = mysqli_query($conn, $sql);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    $dbValue = $row['foto'] ?? '';
} elseif ($entity === 'sim_a') {
    if ($role === 'admin') {
        $sql = "SELECT sim_a FROM transaksi WHERE id_transaksi='$id' LIMIT 1";
    } elseif ($role === 'customer') {
        $sql = "SELECT sim_a FROM transaksi WHERE id_transaksi='$id' AND id_customer='$idUser' LIMIT 1";
    } else {
        http_response_code(403);
        exit('Akses ditolak.');
    }

    $result = mysqli_query($conn, $sql);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    $dbValue = $row['sim_a'] ?? '';
} else {
    http_response_code(404);
    exit('File tidak ditemukan.');
}

$binary = app_read_encrypted_file($dbValue);
if ($binary === false) {
    http_response_code(404);
    exit('File tidak ditemukan.');
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->buffer($binary) ?: 'application/octet-stream';
header('Content-Type: ' . $mime);
header('Content-Length: ' . strlen($binary));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=0, no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
echo $binary;
exit;
?>
