<?php
session_start();
include "../config/koneksi.php";
include "../config/helpers.php";
bootstrap_app($conn);
if (!isset($_SESSION['login']) || ($_SESSION['role'] ?? '') !== 'admin') { header('Location: ../auth/login.php'); exit; }

$id = (int) ($_POST['id_mobil'] ?? 0);
$no_plat = mysqli_real_escape_string($conn, trim($_POST['no_plat'] ?? ''));
$nama = mysqli_real_escape_string($conn, trim($_POST['nama_mobil'] ?? ''));
$merk = mysqli_real_escape_string($conn, trim($_POST['merk'] ?? ''));
$tahun = mysqli_real_escape_string($conn, trim($_POST['tahun'] ?? ''));
$harga12 = (float) ($_POST['harga12'] ?? 0);
$harga24 = (float) ($_POST['harga24'] ?? 0);
$deskripsi = mysqli_real_escape_string($conn, trim($_POST['deskripsi'] ?? ''));
$statusInput = trim((string) ($_POST['status'] ?? 'tersedia'));
$statusAllowed = ['tersedia', 'disewa', 'sedang diservice'];
$status = in_array($statusInput, $statusAllowed, true) ? $statusInput : 'tersedia';
$idAdmin = (int) ($_SESSION['id_user'] ?? 0);
$foto = upload_gambar('foto', '../uploads/mobil');

if ($status === 'sedang diservice' && mobil_punya_rental_aktif($conn, $id)) {
    echo "<script>alert('Mobil sedang dipakai customer dan belum dikonfirmasi selesai oleh admin. Status sedang diservice baru bisa dipilih setelah rental aktif selesai. Jadwal yang masih akan datang tetap diperbolehkan.');window.location='edit_mobil.php?id=$id';</script>";
    exit;
}

$sql = "UPDATE mobil SET id_admin='$idAdmin', no_plat='$no_plat', nama_mobil='$nama', merk='$merk', tahun='$tahun', harga_12jam='$harga12', harga_24jam='$harga24', deskripsi='$deskripsi', status='$status'";
if ($foto !== '') {
    $old = mysqli_fetch_assoc(mysqli_query($conn, "SELECT foto FROM mobil WHERE id_mobil='$id' LIMIT 1"));
    if (!empty($old['foto']) && file_exists('../uploads/mobil/' . $old['foto'])) { @unlink('../uploads/mobil/' . $old['foto']); }
    $sql .= ", foto='$foto'";
}
$sql .= " WHERE id_mobil='$id'";
mysqli_query($conn, $sql);
header('Location: mobil.php');
exit;
?>
