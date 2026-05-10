<?php
session_start();
include "../config/koneksi.php";
include "../config/helpers.php";
include "_layout.php";
bootstrap_app($conn);
admin_require_login();

$dari = isset($_GET['dari']) && $_GET['dari'] !== '' ? $_GET['dari'] : date('Y-m-01');
$sampai = isset($_GET['sampai']) && $_GET['sampai'] !== '' ? $_GET['sampai'] : date('Y-m-d');
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$where = "DATE(transaksi.tanggal_sewa) BETWEEN '$dari' AND '$sampai'";
if ($statusFilter !== '') {
    $statusDb = mysqli_real_escape_string($conn, $statusFilter);
    $where .= " AND transaksi.status='$statusDb'";
}
$query = mysqli_query($conn, "
    SELECT transaksi.*, customer.nama, mobil.nama_mobil, mobil.no_plat
    FROM transaksi
    JOIN customer ON transaksi.id_customer = customer.id_customer
    JOIN mobil ON transaksi.id_mobil = mobil.id_mobil
    WHERE $where
    ORDER BY transaksi.tanggal_sewa DESC
");
$summary = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total_transaksi,
           COALESCE(SUM(CASE WHEN status IN ('terverifikasi','disewa','selesai') THEN total_harga ELSE 0 END),0) AS total_pendapatan,
           SUM(CASE WHEN status='dibatalkan' THEN 1 ELSE 0 END) AS total_batal,
           SUM(CASE WHEN status='selesai' THEN 1 ELSE 0 END) AS total_selesai
    FROM transaksi WHERE $where
"));
$rekapMobil = mysqli_query($conn, "
    SELECT mobil.nama_mobil, COUNT(transaksi.id_transaksi) AS total_sewa
    FROM transaksi
    JOIN mobil ON transaksi.id_mobil = mobil.id_mobil
    WHERE $where AND transaksi.status NOT IN ('dibatalkan')
    GROUP BY transaksi.id_mobil
    ORDER BY total_sewa DESC, mobil.nama_mobil ASC
    LIMIT 5
");
admin_page_start('Laporan', 'laporan');
?>
<div class="row g-3 mb-4 no-print">
    <div class="col-lg-10">
        <form class="row g-2" method="GET">
            <div class="col-md-3"><label class="form-label">Dari</label><input type="date" name="dari" class="form-control" value="<?= esc($dari); ?>"></div>
            <div class="col-md-3"><label class="form-label">Sampai</label><input type="date" name="sampai" class="form-control" value="<?= esc($sampai); ?>"></div>
            <div class="col-md-3"><label class="form-label">Status</label><select name="status" class="form-select"><option value="">Semua Status</option><option value="menunggu pembayaran" <?= $statusFilter=='menunggu pembayaran'?'selected':''; ?>>Menunggu Pembayaran</option><option value="menunggu konfirmasi" <?= $statusFilter=='menunggu konfirmasi'?'selected':''; ?>>Menunggu Konfirmasi</option><option value="terverifikasi" <?= $statusFilter=='terverifikasi'?'selected':''; ?>>Terverifikasi</option><option value="dibatalkan" <?= $statusFilter=='dibatalkan'?'selected':''; ?>>Dibatalkan</option><option value="selesai" <?= $statusFilter=='selesai'?'selected':''; ?>>Selesai</option></select></div>
            <div class="col-md-3 d-grid"><label class="form-label d-none d-md-block">&nbsp;</label><button class="btn btn-brand">Tampilkan Laporan</button></div>
        </form>
    </div>
    <div class="col-lg-2 text-lg-end d-flex align-items-end justify-content-lg-end"><button onclick="window.print()" class="btn btn-outline-secondary w-100"><i class="bi bi-printer me-1"></i> Print</button></div>
</div>
<div class="row g-4 mb-4">
    <div class="col-md-3"><div class="card metric-card h-100"><div class="card-body"><span class="metric-icon" style="background:#5a67f2;"><i class="bi bi-file-earmark-text"></i></span><div><div class="metric-label">Total Transaksi</div><p class="metric-value"><?= (int)$summary['total_transaksi']; ?></p></div></div></div></div>
    <div class="col-md-3"><div class="card metric-card h-100"><div class="card-body"><span class="metric-icon" style="background:#36c56a;"><i class="bi bi-cash-stack"></i></span><div><div class="metric-label">Pendapatan</div><p class="metric-value" style="font-size:1.3rem;"><?= format_rupiah($summary['total_pendapatan']); ?></p></div></div></div></div>
    <div class="col-md-3"><div class="card metric-card h-100"><div class="card-body"><span class="metric-icon" style="background:#ffb020;"><i class="bi bi-check2-square"></i></span><div><div class="metric-label">Selesai</div><p class="metric-value"><?= (int)$summary['total_selesai']; ?></p></div></div></div></div>
    <div class="col-md-3"><div class="card metric-card h-100"><div class="card-body"><span class="metric-icon" style="background:#ef4444;"><i class="bi bi-x-circle"></i></span><div><div class="metric-label">Dibatalkan</div><p class="metric-value"><?= (int)$summary['total_batal']; ?></p></div></div></div></div>
</div>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card panel-card h-100"><div class="card-header"><h3 class="card-title">Mobil Paling Sering Disewa</h3></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-soft mb-0"><thead><tr><th>Mobil</th><th>Total Sewa</th></tr></thead><tbody><?php if ($rekapMobil && mysqli_num_rows($rekapMobil) > 0) { while ($m = mysqli_fetch_assoc($rekapMobil)) { ?><tr><td><?= esc($m['nama_mobil']); ?></td><td><?= (int)$m['total_sewa']; ?></td></tr><?php } } else { ?><tr><td colspan="2"><div class="empty-box">Belum ada data.</div></td></tr><?php } ?></tbody></table></div></div></div>
    </div>
    <div class="col-lg-8">
        <div class="card panel-card"><div class="card-header"><h3 class="card-title">Detail Transaksi</h3></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-soft align-middle mb-0"><thead><tr><th>No</th><th>Tanggal</th><th>Customer</th><th>Mobil</th><th>No Plat</th><th>Total</th><th>Status</th></tr></thead><tbody><?php if ($query && mysqli_num_rows($query) > 0) { $no=1; while ($row = mysqli_fetch_assoc($query)) { ?><tr><td><?= $no++; ?></td><td><?= format_tanggal_id($row['tanggal_sewa']); ?></td><td><?= esc($row['nama']); ?></td><td><?= esc($row['nama_mobil']); ?></td><td><?= esc($row['no_plat']); ?></td><td><?= format_rupiah($row['total_harga']); ?></td><td><?= badge_label_html($row); ?></td></tr><?php } } else { ?><tr><td colspan="7"><div class="empty-box">Tidak ada transaksi pada rentang ini.</div></td></tr><?php } ?></tbody></table></div></div></div>
    </div>
</div>
<?php admin_page_end(); ?>