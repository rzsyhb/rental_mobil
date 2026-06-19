<?php
session_start();
include "../config/koneksi.php";
include "../config/helpers.php";
include "_layout.php";
bootstrap_app($conn);
admin_require_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$data = mysqli_query($conn, "SELECT * FROM mobil WHERE id_mobil='$id'");
$d = mysqli_fetch_assoc($data);
if (!$d) { echo 'Data mobil tidak ditemukan.'; exit; }
admin_page_start('Edit Data Mobil', 'mobil');
?>
<div class="row justify-content-center">
    <div class="col-xl-8">
        <div class="card form-card">
            <div class="card-body">
                <form action="proses_edit_mobil.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id_mobil" value="<?= $d['id_mobil']; ?>">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Nama Mobil</label><input type="text" name="nama_mobil" class="form-control" value="<?= esc($d['nama_mobil']); ?>" required></div>
                        <div class="col-md-6"><label class="form-label">Merk</label><input type="text" name="merk" class="form-control" value="<?= esc($d['merk']); ?>" required></div>
                        <div class="col-md-6"><label class="form-label">No Plat</label><input type="text" name="no_plat" class="form-control" value="<?= esc($d['no_plat']); ?>" required></div>
                        <div class="col-md-6"><label class="form-label">Tahun</label><input type="number" name="tahun" class="form-control" value="<?= esc($d['tahun']); ?>" required></div>
                        <div class="col-md-6"><label class="form-label">Harga 12 Jam</label><input type="number" name="harga12" class="form-control" value="<?= esc($d['harga_12jam']); ?>" required></div>
                        <div class="col-md-6"><label class="form-label">Harga 24 Jam</label><input type="number" name="harga24" class="form-control" value="<?= esc($d['harga_24jam']); ?>" required></div>
                        <div class="col-md-6"><label class="form-label">Status Mobil</label><select name="status" class="form-select" required><option value="tersedia" <?= ($d['status'] ?? 'tersedia') === 'tersedia' ? 'selected' : ''; ?>>Tersedia</option><option value="disewa" <?= ($d['status'] ?? '') === 'disewa' ? 'selected' : ''; ?>>Disewa</option><option value="sedang diservice" <?= ($d['status'] ?? '') === 'sedang diservice' ? 'selected' : ''; ?>>Sedang Diservice</option></select><div class="top-note mt-1">Status <strong>Sedang Diservice</strong> akan membuat mobil tidak bisa dibooking customer. Status ini tetap boleh dipilih walaupun masih ada jadwal yang akan datang, tetapi tidak bisa dipilih jika mobil sedang benar-benar dipakai customer dan belum diselesaikan admin.</div></div>
                        <div class="col-12"><label class="form-label">Deskripsi</label><textarea name="deskripsi" class="form-control"><?= esc($d['deskripsi']); ?></textarea></div>
                        <div class="col-md-6"><label class="form-label">Foto Baru</label><input type="file" name="foto" class="form-control" accept="image/*"><small class="text-muted">Format: JPG, PNG. Maksimal 3 MB.</small></div>
                        <div class="col-md-6"><label class="form-label">Foto Saat Ini</label><div><?php if (!empty($d['foto'])) { ?><img src="../uploads/mobil/<?= esc($d['foto']); ?>" class="table-thumb-lg" alt="Mobil"><?php } else { ?><div class="empty-box py-2">Tidak ada foto</div><?php } ?></div></div>
                    </div>
                    <div class="page-actions mt-4">
                        <a href="mobil.php" class="btn btn-outline-secondary">Kembali</a>
                        <button class="btn btn-brand">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php admin_page_end(); ?>
