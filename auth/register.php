<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registrasi Customer - Brawijaya Rental & Travel</title>
<link rel="stylesheet" href="../assets/adminlte/dist/css/adminlte.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="../assets/css/custom-theme.css">
</head>
<body class="auth-page">
<div class="auth-shell">
    <div class="auth-promo">
        <img src="../assets/img/logo-brawijaya.png" alt="Brawijaya Rental" class="auth-logo">
        <h1>Daftar Customer Baru</h1>
        <p>Lengkapi data Anda agar proses booking, invoice, dan verifikasi dokumen berjalan lebih cepat.</p>
        <div class="mt-4">
            <span class="hero-badge"><i class="bi bi-card-checklist"></i> Upload KTP saat registrasi</span>
        </div>
    </div>
    <div class="auth-form-panel">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-1 fw-bold">Registrasi Customer</h3>
                <p class="text-muted mb-0">Buat akun untuk mulai melakukan booking.</p>
            </div>
            <a href="login.php" class="auth-link-back"><i class="bi bi-arrow-left me-1"></i>Login</a>
        </div>
        <form action="proses_register.php" method="POST" id="formRegister" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-12"><label class="form-label">Nama</label><input type="text" name="nama" class="form-control" required></div>
                <div class="col-12"><label class="form-label">Alamat</label><textarea name="alamat" class="form-control" required></textarea></div>
                <div class="col-md-6"><label class="form-label">Jenis Kelamin</label><select name="jenis_kelamin" class="form-select" required><option value="" disabled selected>-- Pilih Jenis Kelamin --</option><option value="L">Laki-laki</option><option value="P">Perempuan</option></select></div>
                <div class="col-md-6"><label class="form-label">No Telepon</label><input type="text" name="no_hp" id="no_hp" class="form-control"><small id="errorHp" class="text-danger"></small></div>
                <div class="col-md-6"><label class="form-label">Username</label><input type="text" name="username" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label">Password</label><input type="password" name="password" id="password" class="form-control"><small id="errorPassword" class="text-danger"></small></div>
                <div class="col-12"><label class="form-label">Upload Foto KTP</label><input type="file" name="foto" class="form-control" accept="image/*" required><small class="text-muted">Format: JPG, PNG. Maksimal 3 MB.</small></div>
            </div>
            <div class="page-actions mt-4">
                <button type="submit" class="btn btn-brand">Register</button>
                <a href="login.php" class="btn btn-outline-secondary">Kembali</a>
            </div>
        </form>
    </div>
</div>
<script>
let password = document.getElementById("password");
let no_hp = document.getElementById("no_hp");
let errorPassword = document.getElementById("errorPassword");
let errorHp = document.getElementById("errorHp");
password.addEventListener("keyup", function() {
    if (this.value.length < 8) {
        this.classList.add("is-invalid");
        this.classList.remove("is-valid");
        errorPassword.innerHTML = "Password minimal 8 karakter";
    } else {
        this.classList.remove("is-invalid");
        this.classList.add("is-valid");
        errorPassword.innerHTML = "";
    }
});
no_hp.addEventListener("keyup", function() {
    if (!/^[0-9]+$/.test(this.value)) {
        this.classList.add("is-invalid");
        this.classList.remove("is-valid");
        errorHp.innerHTML = "Nomor telepon harus angka";
    } else if (this.value.length < 8) {
        this.classList.add("is-invalid");
        this.classList.remove("is-valid");
        errorHp.innerHTML = "Minimal 8 digit";
    } else {
        this.classList.remove("is-invalid");
        this.classList.add("is-valid");
        errorHp.innerHTML = "";
    }
});
document.getElementById("formRegister").addEventListener("submit", function(e) {
    let valid = true;
    if (password.value.length < 8) {
        errorPassword.innerHTML = "Password minimal 8 karakter";
        password.classList.add("is-invalid");
        valid = false;
    }
    if (!/^[0-9]+$/.test(no_hp.value) || no_hp.value.length < 8) {
        errorHp.innerHTML = "Nomor telepon tidak valid";
        no_hp.classList.add("is-invalid");
        valid = false;
    }
    if (!valid) { e.preventDefault(); }
});
</script>
</body>
</html>
