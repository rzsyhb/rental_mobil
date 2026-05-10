<?php
session_start();
include "../config/koneksi.php";

$id = $_GET['id'];
$idAdmin = (int) ($_SESSION['id_user'] ?? 0);

mysqli_query($conn, "
    UPDATE transaksi 
    SET id_admin='$idAdmin', status='selesai'
    WHERE id_transaksi='$id'
");

header("Location: transaksi.php");
?>