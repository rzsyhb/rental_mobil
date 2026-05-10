<?php
session_start();
include "../config/koneksi.php";
include "../config/helpers.php";
bootstrap_app($conn);

if (!isset($_SESSION['id_user']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'customer') {
    echo "Harus login dulu!";
    exit;
}

$id_user  = (int) $_SESSION['id_user'];
$id_mobil = isset($_POST['id_mobil']) ? (int) $_POST['id_mobil'] : 0;
$lama     = isset($_POST['lama_sewa']) ? (int) $_POST['lama_sewa'] : 0;
$tgl_sewa_input = isset($_POST['tgl_sewa']) ? $_POST['tgl_sewa'] : '';

if ($id_mobil <= 0 || $lama <= 0 || empty($tgl_sewa_input)) {
    echo "Data booking tidak lengkap.";
    exit;
}

$tgl_sewa = date("Y-m-d H:i:s", strtotime($tgl_sewa_input));
$tgl_kembali = date("Y-m-d H:i:s", strtotime("+$lama hours", strtotime($tgl_sewa)));

if (ada_bentrok_jadwal($conn, $id_mobil, $tgl_sewa, $tgl_kembali)) {
    echo "<script>alert('Jadwal mobil bentrok atau masih menunggu diselesaikan admin. Silakan pilih jam lain.');window.history.back();</script>";
    exit;
}

$mobilQuery = mysqli_query($conn, "SELECT * FROM mobil WHERE id_mobil='$id_mobil' LIMIT 1");
$mobil = mysqli_fetch_assoc($mobilQuery);

if (!$mobil) {
    echo "Mobil tidak ditemukan.";
    exit;
}

if (!mobil_bisa_disewa($mobil)) {
    echo "<script>alert('Mobil ini tidak bisa disewa karena statusnya bukan tersedia.');window.history.back();</script>";
    exit;
}

$total = ($lama == 12) ? (float) $mobil['harga_12jam'] : (($lama / 24) * (float) $mobil['harga_24jam']);

mysqli_query($conn, "
    INSERT INTO transaksi (id_customer, id_mobil, lama_sewa, tanggal_sewa, tanggal_kembali, total_harga, status)
    VALUES ('$id_user', '$id_mobil', '$lama', '$tgl_sewa', '$tgl_kembali', '$total', 'menunggu pembayaran')
");

$idTransaksi = mysqli_insert_id($conn);
bootstrap_app($conn);

header("Location: invoice.php?id=" . $idTransaksi);
exit;
?>
