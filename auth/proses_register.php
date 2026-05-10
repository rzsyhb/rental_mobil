<?php
include "../config/koneksi.php";
include "../config/helpers.php";
bootstrap_app($conn);

$nama = mysqli_real_escape_string($conn, trim($_POST['nama'] ?? ''));
$alamat = mysqli_real_escape_string($conn, trim($_POST['alamat'] ?? ''));
$jk = mysqli_real_escape_string($conn, trim($_POST['jenis_kelamin'] ?? ''));
$no_hp_input = trim($_POST['no_hp'] ?? '');
$username = mysqli_real_escape_string($conn, trim($_POST['username'] ?? ''));
$password_input = (string) ($_POST['password'] ?? '');
$foto = upload_gambar_terenkripsi('foto', 'customer_ktp');

if ($foto === '') {
    echo "<script>alert('Upload foto KTP wajib berupa JPG/PNG/WEBP');window.history.back();</script>";
    exit;
}

if ($password_input === '' || strlen($password_input) < 6) {
    echo "<script>alert('Password minimal 6 karakter.');window.history.back();</script>";
    exit;
}

$no_hp = mysqli_real_escape_string($conn, app_encrypt_phone($no_hp_input));
$password = mysqli_real_escape_string($conn, app_hash_password($password_input));
$fotoDb = mysqli_real_escape_string($conn, $foto);

$query = mysqli_query($conn, "INSERT INTO customer (nama,alamat,jenis_kelamin,no_hp,username,password,foto) VALUES ('$nama','$alamat','$jk','$no_hp','$username','$password','$fotoDb')");

if($query){
    echo "<script>alert('Registrasi berhasil');window.location='login.php';</script>";
}else{
    echo "Registrasi gagal";
}
?>
