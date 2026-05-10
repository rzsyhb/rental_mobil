<?php
session_start();
include "../config/koneksi.php";
include "../config/helpers.php";
include "_layout.php";
bootstrap_app($conn);
customer_require_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$idUser = (int) $_SESSION['id_user'];
$query = mysqli_query($conn, "SELECT * FROM transaksi WHERE id_transaksi='$id' AND id_customer='$idUser' LIMIT 1");
$transaksi = mysqli_fetch_assoc($query);
if (!$transaksi) {
    echo "Data transaksi tidak ditemukan.";
    exit;
}
customer_page_start('Upload Dokumen Booking', 'transaksi');
?>
<div class="row justify-content-center">
    <div class="col-xl-8">
        <div class="card form-card">
            <div class="card-header"><h3 class="card-title">Upload Bukti Pembayaran &amp; SIM A</h3></div>
            <div class="card-body">
                <p class="text-muted">Silakan upload gambar bukti pembayaran dan foto SIM A agar admin bisa melakukan verifikasi.</p>
                <form action="proses_upload.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id_transaksi" value="<?= $id; ?>">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Upload Bukti Pembayaran</label><input type="file" name="bukti" class="form-control" accept="image/*" required></div>
                        <div class="col-md-6"><label class="form-label">Upload SIM A</label><input type="file" name="sim_a" class="form-control" accept="image/*" required></div>
                    </div>
                    <div class="page-actions mt-4">
                        <button class="btn btn-brand">Upload Dokumen</button>
                        <a href="invoice.php?id=<?= $id; ?>" class="btn btn-outline-secondary">Kembali ke Invoice</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php customer_page_end(); ?>
