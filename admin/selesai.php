<?php
session_start();
include "../config/koneksi.php";
include "../config/helpers.php";
bootstrap_app($conn);
if (!isset($_SESSION['login']) || ($_SESSION['role'] ?? '') !== 'admin') { header('Location: ../auth/login.php'); exit; }
$id = (int) ($_GET['id'] ?? 0);
$idAdmin = (int) ($_SESSION['id_user'] ?? 0);
$query = mysqli_query($conn, "SELECT * FROM transaksi WHERE id_transaksi='$id' LIMIT 1");
$data = mysqli_fetch_assoc($query);
if ($data) {
    $data['status'] = status_transaksi_efektif($data);
}
if ($data && in_array($data['status'], ['terverifikasi','disewa'], true)) {
    $overtime = hitung_overtime_data($data);
    $jam = (int) $overtime['jam'];
    $biaya = (float) $overtime['biaya'];
    $totalAkhir = (float) $data['total_harga'] + $biaya;
    $catatanSelesai = $jam > 0 ? 'Pesanan diselesaikan admin dengan overtime ' . $jam . ' jam (' . tarif_overtime_per_jam() . '/jam).' : 'Pesanan telah dikonfirmasi selesai oleh admin.';
    $catatanSelesai = mysqli_real_escape_string($conn, $catatanSelesai);
    mysqli_query($conn, "UPDATE transaksi SET id_admin='$idAdmin', status='selesai', overtime_jam='$jam', overtime_biaya='$biaya', total_akhir='$totalAkhir', selesai_admin_at=NOW(), catatan_admin='$catatanSelesai' WHERE id_transaksi='$id'");
}
bootstrap_app($conn);
header('Location: transaksi.php');
exit;
?>