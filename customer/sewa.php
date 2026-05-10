<?php
session_start();
include "../config/koneksi.php";
include "../config/helpers.php";
include "_layout.php";
bootstrap_app($conn);
customer_require_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$data = mysqli_query($conn, "SELECT * FROM mobil WHERE id_mobil='$id' LIMIT 1");
$mobil = mysqli_fetch_assoc($data);

if (!$mobil) {
    echo "Data mobil tidak ditemukan.";
    exit;
}

$statusMobilSaatIni = trim((string) ($mobil['status'] ?? 'tersedia'));
$mobilSedangDiservice = $statusMobilSaatIni === 'sedang diservice';

$jadwalQuery = mysqli_query($conn, "
    SELECT tanggal_sewa, tanggal_kembali, status, catatan_admin, selesai_admin_at, bukti_pembayaran, sim_a
    FROM transaksi
    WHERE id_mobil='$id'
      AND status IN ('menunggu pembayaran','menunggu konfirmasi','terverifikasi','disewa')
      AND (selesai_admin_at IS NULL OR selesai_admin_at = '0000-00-00 00:00:00')
      AND LOWER(COALESCE(catatan_admin,'')) NOT LIKE '%batal%'
      AND tanggal_sewa >= NOW()
    ORDER BY tanggal_sewa ASC
");

$jadwalBentrok = array();
while ($row = mysqli_fetch_assoc($jadwalQuery)) {
    $jadwalBentrok[] = array(
        'start' => date('Y-m-d\TH:i', strtotime($row['tanggal_sewa'])),
        'end' => date('Y-m-d\TH:i', strtotime($row['tanggal_kembali'])),
        'status' => $row['status'],
        'label' => transaksi_status_info($row)['label']
    );
}

$transaksiAktifSekarang = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT * FROM transaksi
    WHERE id_mobil='$id'
      AND status IN ('terverifikasi','disewa')
      AND (selesai_admin_at IS NULL OR selesai_admin_at = '0000-00-00 00:00:00')
      AND NOW() >= tanggal_sewa
    ORDER BY tanggal_sewa ASC
    LIMIT 1
"));

customer_page_start('Form Booking Mobil', 'mobil');
?>
<div class="row g-4">
    <div class="col-xl-7">
        <div class="card form-card">
            <div class="card-header"><h3 class="card-title">Form Booking Mobil</h3></div>
            <div class="card-body">
                <?php if ($mobilSedangDiservice) { ?>
                    <div class="alert alert-warning mb-3">
                        <strong>Mobil sedang diservice.</strong><br>
                        Untuk sementara mobil ini tidak bisa disewa sampai admin mengubah status kembali menjadi tersedia.
                    </div>
                <?php } ?>
                <?php if ($transaksiAktifSekarang) { $ot = hitung_overtime_data($transaksiAktifSekarang); ?>
                    <div class="alert-soft-danger mb-3">
                        <strong>Mobil sedang dipakai / belum diselesaikan admin.</strong><br>
                        Booking baru tetap akan ditolak jika waktunya bentrok atau transaksi sebelumnya belum ditandai selesai oleh admin.
                        <?php if ($ot['jam'] > 0) { ?>
                            <div class="mt-2">Overtime berjalan: <strong><?= (int) $ot['jam']; ?> jam</strong> (<?= format_rupiah($ot['biaya']); ?>)</div>
                        <?php } ?>
                    </div>
                <?php } ?>
                <form action="proses_sewa.php" method="POST" id="bookingForm">
                    <input type="hidden" name="id_mobil" value="<?= $mobil['id_mobil']; ?>">
                    <input type="hidden" id="harga12" value="<?= (int) $mobil['harga_12jam']; ?>">
                    <input type="hidden" id="harga24" value="<?= (int) $mobil['harga_24jam']; ?>">
                    <div class="row g-3">
                        <div class="col-12"><label class="form-label">Nama Mobil</label><input type="text" class="form-control" value="<?= esc($mobil['nama_mobil']); ?>" readonly></div>
                        <div class="col-md-6">
                            <label class="form-label">Lama Sewa</label>
                            <select name="lama_sewa" id="lama_sewa" class="form-select" required <?= $mobilSedangDiservice ? 'disabled' : ''; ?>>
                                <option value="" disabled selected>-- Pilih Lama Sewa --</option>
                                <option value="12">12 Jam</option>
                                <option value="24">24 Jam</option>
                                <option value="36">36 Jam</option>
                                <option value="48">48 Jam</option>
                                <option value="72">72 Jam</option>
                                <option value="96">96 Jam</option>
                                <option value="120">120 Jam</option>
                                <option value="144">144 Jam</option>
                            </select>
                        </div>
                        <div class="col-md-6"><label class="form-label">Tanggal Mulai Sewa</label><input type="datetime-local" name="tgl_sewa" id="tgl_sewa" class="form-control" required min="<?= date('Y-m-d\TH:i'); ?>" <?= $mobilSedangDiservice ? 'disabled' : ''; ?>></div>
                        <div class="col-md-6"><label class="form-label">Tanggal Kembali</label><input type="text" name="tgl_kembali" id="tgl_kembali" class="form-control" readonly></div>
                        <div class="col-md-6"><label class="form-label">Total Harga</label><input type="text" id="total_harga" class="form-control" readonly></div>
                    </div>
                    <div id="alertBentrok" class="alert alert-danger d-none mt-3"></div>
                    <div class="booking-summary-box mt-3">
                        <strong>Syarat & Jaminan Rental Mobil:</strong><br>
                        - Wajib memiliki SIM A atau SIM roda 4 ke atas.<br>
                        - Jaminan: 2 KTP asli (KTP penyewa dan KTP Saudara) dan sepeda motor (jika ada).<br>
                        - Verifikasi: Petugas akan melakukan pengecekan SIM A.<br>
                        - Biaya overtime dikenakan <?= format_rupiah(tarif_overtime_per_jam()); ?> per 1 jam.
                    </div>
                    <div class="form-check mt-3">
                        <input type="checkbox" class="form-check-input" id="setuju" <?= $mobilSedangDiservice ? 'disabled' : ''; ?>>
                        <label class="form-check-label" for="setuju">Saya setuju dengan syarat dan ketentuan di atas.</label>
                    </div>
                    <div class="page-actions mt-4">
                        <a href="mobil.php" class="btn btn-outline-secondary">Kembali</a>
                        <button class="btn btn-brand" id="btnBooking" disabled><?= $mobilSedangDiservice ? 'Tidak Bisa Dibooking' : 'Booking Sekarang'; ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card panel-card mb-4">
            <div class="card-header"><h3 class="card-title">Preview Booking</h3></div>
            <div class="card-body">
                <img src="../uploads/mobil/<?= esc($mobil['foto']); ?>" class="img-fluid rounded border mb-3" alt="Mobil">
                <div class="preview-kpi mb-3">
                    <div class="mini-card"><div class="metric-label">12 Jam</div><div class="metric-value"><?= format_rupiah($mobil['harga_12jam']); ?></div></div>
                    <div class="mini-card"><div class="metric-label">24 Jam</div><div class="metric-value"><?= format_rupiah($mobil['harga_24jam']); ?></div></div>
                </div>
                <div class="invoice-side-list">
                    <p><strong>Mobil</strong> <?= esc($mobil['nama_mobil']); ?></p>
                    <p><strong>Merk</strong> <?= esc($mobil['merk']); ?></p>
                    <p><strong>Tahun</strong> <?= esc($mobil['tahun']); ?></p>
                    <p><strong>No Plat</strong> <?= esc($mobil['no_plat']); ?></p>
                    <p><strong>Status</strong> <?= esc(mobil_status_info($statusMobilSaatIni)['label']); ?></p>
                </div>
            </div>
        </div>

        <div class="card panel-card">
            <div class="card-header"><h3 class="card-title">Jadwal yang Sudah Dipakai</h3></div>
            <div class="card-body">
                <p class="text-muted small">Hanya jadwal yang akan datang yang ditampilkan. Jadwal akan hilang jika dibatalkan admin atau sudah diselesaikan admin.</p>
                <?php if (count($jadwalBentrok) > 0) { ?>
                    <div class="table-responsive">
                        <table class="table table-soft table-sm schedule-table mb-0">
                            <thead>
                                <tr><th>Mulai</th><th>Selesai</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($jadwalBentrok as $jadwal) { ?>
                                    <tr>
                                        <td><?= date('d-m-Y H:i', strtotime($jadwal['start'])); ?></td>
                                        <td><?= date('d-m-Y H:i', strtotime($jadwal['end'])); ?></td>
                                        <td><?= esc($jadwal['label']); ?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                <?php } else { ?>
                    <div class="empty-box">Belum ada jadwal mendatang untuk mobil ini.</div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
<?php
$extraScript = '<script>
const blockedSchedules = ' . json_encode($jadwalBentrok) . ';
const lamaSewa = document.getElementById("lama_sewa");
const tglSewa = document.getElementById("tgl_sewa");
const tglKembali = document.getElementById("tgl_kembali");
const totalHarga = document.getElementById("total_harga");
const btnBooking = document.getElementById("btnBooking");
const alertBentrok = document.getElementById("alertBentrok");
const setuju = document.getElementById("setuju");
function formatTanggal(date){
    const y = date.getFullYear();
    const m = String(date.getMonth()+1).padStart(2,"0");
    const d = String(date.getDate()).padStart(2,"0");
    const h = String(date.getHours()).padStart(2,"0");
    const i = String(date.getMinutes()).padStart(2,"0");
    return `${y}-${m}-${d} ${h}:${i}:00`;
}
function hitung(){
    const lama = parseInt(lamaSewa.value || 0, 10);
    const mulai = tglSewa.value;
    const harga12 = parseInt(document.getElementById("harga12").value || 0, 10);
    const harga24 = parseInt(document.getElementById("harga24").value || 0, 10);
    alertBentrok.classList.add("d-none");
    alertBentrok.textContent = "";
    if (!lama || !mulai) {
        tglKembali.value = "";
        totalHarga.value = "";
        cekTombol();
        return;
    }
    const startDate = new Date(mulai);
    const endDate = new Date(startDate.getTime() + lama * 60 * 60 * 1000);
    tglKembali.value = formatTanggal(endDate);
    let total = lama === 12 ? harga12 : (lama / 24) * harga24;
    totalHarga.value = "Rp " + total.toLocaleString("id-ID");
    const bentrok = blockedSchedules.find(function(item){
        const blockStart = new Date(item.start);
        const blockEnd = new Date(item.end);
        return startDate < blockEnd && endDate > blockStart;
    });
    if (bentrok) {
        alertBentrok.textContent = "Jadwal bentrok dengan booking lain pada " + new Date(bentrok.start).toLocaleString("id-ID") + " s/d " + new Date(bentrok.end).toLocaleString("id-ID") + ".";
        alertBentrok.classList.remove("d-none");
    }
    cekTombol();
}
function cekTombol(){
    const adaBentrok = !alertBentrok.classList.contains("d-none");
    btnBooking.disabled = !setuju.checked || adaBentrok || !lamaSewa.value || !tglSewa.value;
}
lamaSewa.addEventListener("change", hitung);
tglSewa.addEventListener("change", hitung);
setuju.addEventListener("change", cekTombol);
document.getElementById("bookingForm").addEventListener("submit", function(e){
    hitung();
    if (!alertBentrok.classList.contains("d-none")) {
        e.preventDefault();
    }
});
</script>';
customer_page_end($extraScript);
?>