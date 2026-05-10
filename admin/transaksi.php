<?php
session_start();
include "../config/koneksi.php";
include "../config/helpers.php";
include "_layout.php";
bootstrap_app($conn);
admin_require_login();

$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
$where = "1=1";
if ($statusFilter !== '') {
    $statusFilterDb = mysqli_real_escape_string($conn, $statusFilter);
    $where .= " AND transaksi.status='$statusFilterDb'";
}
if ($keyword !== '') {
    $k = mysqli_real_escape_string($conn, $keyword);
    $where .= " AND (customer.nama LIKE '%$k%' OR mobil.nama_mobil LIKE '%$k%' OR mobil.no_plat LIKE '%$k%')";
}
$query = mysqli_query($conn, "
    SELECT transaksi.*, customer.nama, customer.foto AS foto_ktp, mobil.nama_mobil, mobil.no_plat
    FROM transaksi
    JOIN customer ON transaksi.id_customer = customer.id_customer
    JOIN mobil ON transaksi.id_mobil = mobil.id_mobil
    WHERE $where
    ORDER BY transaksi.id_transaksi DESC
");
admin_page_start('Transaksi', 'transaksi');
?>
<div class="row mb-4 g-3 align-items-end">
    <div class="col-lg-8">
        <form method="GET" class="row g-2">
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="menunggu pembayaran" <?= $statusFilter=='menunggu pembayaran'?'selected':''; ?>>Menunggu Pembayaran</option>
                    <option value="menunggu konfirmasi" <?= $statusFilter=='menunggu konfirmasi'?'selected':''; ?>>Menunggu Konfirmasi</option>
                    <option value="terverifikasi" <?= $statusFilter=='terverifikasi'?'selected':''; ?>>Terverifikasi</option>
                    <option value="disewa" <?= $statusFilter=='disewa'?'selected':''; ?>>Disewa</option>
                    <option value="dibatalkan" <?= $statusFilter=='dibatalkan'?'selected':''; ?>>Dibatalkan</option>
                    <option value="selesai" <?= $statusFilter=='selesai'?'selected':''; ?>>Selesai</option>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label">Cari</label>
                <input type="text" name="q" class="form-control" value="<?= esc($keyword); ?>" placeholder="Customer, mobil, no plat...">
            </div>
            <div class="col-md-2 d-grid"><label class="form-label d-none d-md-block"></label><button class="btn btn-brand">Filter</button></div>
        </form>
    </div>
    <div class="col-lg-4 text-lg-end">
        <a href="laporan.php" class="btn btn-outline-secondary"><i class="bi bi-printer me-1"></i> Laporan</a>
    </div>
</div>

<div class="card panel-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-soft align-middle mb-0">
                <thead>
                    <tr>
                        <th>No</th><th>Customer</th><th>Mobil</th><th>Jadwal</th><th>Total</th><th>KTP</th><th>Bukti Bayar</th><th>SIM A</th><th>Status</th><th>Catatan</th><th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($query && mysqli_num_rows($query) > 0) { $no=1; while($d = mysqli_fetch_assoc($query)){ $statusEfektif = status_transaksi_efektif($d); $d['status'] = $statusEfektif; $ot = hitung_overtime_data($d); $totalTagihan = total_tagihan_transaksi($d); $ktpUrl = secure_file_url('customer_ktp', $d['id_customer']); $simUrl = secure_file_url('sim_a', $d['id_transaksi']); ?>
                    <tr class="<?= $ot['jam'] > 0 && in_array($d['status'], ['terverifikasi','disewa']) ? 'table-warning-soft' : ''; ?>">
                        <td><?= $no++; ?></td>
                        <td><strong><?= esc($d['nama']); ?></strong></td>
                        <td><strong><?= esc($d['nama_mobil']); ?></strong><br><span class="top-note"><?= esc($d['no_plat']); ?></span></td>
                        <td>
                            <?= format_tanggal_id($d['tanggal_sewa']); ?><br>
                            <span class="top-note">s/d <?= format_tanggal_id($d['tanggal_kembali']); ?></span>
                            <?php if ($ot['jam'] > 0 && in_array($d['status'], ['terverifikasi','disewa'])) { ?>
                                <div class="top-note text-danger mt-1">Overtime <?= (int) $ot['jam']; ?> jam</div>
                            <?php } ?>
                        </td>
                        <td>
                            <strong><?= format_rupiah($totalTagihan); ?></strong>
                            <?php if ($ot['jam'] > 0) { ?>
                                <br><span class="top-note text-danger">Denda <?= format_rupiah($ot['biaya']); ?></span>
                            <?php } ?>
                        </td>
                        <td>
                            <?php if(!empty($d['foto_ktp'])){ ?>
                                <a href="#" data-bs-toggle="modal" data-bs-target="#ktp<?= $d['id_transaksi']; ?>"><img src="<?= esc($ktpUrl); ?>" class="table-thumb" alt="KTP"></a>
                                <div class="modal fade" id="ktp<?= $d['id_transaksi']; ?>"><div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content"><div class="modal-body text-center"><img src="<?= esc($ktpUrl); ?>" class="img-fluid rounded"></div></div></div></div>
                            <?php } else { ?><span class="top-note">-</span><?php } ?>
                        </td>
                        <td>
                            <?php if(!empty($d['bukti_pembayaran'])){ ?>
                                <a href="#" data-bs-toggle="modal" data-bs-target="#bukti<?= $d['id_transaksi']; ?>"><img src="../<?= esc($d['bukti_pembayaran']); ?>" class="table-thumb" alt="Bukti"></a>
                                <div class="modal fade" id="bukti<?= $d['id_transaksi']; ?>"><div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content"><div class="modal-body text-center"><img src="../<?= esc($d['bukti_pembayaran']); ?>" class="img-fluid rounded"></div></div></div></div>
                            <?php } else { ?><span class="top-note">Belum</span><?php } ?>
                        </td>
                        <td>
                            <?php if(!empty($d['sim_a'])){ ?>
                                <a href="#" data-bs-toggle="modal" data-bs-target="#sim<?= $d['id_transaksi']; ?>"><img src="<?= esc($simUrl); ?>" class="table-thumb" alt="SIM A"></a>
                                <div class="modal fade" id="sim<?= $d['id_transaksi']; ?>"><div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content"><div class="modal-body text-center"><img src="<?= esc($simUrl); ?>" class="img-fluid rounded"></div></div></div></div>
                            <?php } else { ?><span class="top-note">Belum</span><?php } ?>
                        </td>
                        <td><?= badge_label_html($d); ?></td>
                        <td>
                            <?php if (!empty($d['catatan_admin'])) { ?>
                                <?= esc($d['catatan_admin']); ?>
                            <?php } elseif ($ot['jam'] > 0 && in_array($d['status'], ['terverifikasi','disewa'])) { ?>
                                <span class="text-danger">Belum selesai admin. Mobil tetap terkunci.</span>
                            <?php } elseif ($d['status'] === 'selesai' && (float) ($d['overtime_biaya'] ?? 0) > 0) { ?>
                                Selesai admin dengan overtime <?= (int) ($d['overtime_jam'] ?? 0); ?> jam.
                            <?php } else { ?>
                                <span class="top-note">-</span>
                            <?php } ?>
                        </td>
                        <td>
                            <div class="action-stack">
                                <?php if($d['status'] == 'menunggu konfirmasi'){ ?>
                                    <a href="verifikasi.php?id=<?= $d['id_transaksi']; ?>" class="btn btn-success btn-sm" onclick="return confirm('Verifikasi pesanan ini sebagai SIAP DISEWA? Customer akan menerima notifikasi pengambilan mobil 20 menit sebelum jam sewa.')">Verifikasi Siap Disewa</a>
                                    <a href="batalkan.php?id=<?= $d['id_transaksi']; ?>" class="btn btn-outline-danger btn-sm">Batalkan Pesanan</a>
                                <?php } elseif($d['status'] == 'menunggu pembayaran'){ ?>
                                    <a href="batalkan.php?id=<?= $d['id_transaksi']; ?>" class="btn btn-outline-danger btn-sm">Batalkan Pesanan</a>
                                <?php } elseif(in_array($d['status'], ['terverifikasi','disewa'])){ ?>
                                    <a href="selesai.php?id=<?= $d['id_transaksi']; ?>" class="btn btn-outline-primary btn-sm" onclick="return confirm('Konfirmasi pesanan ini sudah selesai? Jika admin belum klik selesai, mobil tetap tidak bisa disewa customer lain dan overtime akan terus berjalan Rp 30.000 per jam.')">Konfirmasi Mobil Selesai</a>
                                    <div class="top-note mt-1 text-muted">Tombol ini tetap tampil setelah verifikasi. Selama belum diklik selesai, mobil tetap terkunci untuk customer lain.</div>
                                <?php } else { ?><span class="top-note">Tidak ada aksi</span><?php } ?>
                            </div>
                        </td>
                    </tr>
                <?php } } else { ?>
                    <tr><td colspan="11"><div class="empty-box">Belum ada data transaksi.</div></td></tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php admin_page_end(); ?>
