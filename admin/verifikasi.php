<?php
session_start();
include "../config/koneksi.php";
include "../config/helpers.php";
bootstrap_app($conn);
if (!isset($_SESSION['login']) || ($_SESSION['role'] ?? '') !== 'admin') { header('Location: ../auth/login.php'); exit; }
$id = (int) ($_GET['id'] ?? 0);
$idAdmin = (int) ($_SESSION['id_user'] ?? 0);

/* Pastikan kolom status tidak lagi tersimpan sebagai ENUM lama yang menolak nilai baru. */
@mysqli_query($conn, "ALTER TABLE transaksi MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'menunggu pembayaran'");

mysqli_query($conn, "UPDATE transaksi SET id_admin='$idAdmin', status='terverifikasi', catatan_admin='Pesanan telah diverifikasi admin. Mobil siap diambil 20 menit sebelum jam sewa. Mohon siapkan jaminan sesuai ketentuan rental.' WHERE id_transaksi='$id'");
bootstrap_app($conn);
header('Location: transaksi.php');
exit;
?>