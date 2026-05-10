<?php
session_start();
include "../config/koneksi.php";
include "../config/helpers.php";
include "_layout.php";
bootstrap_app($conn);
customer_require_login();

$mobilQuery = mysqli_query($conn, "
    SELECT m.*,
           EXISTS(
               SELECT 1 FROM transaksi t
               WHERE t.id_mobil = m.id_mobil
                 AND t.status IN ('terverifikasi', 'disewa')
                 AND NOW() >= t.tanggal_sewa
           ) AS sedang_disewa,
           (
               SELECT MIN(t2.tanggal_sewa) FROM transaksi t2
               WHERE t2.id_mobil = m.id_mobil
                 AND t2.status IN ('menunggu pembayaran','menunggu konfirmasi','terverifikasi','disewa')
                 AND t2.tanggal_sewa > NOW()
           ) AS jadwal_terdekat
    FROM mobil m
    ORDER BY m.id_mobil DESC
");

$tersedia = array();
$aktif = array();
while ($row = mysqli_fetch_assoc($mobilQuery)) {
    if ((int) $row['sedang_disewa'] === 1) {
        $aktif[] = $row;
    } else {
        $tersedia[] = $row;
    }
}

$idUser = (int) $_SESSION['id_user'];
$summary = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT 
        COUNT(*) AS total_booking,
        SUM(CASE WHEN status='menunggu konfirmasi' THEN 1 ELSE 0 END) AS menunggu_konfirmasi,
        SUM(CASE WHEN status IN ('terverifikasi','disewa') THEN 1 ELSE 0 END) AS aktif,
        SUM(CASE WHEN status='selesai' THEN 1 ELSE 0 END) AS selesai
    FROM transaksi
    WHERE id_customer='$idUser'
"));

$latestTrans = mysqli_query($conn, "
    SELECT t.*, m.nama_mobil, m.no_plat
    FROM transaksi t
    JOIN mobil m ON t.id_mobil = m.id_mobil
    WHERE t.id_customer='$idUser'
    ORDER BY t.id_transaksi DESC
    LIMIT 5
");

customer_page_start('Dashboard Customer', 'dashboard');
?>
<div class="row mb-4">
    <div class="col-12">
        <div class="callout-info-soft customer-hero">
            <div>
                <div class="hero-badge mb-2"><i class="bi bi-stars"></i> Brawijaya Rental Mobil</div>
                <h5 class="mb-1">Selamat datang, <?= esc($_SESSION['nama']); ?></h5>
                <p>Silahkan melakukan boking sesuai jadwal yang tersedia.</p>
            </div>
            
        </div>
    </div>
</div>

<?php
$notifAktif = mysqli_query($conn, "
    SELECT t.*, m.nama_mobil
    FROM transaksi t
    JOIN mobil m ON t.id_mobil = m.id_mobil
    WHERE t.id_customer='$idUser' AND t.status IN ('terverifikasi','disewa')
    ORDER BY t.tanggal_sewa ASC
    LIMIT 3
");
if ($notifAktif && mysqli_num_rows($notifAktif) > 0) {
?>
<div class="row mb-4">
    <div class="col-12">
        <?php while ($n = mysqli_fetch_assoc($notifAktif)) { $pesanNotif = customer_booking_notification($n); if (!$pesanNotif) continue; ?>
            <div class="alert alert-info border-0 shadow-sm mb-3">
                <strong><?= esc($n['nama_mobil']); ?>:</strong> <?= esc($pesanNotif); ?>
            </div>
        <?php } ?>
    </div>
</div>
<?php } ?>

<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3"><div class="card metric-card h-100"><div class="card-body"><span class="metric-icon" style="background:#5a67f2;"><i class="bi bi-car-front-fill"></i></span><div><div class="metric-label">Mobil Siap Booking</div><p class="metric-value"><?= count($tersedia); ?></p></div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card metric-card h-100"><div class="card-body"><span class="metric-icon" style="background:#ffb020;"><i class="bi bi-clock-history"></i></span><div><div class="metric-label">Booking Saya</div><p class="metric-value"><?= (int) ($summary['total_booking'] ?? 0); ?></p></div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card metric-card h-100"><div class="card-body"><span class="metric-icon" style="background:#06b6d4;"><i class="bi bi-patch-check-fill"></i></span><div><div class="metric-label">Menunggu Konfirmasi</div><p class="metric-value"><?= (int) ($summary['menunggu_konfirmasi'] ?? 0); ?></p></div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card metric-card h-100"><div class="card-body"><span class="metric-icon" style="background:#36c56a;"><i class="bi bi-check2-square"></i></span><div><div class="metric-label">Transaksi Selesai</div><p class="metric-value"><?= (int) ($summary['selesai'] ?? 0); ?></p></div></div></div></div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <div class="card panel-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Armada Siap Disewa</h3>
                <a href="mobil.php" class="btn btn-sm btn-brand">Lihat Semua</a>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <?php if (count($tersedia) > 0) { foreach (array_slice($tersedia, 0, 4) as $m) { ?>
                    <div class="col-md-6">
                        <div class="customer-grid-card h-100">
                            <div class="image-wrap">
                                <img src="../uploads/mobil/<?= esc($m['foto']); ?>" alt="<?= esc($m['nama_mobil']); ?>">
                            </div>
                            <div class="content">
                                <h5 class="mb-1"><?= esc($m['nama_mobil']); ?></h5>
                                <p class="top-note mb-2"><?= esc($m['merk']); ?> • Tahun <?= esc($m['tahun']); ?></p>
                                <div class="mb-2"><strong><?= format_rupiah($m['harga_24jam']); ?></strong> / 24 jam</div>
                                <div class="top-note mb-3">12 jam <?= format_rupiah($m['harga_12jam']); ?></div>
                                <?php if (!empty($m['jadwal_terdekat'])) { ?>
                                    <div class="booking-summary-box mb-3">
                                        <div class="top-note">Jadwal berikutnya</div>
                                        <strong><?= format_tanggal_id($m['jadwal_terdekat']); ?></strong>
                                    </div>
                                <?php } ?>
                                <a href="sewa.php?id=<?= $m['id_mobil']; ?>" class="btn btn-brand w-100">Booking Sekarang</a>
                            </div>
                        </div>
                    </div>
                    <?php }} else { ?>
                    <div class="col-12"><div class="empty-box">Saat ini tidak ada mobil yang bisa disewa.</div></div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card dashboard-card mb-4">
            <div class="card-header"><h3 class="card-title">Status Rental Saya</h3></div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2"><span>Rental Aktif</span><strong><?= (int) ($summary['aktif'] ?? 0); ?></strong></div>
                <div class="progress mb-3"><div class="progress-bar bg-primary" style="width: <?= max(8, min(100, (int) ($summary['aktif'] ?? 0) * 20)); ?>%"></div></div>
                <div class="d-flex justify-content-between mb-2"><span>Menunggu Konfirmasi</span><strong><?= (int) ($summary['menunggu_konfirmasi'] ?? 0); ?></strong></div>
                <div class="progress mb-3"><div class="progress-bar bg-info" style="width: <?= max(8, min(100, (int) ($summary['menunggu_konfirmasi'] ?? 0) * 20)); ?>%"></div></div>
                <div class="d-flex justify-content-between mb-2"><span>Transaksi Selesai</span><strong><?= (int) ($summary['selesai'] ?? 0); ?></strong></div>
                <div class="progress"><div class="progress-bar bg-success" style="width: <?= max(8, min(100, (int) ($summary['selesai'] ?? 0) * 20)); ?>%"></div></div>
            </div>
        </div>
        <div class="card dashboard-card">
            <div class="card-header"><h3 class="card-title">Mobil Sedang Dipakai Sekarang</h3></div>
            <div class="card-body">
                <?php if (count($aktif) > 0) { foreach (array_slice($aktif, 0, 5) as $m) { ?>
                    <div class="d-flex align-items-center gap-3 border-bottom py-2">
                        <img src="../uploads/mobil/<?= esc($m['foto']); ?>" class="table-thumb" alt="<?= esc($m['nama_mobil']); ?>">
                        <div>
                            <strong><?= esc($m['nama_mobil']); ?></strong>
                            <div class="top-note"><?= esc($m['merk']); ?> • Sedang disewa</div>
                        </div>
                    </div>
                <?php }} else { ?>
                    <div class="empty-box">Belum ada mobil yang sedang dipakai saat ini.</div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>


<?php customer_page_end(); ?>
