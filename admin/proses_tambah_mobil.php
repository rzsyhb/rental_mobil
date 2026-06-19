<?php
session_start();
include "../config/koneksi.php";
include "../config/helpers.php";
bootstrap_app($conn);
if (!isset($_SESSION['login']) || ($_SESSION['role'] ?? '') !== 'admin') { header('Location: ../auth/login.php'); exit; }

$nama = mysqli_real_escape_string($conn, trim($_POST['nama_mobil'] ?? ''));
$merk = mysqli_real_escape_string($conn, trim($_POST['merk'] ?? ''));
$no_plat = mysqli_real_escape_string($conn, trim($_POST['no_plat'] ?? ''));
$tahun = mysqli_real_escape_string($conn, trim($_POST['tahun'] ?? ''));
$harga12 = (float) ($_POST['harga12'] ?? 0);
$harga24 = (float) ($_POST['harga24'] ?? 0);
$deskripsi = mysqli_real_escape_string($conn, trim($_POST['deskripsi'] ?? ''));
$statusInput = trim((string) ($_POST['status'] ?? 'tersedia'));
$statusAllowed = ['tersedia', 'disewa', 'sedang diservice'];
$status = in_array($statusInput, $statusAllowed, true) ? $statusInput : 'tersedia';
$idAdmin = (int) ($_SESSION['id_user'] ?? 0);
$foto = upload_gambar('foto', '../uploads/mobil');

if ($foto === 'error_size') {
    echo "<script>alert('Ukuran foto mobil maksimal 3 MB.');window.history.back();</script>";
    exit;
}

mysqli_query($conn, "INSERT INTO mobil (id_admin,no_plat,nama_mobil,merk,tahun,harga_12jam,harga_24jam,deskripsi,foto,status) VALUES ('$idAdmin','$no_plat','$nama','$merk','$tahun','$harga12','$harga24','$deskripsi','$foto','$status')");
header('Location: mobil.php');
exit;
?>
