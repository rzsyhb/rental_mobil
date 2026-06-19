<?php
session_start();
include "config/koneksi.php";
include "config/helpers.php";
bootstrap_app($conn);

$query = mysqli_query($conn, "SELECT * FROM mobil ORDER BY id_mobil DESC");
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$namaSession = isset($_SESSION['nama']) ? $_SESSION['nama'] : '';

if ($role === 'admin') {
    $topLink = 'admin/dashboard.php';
    $topLabel = 'Dashboard Admin';
} elseif ($role === 'customer') {
    $topLink = 'customer/dashboard.php';
    $topLabel = 'Halo, ' . $namaSession;
} else {
    $topLink = 'auth/login.php';
    $topLabel = 'Login';
}

$company = company_info();
$waNumber = preg_replace('/\D+/', '', $company['wa'] ?? '');
$waNumberIntl = preg_replace('/^0/', '62', $waNumber);
$waBaseLink = 'https://wa.me/' . $waNumberIntl;
$waContacts = [
    [
        'nama' => 'Admin Rental',
        'jabatan' => 'Informasi Rental Mobil',
        'pesan' => 'Halo Admin Rental, saya ingin bertanya tentang Rental Mobil.',
    ],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Brawijaya Rental & Travel - Katalog Mobil</title>
    <link rel="stylesheet" href="assets/adminlte/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/custom-theme.css">
    <style>
        .wa-widget {
            position: fixed;
            right: 24px;
            bottom: 24px;
            z-index: 1080;
            display: flex;
            align-items: flex-end;
            gap: 14px;
        }
        .wa-widget-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #ffffff;
            color: #0f172a;
            padding: 1px 3px;
            border-radius: 14px;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.18);
            font-weight: 600;
            max-width: 200px;
            cursor: pointer;
            border: 0;
        }
        .wa-widget-badge:hover {
            transform: translateY(-1px);
        }
        .wa-widget-badge i {
            color: #25d366;
            font-size: 1.15rem;
        }
        .wa-widget-trigger {
            width: 44px;
            height: 44px;
            border: 0;
            border-radius: 50%;
            background: #25d366;
            color: #ffffff;
            font-size: 1.95rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 14px 30px rgba(37, 211, 102, 0.36);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .wa-widget-trigger:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 18px 34px rgba(37, 211, 102, 0.42);
        }
        .wa-widget-panel {
            position: absolute;
            right: 0;
            bottom: 82px;
            width: min(350px, calc(50vw - 22px));
            background: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 22px 48px rgba(15, 23, 42, 0.22);
            opacity: 0;
            visibility: hidden;
            transform: translateY(16px) scale(0.98);
            transition: opacity 0.22s ease, transform 0.22s ease, visibility 0.22s ease;
        }
        .wa-widget.is-open .wa-widget-panel {
            opacity: 1;
            visibility: visible;
            transform: translateY(0) scale(1);
        }
        .wa-widget-header {
            background: linear-gradient(135deg, #25d366 0%, #18b655 100%);
            color: #ffffff;
            padding: 20px 22px;
        }
        .wa-widget-header-top {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 8px;
        }
        .wa-widget-header-icon {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.14);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.7rem;
            flex-shrink: 0;
        }
        .wa-widget-title {
            margin: 0;
            font-size: 1.55rem;
            font-weight: 800;
            line-height: 1.1;
        }
        .wa-widget-subtitle,
        .wa-widget-note {
            margin: 0;
            color: rgba(255, 255, 255, 0.92);
        }
        .wa-widget-note {
            margin-top: 10px;
            font-size: 0.92rem;
        }
        .wa-widget-body {
            background: #f8fafc;
            padding: 16px;
        }
        .wa-contact-list {
            display: grid;
            gap: 12px;
        }
        .wa-contact-item {
            display: flex;
            align-items: center;
            gap: 14px;
            text-decoration: none;
            color: #111827;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-left: 4px solid #25d366;
            border-radius: 14px;
            padding: 14px 16px;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }
        .wa-contact-item:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.10);
            border-color: #d1fae5;
        }
        .wa-contact-avatar {
            width: 54px;
            height: 54px;
            border-radius: 50%;
            background: #e9fbee;
            color: #25d366;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.65rem;
            flex-shrink: 0;
        }
        .wa-contact-text {
            min-width: 0;
            flex: 1;
        }
        .wa-contact-name {
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 3px;
        }
        .wa-contact-role {
            font-size: 0.92rem;
            color: #6b7280;
            line-height: 1.35;
        }
        .wa-contact-action {
            color: #25d366;
            font-size: 1.25rem;
            flex-shrink: 0;
        }
        .wa-widget.is-open .wa-widget-trigger .bi-whatsapp,
        .wa-widget:not(.is-open) .wa-widget-trigger .bi-x-lg {
            display: none;
        }
        .wa-widget.is-open .wa-widget-badge {
            opacity: 0;
            visibility: hidden;
            transform: translateY(8px);
        }
        .catalog-desc {
            margin-top: 8px;
            color: #6b7280;
            font-size: 0.95rem;
            line-height: 1.5;
        }
        @media (max-width: 575.98px) {
            .wa-widget {
                right: 16px;
                bottom: 16px;
            }
            .wa-widget-badge {
                display: none;
            }
            .wa-widget-trigger {
                width: 58px;
                height: 58px;
                font-size: 1.75rem;
            }
            .wa-widget-panel {
                bottom: 74px;
                width: min(360px, calc(100vw - 16px));
            }
            .wa-widget-title {
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body class="site-landing">
    <nav class="navbar site-navbar py-2 py-md-3">
        <div class="container d-flex align-items-center justify-content-between">
            <a href="index.php" class="site-brand">
                <img src="assets/img/logo-brawijaya.png" alt="Brawijaya Rental" class="site-brand-logo">
                <span class="site-brand-text">
                    <span class="site-brand-title d-block">BRAWIJAYA</span>
                    <span class="site-brand-subtitle">Rental Mobil</span>
                </span>
            </a>
            <a href="<?= esc($topLink); ?>" class="site-login-btn"><?= esc($topLabel); ?></a>
        </div>
    </nav>

    <main class="py-5">
        <div class="container">
            <div class="section-title-wrap">
                <h1 class="section-title">ARMADA KAMI</h1>
                <div class="section-divider"><i class="bi bi-car-front-fill"></i></div>
                <p class="text-secondary mb-0">
                    Kami selalu menjaga kebersihan, kelayakan, dan kenyamanan armada untuk memberikan pengalaman sewa terbaik untuk Anda.
                </p>
            </div>

            <div class="row g-4 g-lg-4">
                <?php if ($query && mysqli_num_rows($query) > 0) { ?>
                    <?php while ($data = mysqli_fetch_assoc($query)) {
                        $placeholder = 'data:image/svg+xml;utf8,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="640" height="360"><rect width="100%" height="100%" fill="%23f4f4f4"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="%23888" font-family="Arial" font-size="28">Gambar Mobil</text></svg>');
                        $foto = !empty($data['foto']) ? 'uploads/mobil/' . $data['foto'] : $placeholder;
                        $ctaLink = ($role === 'customer') ? 'customer/sewa.php?id=' . (int) $data['id_mobil'] : 'auth/login.php';
                        $ctaLabel = ($role === 'customer') ? 'Booking Sekarang' : 'Login untuk Booking';
                    ?>
                    <div class="col-md-6 col-lg-6">
                        <div class="catalog-card h-100">
                            <div class="catalog-image-wrap">
                                <img src="<?= esc($foto); ?>" alt="<?= esc($data['nama_mobil']); ?>" class="catalog-image">
                            </div>
                            <div class="catalog-card-footer">
                                <div>
                                    <div class="catalog-name"><?= esc($data['nama_mobil']); ?></div>
                                    <p class="catalog-meta mb-0"><?= esc($data['merk']); ?> • Tahun <?= esc($data['tahun']); ?></p>
                                    <?php if (!empty($data['deskripsi'])) { ?>
                                        <p class="catalog-desc mb-0"><?= esc($data['deskripsi']); ?></p>
                                    <?php } ?>
                                </div>
                                <div class="catalog-price">
                                    <?= format_rupiah($data['harga_24jam']); ?>/hari
                                    <span class="catalog-subprice">12 jam <?= format_rupiah($data['harga_12jam']); ?></span>
                                </div>
                            </div>
                            <div class="catalog-actions">
                                <a href="<?= esc($ctaLink); ?>" class="btn btn-dark w-100 catalog-btn">
                                    <i class="bi bi-calendar-check me-2"></i><?= esc($ctaLabel); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                <?php } else { ?>
                    <div class="col-12">
                        <div class="catalog-empty">
                            Belum ada data mobil. Silakan tambahkan armada dari panel admin.
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </main>

    <div class="wa-widget" id="waWidget">
        <button type="button" class="wa-widget-badge" id="waWidgetBadge">
            <i class="bi bi-whatsapp"></i>
            <span>Butuh bantuan? <strong>Chat kami</strong></span>
        </button>

        <div class="wa-widget-panel" id="waWidgetPanel" aria-hidden="true">
            <div class="wa-widget-header">
                <div class="wa-widget-header-top">
                    <div class="wa-widget-header-icon"><i class="bi bi-whatsapp"></i></div>
                    <div>
                        <h2 class="wa-widget-title">Mulai Percakapan</h2>
                        <p class="wa-widget-subtitle">Klik admin kami untuk chat via WhatsApp.</p>
                    </div>
                </div>
                <p class="wa-widget-note">Biasanya membalas dalam beberapa menit.</p>
            </div>
            <div class="wa-widget-body">
                <div class="wa-contact-list">
                    <?php foreach ($waContacts as $contact) {
                        $waLink = $waBaseLink . '?text=' . rawurlencode($contact['pesan']);
                    ?>
                    <a href="<?= esc($waLink); ?>" target="_blank" rel="noopener" class="wa-contact-item">
                        <span class="wa-contact-avatar"><i class="bi bi-whatsapp"></i></span>
                        <span class="wa-contact-text">
                            <span class="wa-contact-name"><?= esc($contact['nama']); ?></span>
                            <span class="wa-contact-role"><?= esc($contact['jabatan']); ?></span>
                        </span>
                        <span class="wa-contact-action"><i class="bi bi-arrow-up-right-circle"></i></span>
                    </a>
                    <?php } ?>
                </div>
            </div>
        </div>

        <button type="button" class="wa-widget-trigger" id="waWidgetToggle" aria-label="Buka WhatsApp" aria-expanded="false" aria-controls="waWidgetPanel">
            <i class="bi bi-whatsapp"></i>
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <?= info_kontak_footer_html(); ?>

    <script>
        (function () {
            const widget = document.getElementById('waWidget');
            const toggle = document.getElementById('waWidgetToggle');
            const badge = document.getElementById('waWidgetBadge');
            const panel = document.getElementById('waWidgetPanel');
            if (!widget || !toggle || !panel) return;

            function setOpenState(isOpen) {
                widget.classList.toggle('is-open', isOpen);
                toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                panel.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
            }

            toggle.addEventListener('click', function () {
                const isOpen = widget.classList.contains('is-open');
                setOpenState(!isOpen);
            });

            if (badge) {
                badge.addEventListener('click', function () {
                    setOpenState(true);
                });
            }

            document.addEventListener('click', function (event) {
                if (!widget.contains(event.target)) {
                    setOpenState(false);
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    setOpenState(false);
                }
            });
        })();
    </script>
</body>
</html>
