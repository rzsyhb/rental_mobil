<?php
session_start();
include "../config/koneksi.php";
include "../config/helpers.php";
include "_layout.php";
bootstrap_app($conn);
admin_require_login();

$jml_mobil = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM mobil"))['total'];
$jml_customer = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM customer"))['total'];
$jml_transaksi = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM transaksi"))['total'];
$jml_selesai = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM transaksi WHERE status='selesai'"))['total'];
$jml_menunggu = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM transaksi WHERE status='menunggu konfirmasi'"))['total'];
$jml_bayar = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM transaksi WHERE status='menunggu pembayaran'"))['total'];
$jml_aktif = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM transaksi WHERE status IN ('terverifikasi','disewa') AND NOW() >= tanggal_sewa"))['total'];
$jml_batal = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM transaksi WHERE status='dibatalkan'"))['total'];
$notifBaru = $jml_bayar + $jml_menunggu;

$notifQuery = mysqli_query($conn, "
    SELECT t.*, c.nama, m.nama_mobil
    FROM transaksi t
    JOIN customer c ON t.id_customer = c.id_customer
    JOIN mobil m ON t.id_mobil = m.id_mobil
    WHERE t.status IN ('menunggu pembayaran','menunggu konfirmasi')
    ORDER BY t.id_transaksi DESC
    LIMIT 6
");

$chartRows = mysqli_query($conn, "
    SELECT DATE_FORMAT(tanggal_sewa, '%Y-%m') AS bulan, COUNT(*) AS total
    FROM transaksi
    WHERE tanggal_sewa >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(tanggal_sewa, '%Y-%m')
    ORDER BY bulan ASC
");
$chartLabels = [];
$chartValues = [];
while ($r = mysqli_fetch_assoc($chartRows)) {
    $chartLabels[] = $r['bulan'];
    $chartValues[] = (int) $r['total'];
}

$topRows = mysqli_query($conn, "
    SELECT m.nama_mobil, COUNT(t.id_transaksi) AS total
    FROM transaksi t
    JOIN mobil m ON t.id_mobil = m.id_mobil
    WHERE t.status NOT IN ('dibatalkan')
    GROUP BY t.id_mobil
    ORDER BY total DESC, m.nama_mobil ASC
    LIMIT 5
");
$topLabels = [];
$topValues = [];
while ($r = mysqli_fetch_assoc($topRows)) {
    $topLabels[] = $r['nama_mobil'];
    $topValues[] = (int) $r['total'];
}

$harianRows = mysqli_query($conn, "
    SELECT DATE(tanggal_sewa) AS tanggal, COUNT(*) AS total
    FROM transaksi
    WHERE tanggal_sewa >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
    GROUP BY DATE(tanggal_sewa)
    ORDER BY tanggal ASC
");
$harianLabels = [];
$harianValues = [];
while ($r = mysqli_fetch_assoc($harianRows)) {
    $harianLabels[] = $r['tanggal'];
    $harianValues[] = (int) $r['total'];
}

admin_page_start('Dashboard', 'dashboard', '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>');
?>
<div class="row mb-4">
    <div class="col-12">
        <div class="callout-info-soft">
            <strong>Ringkasan admin:</strong>
            <?= $jml_bayar; ?> transaksi menunggu upload/cek pembayaran, <?= $jml_menunggu; ?> booking menunggu verifikasi, <?= $jml_aktif; ?> rental masih aktif atau belum diselesaikan admin.
        </div>
    </div>
</div>

<?php if ($notifBaru > 0) { ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="notice-card">
            <strong><i class="bi bi-bell-fill me-2"></i>Notifikasi transaksi baru</strong>
            Ada <?= $notifBaru; ?> transaksi customer yang perlu segera diproses admin.
            <div class="mt-3"><a href="transaksi.php?status=menunggu%20konfirmasi" class="btn btn-brand btn-sm">Buka Halaman Transaksi</a></div>
        </div>
    </div>
</div>
<?php } ?>

<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3"><div class="card metric-card h-100"><div class="card-body"><span class="metric-icon" style="background:#5a67f2;"><i class="bi bi-car-front-fill"></i></span><div><div class="metric-label">Total Mobil</div><p class="metric-value"><?= $jml_mobil; ?></p></div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card metric-card h-100"><div class="card-body"><span class="metric-icon" style="background:#ff5e57;"><i class="bi bi-person-fill"></i></span><div><div class="metric-label">Total Customer</div><p class="metric-value"><?= $jml_customer; ?></p></div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card metric-card h-100"><div class="card-body"><span class="metric-icon" style="background:#ffb020;"><i class="bi bi-file-earmark-text-fill"></i></span><div><div class="metric-label">Total Rental</div><p class="metric-value"><?= $jml_transaksi; ?></p></div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card metric-card h-100"><div class="card-body"><span class="metric-icon" style="background:#36c56a;"><i class="bi bi-check2-square"></i></span><div><div class="metric-label">Rental Selesai</div><p class="metric-value"><?= $jml_selesai; ?></p></div></div></div></div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <div class="card dashboard-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Antrian Transaksi yang Perlu Diproses</h3>
                <a href="transaksi.php" class="btn btn-sm btn-brand">Lihat Semua</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-dashboard align-middle mb-0">
                        <thead>
                            <tr><th>Customer</th><th>Mobil</th><th>Mulai Sewa</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                        <?php if ($notifQuery && mysqli_num_rows($notifQuery) > 0) { ?>
                            <?php while ($row = mysqli_fetch_assoc($notifQuery)) { ?>
                                <tr>
                                    <td><?= esc($row['nama']); ?></td>
                                    <td><?= esc($row['nama_mobil']); ?></td>
                                    <td><?= format_tanggal_id($row['tanggal_sewa']); ?></td>
                                    <td><?= badge_label_html($row); ?></td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr><td colspan="4"><div class="empty-box py-3">Belum ada transaksi yang menunggu diproses.</div></td></tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card dashboard-card mb-4">
            <div class="card-header"><h3 class="card-title">Akses Cepat</h3></div>
            <div class="card-body">
                <div class="page-actions">
                    <a href="tambah_mobil.php" class="btn btn-brand"><i class="bi bi-plus-circle me-1"></i> Tambah Data</a>
                    <a href="laporan.php" class="btn btn-outline-secondary"><i class="bi bi-printer me-1"></i> Laporan</a>
                    <a href="analitik.php" class="btn btn-outline-primary"><i class="bi bi-bar-chart-line me-1"></i> Analitik</a>
                </div>
            </div>
        </div>
        <div class="card dashboard-card">
            <div class="card-header"><h3 class="card-title">Status Booking</h3></div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2"><span>Menunggu Pembayaran</span><strong><?= $jml_bayar; ?></strong></div>
                <div class="progress mb-3" role="progressbar"><div class="progress-bar bg-info" style="width: <?= max(8, min(100, $jml_transaksi ? round(($jml_bayar / max(1,$jml_transaksi))*100) : 8)); ?>%"></div></div>
                <div class="d-flex justify-content-between mb-2"><span>Menunggu Verifikasi</span><strong><?= $jml_menunggu; ?></strong></div>
                <div class="progress mb-3" role="progressbar"><div class="progress-bar bg-warning" style="width: <?= max(8, min(100, $jml_transaksi ? round(($jml_menunggu / max(1,$jml_transaksi))*100) : 8)); ?>%"></div></div>
                <div class="d-flex justify-content-between mb-2"><span>Rental Aktif</span><strong><?= $jml_aktif; ?></strong></div>
                <div class="progress mb-3" role="progressbar"><div class="progress-bar bg-primary" style="width: <?= max(8, min(100, $jml_transaksi ? round(($jml_aktif / max(1,$jml_transaksi))*100) : 8)); ?>%"></div></div>
                <div class="d-flex justify-content-between mb-2"><span>Dibatalkan</span><strong><?= $jml_batal; ?></strong></div>
                <div class="progress" role="progressbar"><div class="progress-bar bg-danger" style="width: <?= max(8, min(100, $jml_transaksi ? round(($jml_batal / max(1,$jml_transaksi))*100) : 8)); ?>%"></div></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-lg-6">
        <div class="card dashboard-card chart-card">
            <div class="card-header"><h3 class="card-title">Trend Booking 6 Bulan Terakhir</h3></div>
            <div class="card-body"><canvas id="trendChart"></canvas></div>
        </div>
    </div>
    
</div>
<?php
admin_page_end('<script>
const trendCtx = document.getElementById("trendChart");
if (trendCtx) new Chart(trendCtx, {type:"line", data:{labels:' . json_encode($chartLabels) . ', datasets:[{label:"Jumlah Booking", data:' . json_encode($chartValues) . ', borderWidth:2, tension:.35, fill:false}]}, options:{plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}}}});
const dailyCtx = document.getElementById("dailyChart");
if (dailyCtx) new Chart(dailyCtx, {type:"bar", data:{labels:' . json_encode($harianLabels) . ', datasets:[{label:"Jumlah Penyewa", data:' . json_encode($harianValues) . ', borderWidth:1}]}, options:{plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}}}});
const topCtx = document.getElementById("topChart");
if (topCtx) new Chart(topCtx, {type:"bar", data:{labels:' . json_encode($topLabels) . ', datasets:[{label:"Total Sewa", data:' . json_encode($topValues) . ', borderWidth:1}]}, options:{plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}}}});
</script>');
?>