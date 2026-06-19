<?php
session_start();
include "../config/koneksi.php";
include "../config/helpers.php";
include "_layout.php";
bootstrap_app($conn);
customer_require_login();

$idUser = (int) ($_SESSION['id_user'] ?? 0);
$id = isset($_GET['id']) ? (int) $_GET['id'] : (int) ($_POST['id_transaksi'] ?? 0);

$query = mysqli_query($conn, "
    SELECT t.*, m.nama_mobil, m.no_plat
    FROM transaksi t
    JOIN mobil m ON t.id_mobil = m.id_mobil
    WHERE t.id_transaksi = '$id'
      AND t.id_customer = '$idUser'
    LIMIT 1
");
$data = $query ? mysqli_fetch_assoc($query) : null;

if (!$data) {
    customer_page_start('Pembatalan Pesanan', 'transaksi');
    echo '<div class="alert alert-danger">Data transaksi tidak ditemukan.</div><div class="page-actions"><a href="transaksi.php" class="btn btn-outline-secondary">Kembali ke Transaksi</a></div>';
    customer_page_end();
    exit;
}

if ($data['status'] !== 'menunggu pembayaran') {
    customer_page_start('Pembatalan Pesanan', 'transaksi');
    echo '<div class="alert alert-warning">Pesanan ini tidak bisa dibatalkan oleh customer karena statusnya sudah berubah.</div><div class="page-actions"><a href="transaksi.php" class="btn btn-outline-secondary">Kembali ke Transaksi</a><a href="invoice.php?id=' . (int) $data['id_transaksi'] . '" class="btn btn-brand">Lihat Invoice</a></div>';
    customer_page_end();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $catatan = mysqli_real_escape_string($conn, 'Dibatalkan oleh customer sebelum melakukan pembayaran.');
    mysqli_query($conn, "UPDATE transaksi SET status='dibatalkan', catatan_admin='$catatan' WHERE id_transaksi='$id' AND id_customer='$idUser' AND status='menunggu pembayaran' LIMIT 1");
    bootstrap_app($conn);
    header('Location: transaksi.php');
    exit;
}

customer_page_start('Pembatalan Pesanan', 'transaksi');
?>
<div class="row justify-content-center">
    <div class="col-xl-7">
        <div class="card form-card">
            <div class="card-body">
                <div class="alert alert-warning">Pembatalan hanya bisa dilakukan untuk pesanan dengan status <strong>Menunggu Pembayaran</strong>.</div>
                <p>Anda yakin ingin membatalkan pesanan mobil <strong><?= esc($data['nama_mobil']); ?></strong> dengan nomor plat <strong><?= esc($data['no_plat']); ?></strong>?</p>
                <div class="booking-summary-box mb-4">
                    <div class="summary-list">
                        <div class="summary-list-item"><span>Tanggal Sewa</span><strong><?= format_tanggal_id($data['tanggal_sewa']); ?></strong></div>
                        <div class="summary-list-item"><span>Tanggal Kembali</span><strong><?= format_tanggal_id($data['tanggal_kembali']); ?></strong></div>
                        <div class="summary-list-item"><span>Total Harga</span><strong><?= format_rupiah($data['total_harga']); ?></strong></div>
                    </div>
                </div>
                <form method="POST">
                    <input type="hidden" name="id_transaksi" value="<?= (int) $data['id_transaksi']; ?>">
                    <div class="page-actions">
                        <button type="submit" class="btn btn-danger">Ya, Batalkan Pesanan</button>
                        <a href="transaksi.php" class="btn btn-outline-secondary">Tidak, Kembali</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php customer_page_end(); ?>
