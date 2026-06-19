<?php
session_start();
include "../config/koneksi.php";
include "../config/helpers.php";
include "_layout.php";
bootstrap_app($conn);
admin_require_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$idAdmin = (int) ($_SESSION['id_user'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id_transaksi']) ? (int) $_POST['id_transaksi'] : 0;
    $catatan = mysqli_real_escape_string($conn, trim($_POST['catatan_admin'] ?? ''));
    mysqli_query($conn, "UPDATE transaksi SET id_admin='$idAdmin', status='dibatalkan', catatan_admin='$catatan' WHERE id_transaksi='$id'");
    bootstrap_app($conn);
    header('Location: transaksi.php');
    exit;
}
$query = mysqli_query($conn, "SELECT t.*, c.nama, m.nama_mobil FROM transaksi t JOIN customer c ON t.id_customer=c.id_customer JOIN mobil m ON t.id_mobil=m.id_mobil WHERE t.id_transaksi='$id' LIMIT 1");
$data = mysqli_fetch_assoc($query);
if (!$data) { echo 'Data transaksi tidak ditemukan.'; exit; }
admin_page_start('Konfirmasi Pembatalan', 'transaksi');
?>
<div class="row justify-content-center">
    <div class="col-xl-7">
        <div class="card form-card"><div class="card-body">
            <p>Anda akan membatalkan booking <strong><?= esc($data['nama_mobil']); ?></strong> milik customer <strong><?= esc($data['nama']); ?></strong>.</p>
            <form method="POST">
                <input type="hidden" name="id_transaksi" value="<?= $data['id_transaksi']; ?>">
                <div class="mb-3"><label class="form-label">Alasan pembatalan oleh admin</label><textarea name="catatan_admin" class="form-control" rows="4" placeholder="Contoh: SIM A tidak jelas, bukti bayar tidak sesuai, data tidak valid" required></textarea></div>
                <div class="page-actions"><button class="btn btn-danger">Ya, Batalkan Booking</button><a href="transaksi.php" class="btn btn-outline-secondary">Kembali</a></div>
            </form>
        </div></div>
    </div>
</div>
<?php admin_page_end(); ?>