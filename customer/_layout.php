<?php
if (!function_exists('customer_require_login')) {
    function customer_require_login()
    {
        if (!isset($_SESSION['login']) || ($_SESSION['role'] ?? '') !== 'customer') {
            header('Location: ../auth/login.php');
            exit;
        }
    }
}

if (!function_exists('customer_menu_items')) {
    function customer_menu_items()
    {
        return [
            'dashboard' => ['label' => 'Dashboard', 'icon' => 'bi bi-speedometer2', 'href' => 'dashboard.php'],
            'mobil' => ['label' => 'Armada', 'icon' => 'bi bi-car-front', 'href' => 'mobil.php'],
            'transaksi' => ['label' => 'Transaksi', 'icon' => 'bi bi-receipt', 'href' => 'transaksi.php'],
            'password' => ['label' => 'Ganti Password', 'icon' => 'bi bi-key', 'href' => 'ganti_password.php'],
            'logout' => ['label' => 'Logout', 'icon' => 'bi bi-box-arrow-right', 'href' => '../auth/logout.php'],
        ];
    }
}

if (!function_exists('customer_page_start')) {
    function customer_page_start($title, $active = 'dashboard', $extraHead = '')
    {
        $menu = customer_menu_items();
        $pageTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $userName = htmlspecialchars($_SESSION['nama'] ?? 'Customer', ENT_QUOTES, 'UTF-8');

        echo <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$pageTitle}</title>
    <link rel="stylesheet" href="../assets/adminlte/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/custom-theme.css">
    {$extraHead}
</head>
<body class="layout-fixed sidebar-expand-lg dashboard-page customer-theme">
<div class="app-wrapper">
    <nav class="app-header navbar navbar-expand customer-header border-0 shadow-sm">
        <div class="container-fluid">
            <ul class="navbar-nav align-items-center">
                <li class="nav-item"><a class="nav-link" data-lte-toggle="sidebar" href="#" role="button"><i class="bi bi-list fs-4"></i></a></li>
                <li class="nav-item d-none d-md-block"><span class="navbar-text fw-semibold">{$pageTitle}</span></li>
            </ul>
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item"><span class="navbar-text">Halo, {$userName}</span></li>
            </ul>
        </div>
    </nav>
    <aside class="app-sidebar customer-sidebar shadow" data-bs-theme="light">
        <div class="sidebar-brand p-0">
            <a href="dashboard.php" class="brand-link text-decoration-none border-0">
                <div class="brand-logo-row">
                    <img src="../assets/img/logo-brawijaya.png" alt="Brawijaya Rental" class="brand-mini-logo">
                    <span class="brand-copy">
                        <span class="brand-main">BRAWIJAYA</span>
                        <span class="brand-sub">Rental Mobil</span>
                    </span>
                </div>
            </a>
        </div>
        <div class="sidebar-wrapper">
            <nav class="mt-3">
                <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="navigation" aria-label="Main navigation" data-accordion="false">
HTML;
        foreach ($menu as $key => $item) {
            $href = htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8');
            $label = htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8');
            $icon = htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8');
            $cls = $key === $active ? 'nav-link active' : 'nav-link';
            echo '<li class="nav-item"><a href="' . $href . '" class="' . $cls . '"><i class="nav-icon ' . $icon . '"></i><p>' . $label . '</p></a></li>';
        }
        echo <<<HTML
                </ul>
            </nav>
        </div>
    </aside>
    <main class="app-main">
        <div class="app-content dashboard-content">
            <div class="container-fluid">
                <div class="row mb-3"><div class="col-12"><h3 class="dashboard-title">{$pageTitle}</h3></div></div>
HTML;
    }
}

if (!function_exists('customer_page_end')) {
    function customer_page_end($extraScripts = '')
    {
        echo <<<HTML
            </div>
HTML;
        echo info_kontak_footer_html('../');
        echo <<<HTML
        </div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/adminlte/dist/js/adminlte.min.js"></script>
{$extraScripts}
</body>
</html>
HTML;
    }
}
?>
