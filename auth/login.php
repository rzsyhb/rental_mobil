<?php session_start(); ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Brawijaya Rental & Travel</title>
<link rel="stylesheet" href="../assets/adminlte/dist/css/adminlte.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="../assets/css/custom-theme.css">
</head>
<body class="auth-page">
<div class="auth-shell">
    <div class="auth-promo">
        <img src="../assets/img/logo-brawijaya.png" alt="Brawijaya Rental" class="auth-logo">
        <h1>Brawijaya Rental & Travel</h1>
        <p>Masuk untuk mengelola booking, melihat invoice, dan memantau status rental secara real-time.</p>
        <div class="mt-4">
            <span class="hero-badge"><i class="bi bi-shield-check"></i> Aman, cepat, dan mudah digunakan</span>
        </div>
    </div>
    <div class="auth-form-panel">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-1 fw-bold">Login</h3>
                <p class="text-muted mb-0">Masukkan username dan password Anda.</p>
            </div>
            <a href="../index.php" class="auth-link-back"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
        </div>
        <form action="proses_login.php" method="POST">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-brand w-100">Login</button>
        </form>
        <div class="text-center mt-4">
            Belum memiliki akun? <a href="register.php">Buat Akun</a>
        </div>
    </div>
</div>
</body>
</html>
