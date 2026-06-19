<?php
session_start();
include "../config/koneksi.php";
include "../config/helpers.php";
include "_layout.php";
bootstrap_app($conn);
customer_require_login();

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$where = '1=1';
if ($q !== '') {
    $qdb = mysqli_real_escape_string($conn, $q);
    $where .= " AND (nama_mobil LIKE '%$qdb%' OR merk LIKE '%$qdb%' OR no_plat LIKE '%$qdb%')";
}

/*
 * Query utama dibuat langsung dari tabel mobil agar data armada tetap tampil.
 * Pengecekan transaksi dilakukan setelah data mobil dibaca supaya halaman tidak kosong
 * apabila struktur tabel transaksi berbeda saat database sedang direvisi.
 */
$query = mysqli_query($conn, "SELECT * FROM mobil WHERE $where ORDER BY id_mobil DESC");

$transaksiSiap = table_has_column($conn, 'transaksi', 'id_mobil')
    && table_has_column($conn, 'transaksi', 'status')
    && table_has_column($conn, 'transaksi', 'tanggal_sewa');
$punyaSelesaiAdmin = table_has_column($conn, 'transaksi', 'selesai_admin_at');
$punyaCatatanAdmin = table_has_column($conn, 'transaksi', 'catatan_admin');

customer_page_start('Armada Kami', 'mobil');
?>
<div class="row mb-4 align-items-center">
    <div class="col-lg-7 mb-3 mb-lg-0"><p class="top-note mb-0">Pilih armada sesuai jadwal Anda. Sistem akan menolak booking jika tanggal dan jam bentrok dengan customer lain.</p></div>
    <div class="col-lg-5">
        <form class="d-flex gap-2" method="GET">
            <input type="text" class="form-control" name="q" value="<?= esc($q); ?>" placeholder="Cari nama mobil, merk, no plat...">
            <button class="btn btn-brand">Cari</button>
        </form>
    </div>
</div>
<div class="row g-4">
    <?php if ($query && mysqli_num_rows($query) > 0) { while($m = mysqli_fetch_assoc($query)) { 
        $m['sedang_disewa'] = 0;
        $m['jadwal_terdekat'] = null;

        if ($transaksiSiap) {
            $idMobil = (int) $m['id_mobil'];
            $selesaiAktif = $punyaSelesaiAdmin ? "AND (t.selesai_admin_at IS NULL OR t.selesai_admin_at = '0000-00-00 00:00:00')" : "";
            $selesaiJadwal = $punyaSelesaiAdmin ? "AND (t2.selesai_admin_at IS NULL OR t2.selesai_admin_at = '0000-00-00 00:00:00')" : "";
            $bukanBatal = $punyaCatatanAdmin ? "AND LOWER(COALESCE(t2.catatan_admin,'')) NOT LIKE '%batal%'" : "";

            $cekAktif = mysqli_query($conn, "
                SELECT COUNT(*) AS total
                FROM transaksi t
                WHERE t.id_mobil = '$idMobil'
                  AND t.status IN ('terverifikasi', 'disewa')
                  $selesaiAktif
                  AND NOW() >= t.tanggal_sewa
            ");
            if ($cekAktif) {
                $rowAktif = mysqli_fetch_assoc($cekAktif);
                $m['sedang_disewa'] = ((int) ($rowAktif['total'] ?? 0) > 0) ? 1 : 0;
            }

            $cekJadwal = mysqli_query($conn, "
                SELECT MIN(t2.tanggal_sewa) AS jadwal_terdekat
                FROM transaksi t2
                WHERE t2.id_mobil = '$idMobil'
                  AND t2.status IN ('menunggu pembayaran','menunggu konfirmasi','terverifikasi','disewa')
                  $selesaiJadwal
                  $bukanBatal
                  AND t2.tanggal_sewa > NOW()
            ");
            if ($cekJadwal) {
                $rowJadwal = mysqli_fetch_assoc($cekJadwal);
                $m['jadwal_terdekat'] = $rowJadwal['jadwal_terdekat'] ?? null;
            }
        }
    ?>
    <div class="col-md-6 col-xl-4">
        <div class="customer-grid-card h-100">
            <div class="image-wrap">
                <img src="../uploads/mobil/<?= esc($m['foto']); ?>" alt="<?= esc($m['nama_mobil']); ?>">
            </div>
            <div class="content">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                    <div>
                        <h5 class="mb-1"><?= esc($m['nama_mobil']); ?></h5>
                        <div class="top-note"><?= esc($m['merk']); ?> • <?= esc($m['no_plat']); ?></div>
                    </div>
                    <?php 
                        $statusMobilView = 'tersedia';
                        if (($m['status'] ?? '') === 'sedang diservice') {
                            $statusMobilView = 'sedang diservice';
                        } elseif ((int) $m['sedang_disewa'] === 1 || ($m['status'] ?? '') === 'disewa') {
                            $statusMobilView = 'disewa';
                        }
                        $infoMobilView = mobil_status_info($statusMobilView);
                    ?>
                    <span class="status-pill <?= esc($infoMobilView['class']); ?>"><?= esc($infoMobilView['label']); ?></span>
                </div>
                <div class="preview-kpi mb-3">
                    <div class="mini-card"><div class="metric-label">12 Jam</div><div class="metric-value"><?= format_rupiah($m['harga_12jam']); ?></div></div>
                    <div class="mini-card"><div class="metric-label">24 Jam</div><div class="metric-value"><?= format_rupiah($m['harga_24jam']); ?></div></div>
                </div>
                <?php if (!empty($m['deskripsi'])) { ?>
                    <p class="top-note mb-3"><?= esc($m['deskripsi']); ?></p>
                <?php } ?>
                <?php if (!empty($m['jadwal_terdekat'])) { ?>
                    <div class="booking-summary-box mb-3">
                        <div class="top-note">Jadwal terdekat</div>
                        <strong><?= format_tanggal_id($m['jadwal_terdekat']); ?></strong>
                    </div>
                <?php } ?>
                <?php if ($statusMobilView === 'sedang diservice') { ?>
                    <button class="btn btn-outline-secondary w-100" disabled>Mobil Sedang Diservice</button>
                <?php } elseif ($statusMobilView === 'disewa') { ?>
                    <button class="btn btn-outline-secondary w-100" disabled>Mobil Sedang Disewa</button>
                <?php } else { ?>
                    <a href="sewa.php?id=<?= $m['id_mobil']; ?>" class="btn btn-brand w-100">Booking Mobil Ini</a>
                <?php } ?>
            </div>
        </div>
    </div>
    <?php }} else { ?>
    <div class="col-12"><div class="empty-box">Tidak ada armada yang ditemukan.</div></div>
    <?php } ?>
</div>
<?php customer_page_end(); ?>
