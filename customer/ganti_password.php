<?php
session_start();
include "../config/koneksi.php";
include "../config/helpers.php";
include "_layout.php";
bootstrap_app($conn);
customer_require_login();

$idUser = (int) $_SESSION['id_user'];
$pesan = '';
$tipe = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $passwordLama = trim($_POST['password_lama'] ?? '');
    $passwordBaru = trim($_POST['password_baru'] ?? '');
    $konfirmasi = trim($_POST['konfirmasi_password'] ?? '');

    $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM customer WHERE id_customer='$idUser' LIMIT 1"));
    if (!$user) {
        $pesan = 'Data customer tidak ditemukan.';
        $tipe = 'danger';
    } elseif (!app_password_matches($passwordLama, (string) $user['password'])) {
        $pesan = 'Password lama tidak sesuai.';
        $tipe = 'danger';
    } elseif (strlen($passwordBaru) < 6) {
        $pesan = 'Password baru minimal 6 karakter.';
        $tipe = 'danger';
    } elseif ($passwordBaru !== $konfirmasi) {
        $pesan = 'Konfirmasi password baru tidak sama.';
        $tipe = 'danger';
    } else {
        $passwordBaruDb = mysqli_real_escape_string($conn, app_hash_password($passwordBaru));
        mysqli_query($conn, "UPDATE customer SET password='$passwordBaruDb' WHERE id_customer='$idUser'");
        $pesan = 'Password berhasil diperbarui.';
    }
}

customer_page_start('Ganti Password', 'password');
?>
<div class="row justify-content-center">
    <div class="col-xl-7">
        <div class="card form-card">
            <div class="card-header"><h3 class="card-title">Ubah Password Customer</h3></div>
            <div class="card-body">
                <?php if ($pesan !== '') { ?>
                    <div class="alert alert-<?= esc($tipe); ?>"><?= esc($pesan); ?></div>
                <?php } ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Password Lama</label>
                        <input type="password" name="password_lama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password Baru</label>
                        <input type="password" name="password_baru" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Konfirmasi Password Baru</label>
                        <input type="password" name="konfirmasi_password" class="form-control" required>
                    </div>
                    <div class="page-actions">
                        <button type="submit" class="btn btn-brand">Simpan Password</button>
                        <a href="dashboard.php" class="btn btn-outline-secondary">Kembali</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php customer_page_end(); ?>
