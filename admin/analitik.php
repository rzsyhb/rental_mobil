<?php
session_start();
include "../config/koneksi.php";
include "../config/helpers.php";
include "_layout.php";
bootstrap_app($conn);
admin_require_login();

function sanitize_tahun($value)
{
    $value = trim((string) $value);
    return preg_match('/^\d{4}$/', $value) ? $value : date('Y');
}

function sanitize_tanggal($value, $fallback)
{
    $value = trim((string) $value);
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : $fallback;
}

function get_dashboard_analitik_data($conn, $tahunMobil, $tanggalMulai, $tanggalSelesai)
{
    $tahunMobil = sanitize_tahun($tahunMobil);
    $tanggalMulai = sanitize_tanggal($tanggalMulai, date('Y-m-d', strtotime('-13 days')));
    $tanggalSelesai = sanitize_tanggal($tanggalSelesai, date('Y-m-d'));
    if ($tanggalMulai > $tanggalSelesai) {
        $tmp = $tanggalMulai;
        $tanggalMulai = $tanggalSelesai;
        $tanggalSelesai = $tmp;
    }

    $tahunMobilSql = mysqli_real_escape_string($conn, $tahunMobil);
    $tanggalMulaiSql = mysqli_real_escape_string($conn, $tanggalMulai . ' 00:00:00');
    $tanggalSelesaiSql = mysqli_real_escape_string($conn, $tanggalSelesai . ' 23:59:59');

    $pendapatanBulanan = mysqli_query($conn, "
        SELECT DATE_FORMAT(tanggal_sewa, '%Y-%m') AS label_bulan,
               COALESCE(SUM(CASE WHEN status IN ('terverifikasi','disewa','selesai') THEN total_harga ELSE 0 END),0) AS total_pendapatan,
               COUNT(*) AS total_booking
        FROM transaksi
        WHERE tanggal_sewa >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(tanggal_sewa, '%Y-%m')
        ORDER BY label_bulan ASC
    ");
    $bulan = []; $pendapatan = []; $booking = [];
    while ($pendapatanBulanan && $r = mysqli_fetch_assoc($pendapatanBulanan)) {
        $bulan[] = $r['label_bulan'];
        $pendapatan[] = (float) $r['total_pendapatan'];
        $booking[] = (int) $r['total_booking'];
    }

    $topMobilQuery = mysqli_query($conn, "
        SELECT mobil.nama_mobil, COUNT(transaksi.id_transaksi) AS total
        FROM transaksi
        JOIN mobil ON transaksi.id_mobil = mobil.id_mobil
        WHERE transaksi.status NOT IN ('dibatalkan')
          AND YEAR(transaksi.tanggal_sewa) = '$tahunMobilSql'
        GROUP BY transaksi.id_mobil
        ORDER BY total DESC, mobil.nama_mobil ASC
        LIMIT 7
    ");
    $topMobil = []; $topMobilTotal = [];
    while ($topMobilQuery && $d = mysqli_fetch_assoc($topMobilQuery)) {
        $topMobil[] = $d['nama_mobil'];
        $topMobilTotal[] = (int) $d['total'];
    }
    if (empty($topMobil)) {
        $topMobil[] = 'Tidak ada data';
        $topMobilTotal[] = 0;
    }

    $statusSummary = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT
            SUM(CASE WHEN status='menunggu pembayaran' THEN 1 ELSE 0 END) AS menunggu_bayar,
            SUM(CASE WHEN status='menunggu konfirmasi' THEN 1 ELSE 0 END) AS menunggu_konfirmasi,
            SUM(CASE WHEN status IN ('terverifikasi','disewa') THEN 1 ELSE 0 END) AS aktif,
            SUM(CASE WHEN status='selesai' THEN 1 ELSE 0 END) AS selesai,
            SUM(CASE WHEN status='dibatalkan' THEN 1 ELSE 0 END) AS dibatalkan
        FROM transaksi
    "));
    if (!$statusSummary) {
        $statusSummary = ['menunggu_bayar'=>0,'menunggu_konfirmasi'=>0,'aktif'=>0,'selesai'=>0,'dibatalkan'=>0];
    }

    $harianQuery = mysqli_query($conn, "
        SELECT DATE(tanggal_sewa) AS tanggal, COUNT(*) AS jumlah
        FROM transaksi
        WHERE tanggal_sewa BETWEEN '$tanggalMulaiSql' AND '$tanggalSelesaiSql'
        GROUP BY DATE(tanggal_sewa)
        ORDER BY tanggal ASC
    ");
    $hari = []; $jumlah = [];
    while ($harianQuery && $d = mysqli_fetch_assoc($harianQuery)) {
        $hari[] = $d['tanggal'];
        $jumlah[] = (int) $d['jumlah'];
    }
    if (empty($hari)) {
        $hari[] = $tanggalMulai;
        $jumlah[] = 0;
    }

    $yearListQuery = mysqli_query($conn, "
        SELECT DISTINCT YEAR(tanggal_sewa) AS tahun
        FROM transaksi
        WHERE tanggal_sewa IS NOT NULL
        ORDER BY tahun DESC
    ");
    $daftarTahun = [];
    while ($yearListQuery && $yr = mysqli_fetch_assoc($yearListQuery)) {
        if (!empty($yr['tahun'])) {
            $daftarTahun[] = (int) $yr['tahun'];
        }
    }
    if (empty($daftarTahun)) {
        $daftarTahun[] = (int) date('Y');
    }
    if (!in_array((int) $tahunMobil, $daftarTahun, true)) {
        $daftarTahun[] = (int) $tahunMobil;
        rsort($daftarTahun);
    }

    return [
        'filters' => [
            'tahun_mobil' => $tahunMobil,
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_selesai' => $tanggalSelesai,
        ],
        'pendapatan' => [
            'labels' => $bulan,
            'pendapatan' => $pendapatan,
            'booking' => $booking,
        ],
        'status_summary' => $statusSummary,
        'mobil_terlaris' => [
            'labels' => $topMobil,
            'data' => $topMobilTotal,
            'subtitle' => 'Filter tahun: ' . $tahunMobil,
        ],
        'harian' => [
            'labels' => $hari,
            'data' => $jumlah,
            'title' => 'Jumlah Penyewa per Hari (' . date('d-m-Y', strtotime($tanggalMulai)) . ' - ' . date('d-m-Y', strtotime($tanggalSelesai)) . ')',
        ],
        'daftar_tahun' => $daftarTahun,
    ];
}

$tahunMobil = isset($_GET['tahun_mobil']) ? $_GET['tahun_mobil'] : date('Y');
$tanggalMulai = isset($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : date('Y-m-d', strtotime('-13 days'));
$tanggalSelesai = isset($_GET['tanggal_selesai']) ? $_GET['tanggal_selesai'] : date('Y-m-d');

$dataAnalitik = get_dashboard_analitik_data($conn, $tahunMobil, $tanggalMulai, $tanggalSelesai);

if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => true,
        'filters' => $dataAnalitik['filters'],
        'mobil_terlaris' => $dataAnalitik['mobil_terlaris'],
        'harian' => $dataAnalitik['harian'],
    ]);
    exit;
}

admin_page_start('Analitik', 'analitik', '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>');
?>
<style>
.analytics-toolbar-card .card-header,
.chart-filter-card .card-header { background: linear-gradient(135deg, #f8fbff 0%, #eef3ff 100%); }
.analytics-kicker { font-size: .78rem; text-transform: uppercase; letter-spacing: .08em; color: #6c7a92; font-weight: 700; }
.filter-inline-label { font-size: .78rem; color: #6b7280; margin-bottom: .25rem; display: block; }
.filter-badge-soft { display: inline-flex; align-items: center; gap: .35rem; padding: .35rem .7rem; border-radius: 999px; background: #eef2ff; color: #4f46e5; font-size: .82rem; font-weight: 600; }
.chart-loading { position: absolute; inset: 0; background: rgba(255,255,255,.72); display: none; align-items: center; justify-content: center; z-index: 2; border-radius: 1rem; backdrop-filter: blur(2px); }
.chart-loading.active { display: flex; }
.chart-loading .spinner-border { width: 2rem; height: 2rem; }
.chart-card-body { position: relative; }
.quick-note-box { border: 1px dashed #d8def2; border-radius: 16px; padding: 14px 16px; background: #fafcff; }
.quick-note-title { font-weight: 700; color: #1f2937; margin-bottom: 4px; }
.chart-subtitle { color: #6b7280; font-size: .9rem; margin-bottom: .75rem; }
@media (max-width: 767px) {
    .analytics-actions-stack { width: 100%; }
}
</style>

<div class="row g-4 mb-4">
    <div class="col-md-6 col-xl-3"><div class="card mini-card h-100"><div class="card-body p-4"><div class="metric-label">Menunggu Konfirmasi</div><div class="metric-value"><?= (int)$dataAnalitik['status_summary']['menunggu_konfirmasi']; ?></div></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card mini-card h-100"><div class="card-body p-4"><div class="metric-label">Rental Aktif</div><div class="metric-value"><?= (int)$dataAnalitik['status_summary']['aktif']; ?></div></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card mini-card h-100"><div class="card-body p-4"><div class="metric-label">Selesai</div><div class="metric-value"><?= (int)$dataAnalitik['status_summary']['selesai']; ?></div></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card mini-card h-100"><div class="card-body p-4"><div class="metric-label">Dibatalkan</div><div class="metric-value"><?= (int)$dataAnalitik['status_summary']['dibatalkan']; ?></div></div></div></div>
</div>

<div class="row g-4">
    <div class="col-xl-8">
        <div class="card panel-card analytics-toolbar-card">
            <div class="card-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <div>
                    <div class="analytics-kicker">Ringkasan</div>
                    <h3 class="card-title mb-1">Pendapatan & Booking per Bulan</h3><br>
                    <h6><div class="top-note">Grafik ini tetap tampil sebagai ringkasan 6 bulan terakhir.</div></h6>
                </div>
                <div class="filter-badge-soft">Live chart update aktif</div>
            </div>
            <div class="card-body chart-card-body">
                <canvas id="pendapatanChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card panel-card analytics-toolbar-card">
            <div class="card-header">
                <div class="analytics-kicker">Distribusi</div>
                <h3 class="card-title mb-1">Komposisi Status Transaksi</h3><br>
                <div class="top-note">Membantu admin melihat kondisi transaksi saat ini.</div>
            </div>
            <div class="card-body chart-card-body">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card panel-card chart-filter-card h-100">
            <div class="card-header d-flex flex-column gap-3">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                    <div>
                        <div class="analytics-kicker">Filter Interaktif</div>
                        <h3 class="card-title mb-1">Mobil Terlaris</h3><br>
                        <div class="top-note">Pilih tahun, lalu grafik berubah langsung tanpa refresh halaman.</div>
                    </div>
                    <div class="filter-badge-soft" id="tahunFilterBadge">Tahun <?= esc($dataAnalitik['filters']['tahun_mobil']); ?></div>
                </div>
                <form id="formFilterMobil" class="d-flex flex-column flex-md-row align-items-md-end gap-2 analytics-actions-stack">
                    <div>
                        <label class="filter-inline-label">Tahun</label>
                        <select name="tahun_mobil" id="tahun_mobil" class="form-select" style="min-width:140px;">
                            <?php foreach ($dataAnalitik['daftar_tahun'] as $tahun) { ?>
                                <option value="<?= esc($tahun); ?>" <?= (string)$dataAnalitik['filters']['tahun_mobil'] === (string)$tahun ? 'selected' : ''; ?>><?= esc($tahun); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <input type="hidden" name="tanggal_mulai" value="<?= esc($dataAnalitik['filters']['tanggal_mulai']); ?>">
                    <input type="hidden" name="tanggal_selesai" value="<?= esc($dataAnalitik['filters']['tanggal_selesai']); ?>">
                    <button type="submit" class="btn btn-brand">Cari</button>
                </form>
            </div>
            <div class="card-body chart-card-body">
                <div class="chart-loading" id="mobilLoading"><div class="text-center"><div class="spinner-border text-primary mb-2"></div><div class="small text-muted">Memuat grafik...</div></div></div>
                <div class="quick-note-box mb-3">
                    <div class="quick-note-title">Info filter</div>
                    <div class="chart-subtitle mb-0" id="mobilSubtitle"><?= esc($dataAnalitik['mobil_terlaris']['subtitle']); ?></div>
                </div>
                <canvas id="mobilChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card panel-card chart-filter-card h-100">
            <div class="card-header d-flex flex-column gap-3">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                    <div>
                        <div class="analytics-kicker">Filter Interaktif</div>
                        <h3 class="card-title mb-1" id="harianTitle"><?= esc($dataAnalitik['harian']['title']); ?></h3>
                        <div class="top-note">Pilih rentang tanggal, lalu grafik akan diperbarui otomatis.</div>
                    </div>
                    <div class="filter-badge-soft" id="tanggalFilterBadge"><?= esc($dataAnalitik['filters']['tanggal_mulai']); ?> s/d <?= esc($dataAnalitik['filters']['tanggal_selesai']); ?></div>
                </div>
                <form id="formFilterHarian" class="row g-2 align-items-end">
                    <input type="hidden" name="tahun_mobil" value="<?= esc($dataAnalitik['filters']['tahun_mobil']); ?>">
                    <div class="col-sm-5">
                        <label class="filter-inline-label">Dari Tanggal</label>
                        <input type="date" name="tanggal_mulai" id="tanggal_mulai" class="form-control" value="<?= esc($dataAnalitik['filters']['tanggal_mulai']); ?>">
                    </div>
                    <div class="col-sm-5">
                        <label class="filter-inline-label">Sampai Tanggal</label>
                        <input type="date" name="tanggal_selesai" id="tanggal_selesai" class="form-control" value="<?= esc($dataAnalitik['filters']['tanggal_selesai']); ?>">
                    </div>
                    <div class="col-sm-2 d-grid">
                        <button type="submit" class="btn btn-brand">Cari</button>
                    </div>
                </form>
            </div>
            <div class="card-body chart-card-body">
                <div class="chart-loading" id="harianLoading"><div class="text-center"><div class="spinner-border text-primary mb-2"></div><div class="small text-muted">Memuat grafik...</div></div></div>
                <canvas id="harianChart"></canvas>
            </div>
        </div>
    </div>
</div>
<?php
$extra = '<script>
const analyticsState = ' . json_encode($dataAnalitik, JSON_UNESCAPED_UNICODE) . ';

const pendapatanChart = new Chart(document.getElementById("pendapatanChart"), {
    type: "bar",
    data: {
        labels: analyticsState.pendapatan.labels,
        datasets: [
            { label: "Pendapatan", data: analyticsState.pendapatan.pendapatan, yAxisID: "y" },
            { label: "Booking", data: analyticsState.pendapatan.booking, type: "line", yAxisID: "y1", borderWidth: 2, tension: .35 }
        ]
    },
    options: {
        interaction: { mode: "index", intersect: false },
        scales: { y: { beginAtZero: true }, y1: { beginAtZero: true, position: "right", grid: { drawOnChartArea: false } } }
    }
});

const statusChart = new Chart(document.getElementById("statusChart"), {
    type: "doughnut",
    data: {
        labels: ["Menunggu Bayar", "Menunggu Konfirmasi", "Aktif", "Selesai", "Dibatalkan"],
        datasets: [{ data: [
            parseInt(analyticsState.status_summary.menunggu_bayar || 0),
            parseInt(analyticsState.status_summary.menunggu_konfirmasi || 0),
            parseInt(analyticsState.status_summary.aktif || 0),
            parseInt(analyticsState.status_summary.selesai || 0),
            parseInt(analyticsState.status_summary.dibatalkan || 0)
        ] }]
    },
    options: { plugins: { legend: { position: "bottom" } } }
});

const mobilChart = new Chart(document.getElementById("mobilChart"), {
    type: "bar",
    data: {
        labels: analyticsState.mobil_terlaris.labels,
        datasets: [{ label: "Total Sewa", data: analyticsState.mobil_terlaris.data, borderWidth: 1 }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, precision: 0 } } }
});

const harianChart = new Chart(document.getElementById("harianChart"), {
    type: "line",
    data: {
        labels: analyticsState.harian.labels,
        datasets: [{ label: "Booking", data: analyticsState.harian.data, borderWidth: 2, tension: .35, fill: false }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, precision: 0 } } }
});

const formFilterMobil = document.getElementById("formFilterMobil");
const formFilterHarian = document.getElementById("formFilterHarian");
const mobilLoading = document.getElementById("mobilLoading");
const harianLoading = document.getElementById("harianLoading");
const mobilSubtitle = document.getElementById("mobilSubtitle");
const harianTitle = document.getElementById("harianTitle");
const tahunFilterBadge = document.getElementById("tahunFilterBadge");
const tanggalFilterBadge = document.getElementById("tanggalFilterBadge");

function setLoading(type, on) {
    if (type === "mobil") mobilLoading.classList.toggle("active", on);
    if (type === "harian") harianLoading.classList.toggle("active", on);
    if (type === "all") {
        mobilLoading.classList.toggle("active", on);
        harianLoading.classList.toggle("active", on);
    }
}

function updateUrl(filters) {
    const url = new URL(window.location.href);
    url.searchParams.set("tahun_mobil", filters.tahun_mobil);
    url.searchParams.set("tanggal_mulai", filters.tanggal_mulai);
    url.searchParams.set("tanggal_selesai", filters.tanggal_selesai);
    history.replaceState({}, "", url.toString());
}

function syncFormValues(filters) {
    formFilterMobil.querySelector("[name=tanggal_mulai]").value = filters.tanggal_mulai;
    formFilterMobil.querySelector("[name=tanggal_selesai]").value = filters.tanggal_selesai;
    formFilterHarian.querySelector("[name=tahun_mobil]").value = filters.tahun_mobil;
    document.getElementById("tahun_mobil").value = filters.tahun_mobil;
    document.getElementById("tanggal_mulai").value = filters.tanggal_mulai;
    document.getElementById("tanggal_selesai").value = filters.tanggal_selesai;
}

function renderInteractiveCharts(payload) {
    mobilChart.data.labels = payload.mobil_terlaris.labels;
    mobilChart.data.datasets[0].data = payload.mobil_terlaris.data;
    mobilChart.update();

    harianChart.data.labels = payload.harian.labels;
    harianChart.data.datasets[0].data = payload.harian.data;
    harianChart.update();

    mobilSubtitle.textContent = payload.mobil_terlaris.subtitle;
    harianTitle.textContent = payload.harian.title;
    tahunFilterBadge.textContent = "Tahun " + payload.filters.tahun_mobil;
    tanggalFilterBadge.textContent = payload.filters.tanggal_mulai + " s/d " + payload.filters.tanggal_selesai;

    syncFormValues(payload.filters);
    updateUrl(payload.filters);
}

async function fetchInteractiveCharts(sourceForm, loadingType) {
    const params = new URLSearchParams(new FormData(sourceForm));
    params.set("ajax", "1");
    setLoading(loadingType, true);
    try {
        const res = await fetch("analitik.php?" + params.toString(), {
            headers: { "X-Requested-With": "XMLHttpRequest" }
        });
        const payload = await res.json();
        if (!payload.ok) throw new Error("Gagal memuat data.");
        renderInteractiveCharts(payload);
    } catch (err) {
        alert("Gagal memperbarui grafik. Silakan coba lagi.");
    } finally {
        setLoading(loadingType, false);
    }
}

formFilterMobil.addEventListener("submit", function(e) {
    e.preventDefault();
    fetchInteractiveCharts(formFilterMobil, "mobil");
});

formFilterHarian.addEventListener("submit", function(e) {
    e.preventDefault();
    fetchInteractiveCharts(formFilterHarian, "harian");
});
</script>';
admin_page_end($extra);
?>