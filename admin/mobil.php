<?php
session_start();
include "../config/koneksi.php";
include "../config/helpers.php";
include "_layout.php";
bootstrap_app($conn);
admin_require_login();

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$where = '1=1';
if ($q !== '') {
    $qdb = mysqli_real_escape_string($conn, $q);
    $where .= " AND (nama_mobil LIKE '%$qdb%' OR merk LIKE '%$qdb%' OR no_plat LIKE '%$qdb%')";
}
$query = mysqli_query($conn, "SELECT * FROM mobil WHERE $where ORDER BY id_mobil DESC");
admin_page_start('Data Mobil', 'mobil');
?>
<div class="row mb-4 align-items-center">
    <div class="col-lg-6 mb-3 mb-lg-0">
        <div class="page-actions">
            <a href="tambah_mobil.php" class="btn btn-brand"><i class="bi bi-plus-circle me-1"></i> Tambah Data</a>
        </div>
    </div>
    <div class="col-lg-6">
        <form class="d-flex gap-2" method="GET">
            <input type="text" class="form-control" name="q" value="<?= esc($q); ?>" placeholder="Cari nama mobil, merk, no plat...">
            <button class="btn btn-brand">Cari</button>
        </form>
    </div>
</div>

<div class="card panel-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-soft align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:70px">No</th>
                        <th>Gambar</th>
                        <th>Nama Mobil</th>
                        <th>Merk</th>
                        <th>No. Plat</th>
                        <th>Status</th>
                        <th>Harga</th>
                        <th style="width:150px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($query && mysqli_num_rows($query) > 0) { $no=1; while($data=mysqli_fetch_assoc($query)){ ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td>
                            <?php if (!empty($data['foto'])) { ?>
                                <img src="../uploads/mobil/<?= esc($data['foto']); ?>" class="table-thumb-lg" alt="<?= esc($data['nama_mobil']); ?>">
                            <?php } else { ?>
                                <div class="empty-box py-2">Tidak ada foto</div>
                            <?php } ?>
                        </td>
                        <td><strong><?= esc($data['nama_mobil']); ?></strong><br><span class="top-note">Tahun <?= esc($data['tahun']); ?></span></td>
                        <td><?= esc($data['merk']); ?></td>
                        <td><?= esc($data['no_plat']); ?></td>
                        <?php $infoStatusMobil = mobil_status_info($data['status'] ?? 'tersedia'); ?>
                        <td><span class="status-pill <?= esc($infoStatusMobil['class']); ?>"><?= esc($infoStatusMobil['label']); ?></span></td>
                        <td>
                            <div><?= format_rupiah($data['harga_24jam']); ?> <span class="top-note">/24 jam</span></div>
                            <div class="top-note"><?= format_rupiah($data['harga_12jam']); ?> /12 jam</div>
                        </td>
                        <td>
                            <div class="action-stack">
                                <?php if (!empty($data['foto'])) { ?><a href="../uploads/mobil/<?= esc($data['foto']); ?>" target="_blank" class="btn-circle-soft btn-view" title="Lihat"><i class="bi bi-eye"></i></a><?php } ?>
                                <a href="edit_mobil.php?id=<?= $data['id_mobil']; ?>" class="btn-circle-soft btn-edit" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                <a href="hapus_mobil.php?id=<?= $data['id_mobil']; ?>" class="btn-circle-soft btn-delete" title="Hapus" onclick="return confirm('Apakah anda yakin ingin menghapus mobil ini?')"><i class="bi bi-trash"></i></a>
                            </div>
                        </td>
                    </tr>
                <?php } } else { ?>
                    <tr><td colspan="8"><div class="empty-box">Belum ada data mobil.</div></td></tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php admin_page_end(); ?>
