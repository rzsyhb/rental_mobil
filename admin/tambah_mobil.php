<?php
session_start();
include "../config/koneksi.php";
include "../config/helpers.php";
include "_layout.php";
bootstrap_app($conn);
admin_require_login();
admin_page_start('Tambah Data Mobil', 'mobil');
?>
<div class="row justify-content-center">
    <div class="col-xl-8">
        <div class="card form-card">
            <div class="card-body">
                <form action="proses_tambah_mobil.php" method="POST" enctype="multipart/form-data">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Nama Mobil</label><input type="text" name="nama_mobil" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Merk</label><input type="text" name="merk" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">No Plat</label><input type="text" name="no_plat" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Tahun</label><input type="number" name="tahun" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Harga 12 Jam</label><input type="number" name="harga12" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Harga 24 Jam</label><input type="number" name="harga24" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Status Mobil</label><select name="status" class="form-select" required><option value="tersedia" selected>Tersedia</option><option value="disewa">Disewa</option><option value="sedang diservice">Sedang Diservice</option></select><div class="top-note mt-1">Pilih <strong>Sedang Diservice</strong> saat mobil masuk bengkel/service agar tidak bisa dibooking customer. Jika nantinya masih ada jadwal yang akan datang, status ini tetap bisa dipakai selama mobil belum sedang dipakai customer.</div></div>
                        <div class="col-12"><label class="form-label">Deskripsi</label><textarea name="deskripsi" class="form-control" placeholder="Contoh: Mobil irit, AC dingin, transmisi otomatis"></textarea></div>
                        <div class="col-12"><label class="form-label">Foto Mobil</label><input type="file" name="foto" class="form-control" accept="image/*"></div>
                    </div>
                    <div class="page-actions mt-4">
                        <a href="mobil.php" class="btn btn-outline-secondary">Kembali</a>
                        <button class="btn btn-brand">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php admin_page_end(); ?>
