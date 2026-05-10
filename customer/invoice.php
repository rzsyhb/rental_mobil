<?php
session_start();
include "../config/koneksi.php";
include "../config/helpers.php";
include "_layout.php";
bootstrap_app($conn);
customer_require_login();

$id_user = (int) $_SESSION['id_user'];
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$query = mysqli_query($conn, "
    SELECT transaksi.*, mobil.nama_mobil, mobil.merk, mobil.no_plat, mobil.harga_12jam, mobil.harga_24jam, customer.nama
    FROM transaksi
    JOIN mobil ON transaksi.id_mobil = mobil.id_mobil
    JOIN customer ON transaksi.id_customer = customer.id_customer
    WHERE transaksi.id_transaksi = '$id' AND transaksi.id_customer = '$id_user'
    LIMIT 1
");
$invoice = mysqli_fetch_assoc($query);
if (!$invoice) {
    echo "Invoice tidak ditemukan.";
    exit;
}
$durasi = ((int) $invoice['lama_sewa'] === 12) ? '12 Jam' : ((int) $invoice['lama_sewa'] / 24) . ' Hari';
$tarif = ((int) $invoice['lama_sewa'] === 12)
    ? format_rupiah($invoice['harga_12jam']) . ' / 12 Jam'
    : format_rupiah($invoice['harga_24jam']) . ' / 24 Jam';
$status = transaksi_status_info($invoice);
$overtime = hitung_overtime_data($invoice);
$totalTagihan = total_tagihan_transaksi($invoice);
$notifBooking = customer_booking_notification($invoice);
$simUrl = secure_file_url('sim_a', $invoice['id_transaksi']);
customer_page_start('Invoice Booking', 'transaksi');
?>
<?php if ($invoice['status'] == 'dibatalkan') { ?>
<div class="alert alert-danger no-print">
    <strong>Booking dibatalkan admin.</strong><br>
    <?= !empty($invoice['catatan_admin']) ? esc($invoice['catatan_admin']) : 'Tidak ada catatan pembatalan.'; ?>
</div>
<?php } ?>
<div class="row g-4">
    <div class="col-xl-8">
        <div class="card panel-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Invoice Pembayaran Anda</h3>
                <span class="status-pill <?= status_pill_class($status['class']); ?>"><?= esc($status['label']); ?></span>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                    <div>
                        <div class="hero-badge mb-2"><i class="bi bi-receipt-cutoff"></i> Invoice</div>
                        <div class="top-note">No. INV-<?= str_pad($invoice['id_transaksi'], 5, '0', STR_PAD_LEFT); ?></div>
                        <div class="top-note">Customer: <?= esc($invoice['nama']); ?></div>
                    </div>
                    <div class="no-print page-actions">
                        <a href="transaksi.php" class="btn btn-outline-secondary">Riwayat Transaksi</a>
                        <button onclick="window.print()" class="btn btn-brand">Print Invoice</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-soft mb-0">
                        <tbody>
                            <tr><td width="35%"><strong>Merk Mobil</strong></td><td><?= esc($invoice['nama_mobil']); ?> / <?= esc($invoice['merk']); ?></td></tr>
                            <tr><td><strong>No Plat</strong></td><td><?= esc($invoice['no_plat']); ?></td></tr>
                            <tr><td><strong>Tanggal Rental</strong></td><td><?= format_tanggal_id($invoice['tanggal_sewa']); ?></td></tr>
                            <tr><td><strong>Tanggal Kembali</strong></td><td><?= format_tanggal_id($invoice['tanggal_kembali']); ?></td></tr>
                            <tr><td><strong>Biaya Sewa</strong></td><td><?= $tarif; ?></td></tr>
                            <tr><td><strong>Jumlah Sewa</strong></td><td><?= $durasi; ?></td></tr>
                            <tr><td><strong>Jumlah Pembayaran Dasar</strong></td><td><span class="invoice-box-green"><?= format_rupiah($invoice['total_harga']); ?></span></td></tr><?php if ($overtime['jam'] > 0) { ?><tr><td><strong>Biaya Overtime</strong></td><td><?= (int) $overtime['jam']; ?> jam x <?= format_rupiah(tarif_overtime_per_jam()); ?> = <strong><?= format_rupiah($overtime['biaya']); ?></strong></td></tr><?php } ?><tr><td><strong>Total Tagihan</strong></td><td><span class="invoice-box-green"><?= format_rupiah($totalTagihan); ?></span></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card panel-card mb-4">
            <div class="card-header"><h3 class="card-title">Informasi Pembayaran</h3></div>
            <div class="card-body invoice-side-list">
                <p class="text-success fw-semibold">Silakan lakukan pembayaran melalui salah satu metode di bawah ini.</p>
                <p><strong>BCA</strong> 0332680704</p>
                <p><strong></strong> LEVIA RAHMA MAHFIRATIKA </p>
                <p><strong>BNI</strong> 0051683928</p>
                <p><strong></strong> YAYUK ANI </p>
                <p><strong>BRI</strong> 627301026987538</p>
                <p><strong></strong> YAYUK ANI </p>
                <div class="page-actions no-print mt-3">
                    <?php if ($invoice['status'] == 'menunggu pembayaran') { ?>
                        <a href="upload_bukti.php?id=<?= $invoice['id_transaksi']; ?>" class="btn btn-danger w-100">Upload Bukti Pembayaran &amp; SIM A</a>
                    <?php } ?>
                    <?php if (!empty($invoice['bukti_pembayaran'])) { ?>
                        <a href="../<?= esc($invoice['bukti_pembayaran']); ?>" target="_blank" class="btn btn-outline-primary w-100">Lihat Bukti Pembayaran</a>
                    <?php } ?>
                    <?php if (!empty($invoice['sim_a'])) { ?>
                        <a href="<?= esc($simUrl); ?>" target="_blank" class="btn btn-outline-dark w-100">Lihat SIM A</a>
                    <?php } ?>
                </div>
            </div>
        </div>
        <?php if (!empty($invoice['bukti_pembayaran'])) { ?>
        <div class="card panel-card mb-4">
            <div class="card-header"><h3 class="card-title">Bukti Pembayaran</h3></div>
            <div class="card-body text-center"><img src="../<?= esc($invoice['bukti_pembayaran']); ?>" class="img-fluid rounded border" alt="Bukti Pembayaran"></div>
        </div>
        <?php } ?>
        <?php if (!empty($invoice['sim_a'])) { ?>
        <?php if (!empty($notifBooking)) { ?><div class="alert alert-info border-0 shadow-sm"><?= esc($notifBooking); ?></div><?php } ?>
<div class="card panel-card">
            <div class="card-header"><h3 class="card-title">SIM A</h3></div>
            <div class="card-body text-center"><img src="<?= esc($simUrl); ?>" class="img-fluid rounded border" alt="SIM A"></div>
        </div>
        <?php } ?>
    </div>
</div>
<?php customer_page_end(); ?>
