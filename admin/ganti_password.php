<?php
session_start();
include "../config/koneksi.php";
include "../config/helpers.php";
include "_layout.php";
bootstrap_app($conn);
admin_require_login();

$pesan = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $passwordLama = trim($_POST['password_lama'] ?? '');
    $passwordBaru = trim($_POST['password_baru'] ?? '');
    $konfirmasi = trim($_POST['konfirmasi_password'] ?? '');
    $idAdmin = (int) $_SESSION['id_user'];
    $admin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM admin WHERE id_admin='$idAdmin' LIMIT 1"));
    if (!$admin || !app_password_matches($passwordLama, (string) $admin['password'])) {
        $pesan = '<div class="alert alert-danger">Password lama tidak sesuai.</div>';
    } elseif (strlen($passwordBaru) < 6) {
        $pesan = '<div class="alert alert-warning">Password baru minimal 6 karakter.</div>';
    } elseif ($passwordBaru !== $konfirmasi) {
        $pesan = '<div class="alert alert-warning">Konfirmasi password baru tidak sama.</div>';
    } else {
        $newPassDb = mysqli_real_escape_string($conn, app_hash_password($passwordBaru));
        mysqli_query($conn, "UPDATE admin SET password='$newPassDb' WHERE id_admin='$idAdmin'");
        $pesan = '<div class="alert alert-success">Password admin berhasil diperbarui.</div>';
    }
}
admin_page_start('Ganti Password', 'password');
?>
<div class="row justify-content-center">
    <div class="col-xl-6">
        <?= $pesan; ?>
        <div class="card form-card">
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3"><label class="form-label">Password Lama</label><input type="password" name="password_lama" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Password Baru</label><input type="password" name="password_baru" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Konfirmasi Password Baru</label><input type="password" name="konfirmasi_password" class="form-control" required></div>
                    <div class="page-actions"><button class="btn btn-brand">Simpan Perubahan</button></div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php admin_page_end(); ?>
