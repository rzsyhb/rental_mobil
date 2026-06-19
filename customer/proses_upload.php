<?php
session_start();
include "../config/koneksi.php";
include "../config/helpers.php";
bootstrap_app($conn);

if (!isset($_SESSION['login']) || $_SESSION['role'] != 'customer') {
    header('Location: ../auth/login.php');
    exit;
}

$id = isset($_POST['id_transaksi']) ? (int) $_POST['id_transaksi'] : 0;
$idUser = (int) $_SESSION['id_user'];

$cek = mysqli_query($conn, "SELECT * FROM transaksi WHERE id_transaksi='$id' AND id_customer='$idUser' LIMIT 1");
$transaksi = mysqli_fetch_assoc($cek);
if (!$transaksi) {
    echo "Data transaksi tidak ditemukan.";
    exit;
}

$bukti = upload_gambar('bukti', '../uploads/bukti', ['jpg', 'jpeg', 'png']);
$simA = upload_gambar_terenkripsi('sim_a', 'sim_a', ['jpg', 'jpeg', 'png']);

if ($bukti === 'error_size') {
    echo "<script>alert('Ukuran bukti pembayaran maksimal 3 MB.');window.history.back();</script>";
    exit;
}

if ($simA === 'error_size') {
    echo "<script>alert('Ukuran SIM A maksimal 3 MB.');window.history.back();</script>";
    exit;
}

if (empty($bukti) || empty($simA)) {
    echo "<script>alert('Upload bukti pembayaran dan SIM A wajib berupa gambar PNG atau JPEG/JPG.');window.history.back();</script>";
    exit;
}

$buktiPath = get_relative_upload_path('bukti', $bukti);
$simPath = mysqli_real_escape_string($conn, $simA);

if (!empty($transaksi['sim_a'])) {
    app_remove_secure_file($transaksi['sim_a']);
}

mysqli_query($conn, "
    UPDATE transaksi
    SET bukti_pembayaran='$buktiPath', sim_a='$simPath', status='menunggu konfirmasi', catatan_admin=NULL
    WHERE id_transaksi='$id' AND id_customer='$idUser'
");

header("Location: invoice.php?id=" . $id);
exit;
?>
