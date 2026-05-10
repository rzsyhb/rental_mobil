<?php
session_start();
include "../config/koneksi.php";
include "../config/helpers.php";
bootstrap_app($conn);
if (!isset($_SESSION['login']) || ($_SESSION['role'] ?? '') !== 'admin') { header('Location: ../auth/login.php'); exit; }
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$cek = mysqli_query($conn, "SELECT foto FROM customer WHERE id_customer='$id' LIMIT 1");
$row = mysqli_fetch_assoc($cek);
if ($row) {
    if (!empty($row['foto'])) { app_remove_secure_file($row['foto']); }

    $trx = mysqli_query($conn, "SELECT bukti_pembayaran, sim_a FROM transaksi WHERE id_customer='$id'");
    if ($trx) {
        while ($t = mysqli_fetch_assoc($trx)) {
            if (!empty($t['sim_a'])) {
                app_remove_secure_file($t['sim_a']);
            }
            if (!empty($t['bukti_pembayaran'])) {
                $bukti = '../' . ltrim($t['bukti_pembayaran'], '/');
                if (is_file($bukti)) {
                    @unlink($bukti);
                }
            }
        }
    }

    mysqli_query($conn, "DELETE FROM customer WHERE id_customer='$id'");
}
header('Location: customer.php');
exit;
?>
