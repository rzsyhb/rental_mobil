<?php
session_start();
include "../config/koneksi.php";
include "../config/helpers.php";
include "_layout.php";
bootstrap_app($conn);
admin_require_login();

$cari = isset($_GET['cari']) ? trim($_GET['cari']) : '';
$query = mysqli_query($conn, "SELECT * FROM customer ORDER BY id_customer DESC");
$rows = [];
if ($query) {
    while ($d = mysqli_fetch_assoc($query)) {
        $d['no_hp_plain'] = customer_no_hp_plain($d);
        if ($cari !== '') {
            $needle = mb_strtolower($cari);
            $haystacks = [
                mb_strtolower((string) $d['nama']),
                mb_strtolower((string) $d['username']),
                mb_strtolower((string) $d['no_hp_plain']),
            ];
            $match = false;
            foreach ($haystacks as $haystack) {
                if ($needle !== '' && strpos($haystack, $needle) !== false) {
                    $match = true;
                    break;
                }
            }
            if (!$match) {
                continue;
            }
        }
        $rows[] = $d;
    }
}
admin_page_start('Data User', 'customer');
?>
<div class="row mb-4 align-items-center">
    <div class="col-lg-6 mb-3 mb-lg-0"><p class="top-note mb-0">Admin dapat klik gambar KTP customer untuk melihat ukuran penuh.</p></div>
    <div class="col-lg-6">
        <form method="GET" class="d-flex gap-2">
            <input type="text" name="cari" class="form-control" placeholder="Cari nama, username, nomor HP..." value="<?= esc($cari); ?>">
            <button class="btn btn-brand">Cari</button>
        </form>
    </div>
</div>
<div class="card panel-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-soft align-middle mb-0">
                <thead>
                    <tr><th>No</th><th>Foto KTP</th><th>Nama</th><th>Alamat</th><th>JK</th><th>No HP</th><th>Username</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                <?php if (!empty($rows)) { $no=1; foreach($rows as $d){ $fotoUrl = secure_file_url('customer_ktp', $d['id_customer']); ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td>
                        <?php if(!empty($d['foto'])){ ?>
                            <a href="#" data-bs-toggle="modal" data-bs-target="#foto<?= $d['id_customer'] ?>"><img src="<?= esc($fotoUrl) ?>" class="table-thumb-lg" alt="KTP"></a>
                            <div class="modal fade" id="foto<?= $d['id_customer'] ?>"><div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content"><div class="modal-body text-center"><img src="<?= esc($fotoUrl) ?>" class="img-fluid rounded"></div></div></div></div>
                        <?php } else { ?><div class="empty-box py-2">Tidak ada</div><?php } ?>
                    </td>
                    <td><strong><?= esc($d['nama']) ?></strong></td>
                    <td><?= esc($d['alamat']) ?></td>
                    <td><?= $d['jenis_kelamin']=='L' ? 'Laki-laki' : 'Perempuan'; ?></td>
                    <td><?= esc($d['no_hp_plain']) ?></td>
                    <td><?= esc($d['username']) ?></td>
                    <td>
                        <a href="hapus_customer.php?id=<?= $d['id_customer'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Yakin hapus data customer ini?')"><i class="bi bi-trash me-1"></i> Hapus</a>
                    </td>
                </tr>
                <?php } } else { ?>
                    <tr><td colspan="8"><div class="empty-box">Belum ada data customer.</div></td></tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php admin_page_end(); ?>
