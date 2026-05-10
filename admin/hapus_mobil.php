<?php
session_start();
include "../config/koneksi.php";
include "../config/helpers.php";
bootstrap_app($conn);
if (!isset($_SESSION['login']) || ($_SESSION['role'] ?? '') !== 'admin') { header('Location: ../auth/login.php'); exit; }

$id = (int) ($_GET['id'] ?? 0);
$data = mysqli_query($conn, "SELECT foto FROM mobil WHERE id_mobil='$id' LIMIT 1");
$row = mysqli_fetch_assoc($data);
if ($row) {
    if (!empty($row['foto']) && file_exists('../uploads/mobil/' . $row['foto'])) { @unlink('../uploads/mobil/' . $row['foto']); }
    mysqli_query($conn, "DELETE FROM mobil WHERE id_mobil='$id'");
}
header('Location: mobil.php');
exit;
?>