<?php
session_start();
include "../config/koneksi.php";
include "../config/helpers.php";
include "_layout.php";
bootstrap_app($conn);
customer_require_login();

$id_user = (int) $_SESSION['id_user'];
$query = mysqli_query($conn, "
    SELECT transaksi.*, mobil.nama_mobil, mobil.no_plat
    FROM transaksi
    JOIN mobil ON transaksi.id_mobil = mobil.id_mobil
    WHERE transaksi.id_customer = '$id_user'
    ORDER BY transaksi.id_transaksi DESC
");
customer_page_start('Riwayat Transaksi', 'transaksi');
?>
<div class="row mb-4 align-items-center">
    <div class="col-lg-7 mb-3 mb-lg-0"><p class="top-note mb-0">Lihat invoice, upload dokumen pembayaran dan SIM A, serta cek status verifikasi dari admin.</p></div>
    <div class="col-lg-5 text-lg-end"><a href="dashboard.php" class="btn btn-outline-secondary">Kembali ke Dashboard</a></div>
</div>
<div class="card panel-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-soft align-middle mb-0">
                <thead>
                    <tr>
                        <th>No</th><th>Mobil</th><th>No Plat</th><th>Tanggal Sewa</th><th>Tanggal Kembali</th><th>Total Harga</th><th>Status</th><th>Catatan Admin</th><th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php $no = 1; if ($query && mysqli_num_rows($query) > 0) { while ($data = mysqli_fetch_assoc($query)) { $status = transaksi_status_info($data); ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><?= esc($data['nama_mobil']); ?></td>
                        <td><?= esc($data['no_plat']); ?></td>
                        <td><?= format_tanggal_id($data['tanggal_sewa']); ?></td>
                        <td><?= format_tanggal_id($data['tanggal_kembali']); ?></td>
                        <td><strong><?= format_rupiah(total_tagihan_transaksi($data)); ?></strong><?php $ot = hitung_overtime_data($data); if ($ot['jam'] > 0) { ?><br><span class="top-note text-danger">Overtime <?= (int) $ot['jam']; ?> jam (<?= format_rupiah($ot['biaya']); ?>)</span><?php } ?></td>
                        <td><span class="status-pill <?= status_pill_class($status['class']); ?>"><?= esc($status['label']); ?></span></td>
                        <td>
                            <?php $notifBooking = customer_booking_notification($data); ?>
                            <?php if (!empty($data['catatan_admin'])) { ?>
                                <?= esc($data['catatan_admin']); ?>
                                <?php if (!empty($notifBooking) && in_array($data['status'], ['terverifikasi','disewa'], true)) { ?>
                                    <br><span class="top-note text-primary"><?= esc($notifBooking); ?></span>
                                <?php } ?>
                            <?php } elseif (!empty($notifBooking)) { ?>
                                <span class="text-primary"><?= esc($notifBooking); ?></span>
                            <?php } else { ?>
                                -
                            <?php } ?>
                        </td>
                        <td>
                            <div class="action-stack">
                                <a href="invoice.php?id=<?= $data['id_transaksi']; ?>" class="btn btn-sm btn-brand">Lihat Invoice</a>
                                <?php if ($data['status'] == 'menunggu pembayaran') { ?>
                                    <a href="upload_bukti.php?id=<?= $data['id_transaksi']; ?>" class="btn btn-sm btn-outline-warning">Upload Bukti &amp; SIM A</a>
                                    <a href="batalkan.php?id=<?= $data['id_transaksi']; ?>" class="btn btn-sm btn-outline-danger">Batalkan Pesanan</a>
                                <?php } elseif ($data['status'] == 'menunggu konfirmasi') { ?>
                                    <span class="top-note">Menunggu verifikasi admin</span>
                                <?php } elseif ($data['status'] == 'dibatalkan') { ?>
                                    <span class="top-note text-danger">Pesanan dibatalkan</span>
                                <?php } else { ?>
                                    <span class="top-note">Tidak ada aksi tambahan</span>
                                <?php } ?>
                            </div>
                        </td>
                    </tr>
                <?php } } else { ?>
                    <tr><td colspan="10"><div class="empty-box">Belum ada transaksi.</div></td></tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php customer_page_end(); ?>
