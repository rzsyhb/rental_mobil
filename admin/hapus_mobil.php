<?php
session_start();
include "../config/koneksi.php";
include "../config/helpers.php";

bootstrap_app($conn);

if (!isset($_SESSION['login']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: mobil.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| CEK TRANSAKSI AKTIF
|--------------------------------------------------------------------------
*/
$cek = mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM transaksi
    WHERE id_mobil = '$id'
    AND status IN (
        'menunggu pembayaran',
        'menunggu konfirmasi',
        'terverifikasi',
        'disewa'
    )
");

$hasil = mysqli_fetch_assoc($cek);

if ($hasil['total'] > 0) {

    echo "
    <script>
        alert('Mobil tidak dapat dihapus karena masih memiliki transaksi aktif.');
        window.location='mobil.php';
    </script>";
    exit;
}

/*
|--------------------------------------------------------------------------
| AMBIL DATA MOBIL
|--------------------------------------------------------------------------
*/
$data = mysqli_query($conn, "
    SELECT foto
    FROM mobil
    WHERE id_mobil='$id'
    LIMIT 1
");

$row = mysqli_fetch_assoc($data);

if (!$row) {
    header('Location: mobil.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| HAPUS FOTO
|--------------------------------------------------------------------------
*/
if (!empty($row['foto'])) {

    $fotoPath = '../uploads/mobil/' . $row['foto'];

    if (file_exists($fotoPath)) {
        @unlink($fotoPath);
    }
}

/*
|--------------------------------------------------------------------------
| HAPUS DATA MOBIL
|--------------------------------------------------------------------------
*/
mysqli_query($conn, "
    DELETE FROM mobil
    WHERE id_mobil='$id'
");

header('Location: mobil.php');
exit;
?>