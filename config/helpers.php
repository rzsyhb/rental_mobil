<?php
require_once __DIR__ . '/secure_config.php';

function table_has_column($conn, $table, $column)
{
    $table = mysqli_real_escape_string($conn, $table);
    $column = mysqli_real_escape_string($conn, $column);
    $query = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $query && mysqli_num_rows($query) > 0;
}

function ensure_schema($conn)
{
    @mysqli_query($conn, "ALTER TABLE mobil ADD COLUMN id_admin INT UNSIGNED NULL AFTER id_mobil");
    @mysqli_query($conn, "ALTER TABLE mobil ADD KEY idx_mobil_admin (id_admin)");
    @mysqli_query($conn, "ALTER TABLE mobil ADD CONSTRAINT fk_mobil_admin FOREIGN KEY (id_admin) REFERENCES admin(id_admin) ON DELETE SET NULL ON UPDATE CASCADE");
    @mysqli_query($conn, "ALTER TABLE mobil ADD COLUMN status VARCHAR(30) NOT NULL DEFAULT 'tersedia'");
    @mysqli_query($conn, "ALTER TABLE mobil MODIFY COLUMN status VARCHAR(30) NOT NULL DEFAULT 'tersedia'");
    @mysqli_query($conn, "ALTER TABLE transaksi ADD COLUMN id_admin INT UNSIGNED NULL AFTER id_mobil");
    @mysqli_query($conn, "ALTER TABLE transaksi ADD KEY idx_transaksi_admin (id_admin)");
    @mysqli_query($conn, "ALTER TABLE transaksi ADD CONSTRAINT fk_transaksi_admin FOREIGN KEY (id_admin) REFERENCES admin(id_admin) ON DELETE SET NULL ON UPDATE CASCADE");
    @mysqli_query($conn, "ALTER TABLE transaksi MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'menunggu pembayaran'");
    @mysqli_query($conn, "ALTER TABLE transaksi ADD COLUMN bukti_pembayaran VARCHAR(255) NULL AFTER total_harga");
    @mysqli_query($conn, "ALTER TABLE transaksi ADD COLUMN sim_a VARCHAR(255) NULL AFTER bukti_pembayaran");
    @mysqli_query($conn, "ALTER TABLE transaksi MODIFY COLUMN sim_a VARCHAR(255) NULL");
    @mysqli_query($conn, "ALTER TABLE transaksi ADD COLUMN catatan_admin TEXT NULL AFTER status");
    @mysqli_query($conn, "ALTER TABLE transaksi ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
    @mysqli_query($conn, "ALTER TABLE transaksi ADD COLUMN overtime_jam INT NOT NULL DEFAULT 0 AFTER catatan_admin");
    @mysqli_query($conn, "ALTER TABLE transaksi ADD COLUMN overtime_biaya DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER overtime_jam");
    @mysqli_query($conn, "ALTER TABLE transaksi ADD COLUMN total_akhir DECIMAL(15,2) NULL AFTER overtime_biaya");
    @mysqli_query($conn, "ALTER TABLE transaksi ADD COLUMN selesai_admin_at DATETIME NULL AFTER total_akhir");

    @mysqli_query($conn, "ALTER TABLE customer MODIFY COLUMN no_hp VARCHAR(255) NOT NULL");
    @mysqli_query($conn, "ALTER TABLE customer MODIFY COLUMN foto VARCHAR(255) NULL");
    @mysqli_query($conn, "ALTER TABLE admin MODIFY COLUMN password VARCHAR(255) NOT NULL");
    @mysqli_query($conn, "ALTER TABLE customer MODIFY COLUMN password VARCHAR(255) NOT NULL");
}

function tarif_overtime_per_jam()
{
    return 30000;
}

function company_info()
{
    $alamat = 'No.131 b, Jl. Brawijaya, Mangunrejo, Tulungrejo, Kec. Pare, Kabupaten Kediri, Jawa Timur 64212';

    return [
        'nama' => 'Brawijaya Rental Mobil',
        'wa' => '08877120304',
        'wa_link' => 'https://wa.me/628877120304',
        'alamat' => $alamat,
        'maps_link' => 'https://www.google.com/maps/place/Brawijaya+Tour+%26+Travel/@-7.7556428,112.1795909,17z/data=!3m1!4b1!4m6!3m5!1s0x2e785ddc9d7d0b47:0xca01c31704669bb8!8m2!3d-7.7556428!4d112.1795909!16s%2Fg%2F11k9rd3kgz!18m1!1e1?entry=ttu&g_ep=EgoyMDI2MDQxMy4wIKXMDSoASAFQAw%3D%3D' . rawurlencode($alamat),
    ];
}

function app_sodium_supported()
{
    return defined('SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES')
        && defined('SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES')
        && function_exists('sodium_crypto_aead_xchacha20poly1305_ietf_encrypt')
        && function_exists('sodium_crypto_aead_xchacha20poly1305_ietf_decrypt');
}

function app_crypto_key_bytes()
{
    return app_sodium_supported() ? SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES : 32;
}

function app_crypto_nonce_bytes()
{
    return app_sodium_supported() ? SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES : 12;
}

function app_secret_key()
{
    static $key = null;
    if ($key !== null) {
        return $key;
    }

    $raw = defined('APP_SECRET_KEY_BASE64') ? (string) APP_SECRET_KEY_BASE64 : '';
    $decoded = base64_decode($raw, true);
    $keyBytes = app_crypto_key_bytes();
    if ($decoded !== false && strlen($decoded) >= $keyBytes) {
        $key = substr($decoded, 0, $keyBytes);
        return $key;
    }

    $fallback = hash('sha256', __DIR__ . '|rental_mobil_secure_key', true);
    $key = substr($fallback, 0, $keyBytes);
    return $key;
}

function app_encrypt_value($plaintext, $aad = 'db-field')
{
    $plaintext = (string) $plaintext;
    if ($plaintext === '') {
        return '';
    }
    if (strpos($plaintext, 'enc:') === 0 || strpos($plaintext, 'enc2:') === 0) {
        return $plaintext;
    }

    if (app_sodium_supported()) {
        $nonce = random_bytes(app_crypto_nonce_bytes());
        $cipher = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt($plaintext, $aad, $nonce, app_secret_key());
        return 'enc:' . rtrim(strtr(base64_encode($nonce . $cipher), '+/', '-_'), '=');
    }

    if (!function_exists('openssl_encrypt')) {
        return '';
    }

    $iv = random_bytes(app_crypto_nonce_bytes());
    $tag = '';
    $cipher = openssl_encrypt($plaintext, 'aes-256-gcm', app_secret_key(), OPENSSL_RAW_DATA, $iv, $tag, $aad);
    if ($cipher === false) {
        return '';
    }

    return 'enc2:' . rtrim(strtr(base64_encode($iv . $tag . $cipher), '+/', '-_'), '=');
}

function app_decrypt_value($value, $aad = 'db-field')
{
    $value = (string) $value;
    if ($value === '') {
        return '';
    }

    if (strpos($value, 'enc2:') === 0) {
        $encoded = substr($value, 5);
        $encoded .= str_repeat('=', (4 - strlen($encoded) % 4) % 4);
        $decoded = base64_decode(strtr($encoded, '-_', '+/'), true);
        if ($decoded === false || strlen($decoded) <= 28 || !function_exists('openssl_decrypt')) {
            return '';
        }

        $iv = substr($decoded, 0, 12);
        $tag = substr($decoded, 12, 16);
        $cipher = substr($decoded, 28);
        $plain = openssl_decrypt($cipher, 'aes-256-gcm', app_secret_key(), OPENSSL_RAW_DATA, $iv, $tag, $aad);
        return $plain === false ? '' : $plain;
    }

    if (strpos($value, 'enc:') !== 0) {
        return $value;
    }

    $encoded = substr($value, 4);
    $encoded .= str_repeat('=', (4 - strlen($encoded) % 4) % 4);
    $decoded = base64_decode(strtr($encoded, '-_', '+/'), true);
    if ($decoded === false || strlen($decoded) <= 24 || !app_sodium_supported()) {
        return '';
    }

    $nonce = substr($decoded, 0, 24);
    $cipher = substr($decoded, 24);
    try {
        $plain = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt($cipher, $aad, $nonce, app_secret_key());
        return $plain === false ? '' : $plain;
    } catch (Throwable $e) {
        return '';
    }
}

function app_encrypt_binary($plaintext, $aad)
{
    if (app_sodium_supported()) {
        $nonce = random_bytes(24);
        $cipher = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt($plaintext, $aad, $nonce, app_secret_key());
        return 'RMK1' . $nonce . $cipher;
    }

    if (!function_exists('openssl_encrypt')) {
        return false;
    }

    $iv = random_bytes(12);
    $tag = '';
    $cipher = openssl_encrypt($plaintext, 'aes-256-gcm', app_secret_key(), OPENSSL_RAW_DATA, $iv, $tag, $aad);
    if ($cipher === false) {
        return false;
    }

    return 'RMK2' . $iv . $tag . $cipher;
}

function app_decrypt_binary($payload, $aad)
{
    if (!is_string($payload) || strlen($payload) < 4) {
        return false;
    }

    $header = substr($payload, 0, 4);
    if ($header === 'RMK1') {
        if (!app_sodium_supported()) {
            return false;
        }
        $offset = 4;
        $nonce = substr($payload, $offset, 24);
        $cipher = substr($payload, $offset + 24);
        if ($nonce === '' || $cipher === '') {
            return false;
        }

        try {
            return sodium_crypto_aead_xchacha20poly1305_ietf_decrypt($cipher, $aad, $nonce, app_secret_key());
        } catch (Throwable $e) {
            return false;
        }
    }

    if ($header === 'RMK2') {
        if (!function_exists('openssl_decrypt')) {
            return false;
        }
        $offset = 4;
        $iv = substr($payload, $offset, 12);
        $tag = substr($payload, $offset + 12, 16);
        $cipher = substr($payload, $offset + 28);
        if ($iv === '' || $tag === '' || $cipher === '') {
            return false;
        }

        return openssl_decrypt($cipher, 'aes-256-gcm', app_secret_key(), OPENSSL_RAW_DATA, $iv, $tag, $aad);
    }

    return false;
}

function app_encrypt_phone($phone)
{
    return app_encrypt_value(trim((string) $phone), 'customer-nohp');
}

function app_decrypt_phone($phone)
{
    return app_decrypt_value((string) $phone, 'customer-nohp');
}

function app_password_is_hashed($hash)
{
    $info = password_get_info((string) $hash);
    return !empty($info['algo']);
}

function app_hash_password($password)
{
    return password_hash((string) $password, PASSWORD_ARGON2ID);
}

function app_password_matches($plainPassword, $storedHash)
{
    $storedHash = (string) $storedHash;
    if ($storedHash === '') {
        return false;
    }
    if (app_password_is_hashed($storedHash)) {
        return password_verify((string) $plainPassword, $storedHash);
    }
    return hash_equals($storedHash, (string) $plainPassword);
}

function app_relative_project_path($path)
{
    return ltrim(str_replace('\\', '/', (string) $path), '/');
}

function app_project_root()
{
    return dirname(__DIR__);
}

function app_private_storage_root()
{
    return app_project_root() . '/storage/private';
}

function app_private_storage_dir($category)
{
    return rtrim(app_private_storage_root(), '/') . '/' . trim((string) $category, '/');
}

function app_reference_is_encrypted($value)
{
    $value = (string) $value;
    return strpos($value, 'enc:') === 0 || strpos($value, 'enc2:') === 0;
}

function app_plain_secure_reference($value)
{
    $value = (string) $value;
    if ($value === '') {
        return '';
    }
    return app_reference_is_encrypted($value) ? app_decrypt_value($value, 'secure-file-ref') : $value;
}

function app_encrypt_secure_reference($plainReference)
{
    return app_encrypt_value((string) $plainReference, 'secure-file-ref');
}

function app_secure_ref_to_absolute_path($dbValue)
{
    $plain = app_plain_secure_reference($dbValue);
    if ($plain === '') {
        return '';
    }
    return app_project_root() . '/' . app_relative_project_path($plain);
}

function app_encrypt_uploaded_file($tmpPath, $category, $originalName = '')
{
    if (!is_file($tmpPath)) {
        return '';
    }

    $binary = @file_get_contents($tmpPath);
    if ($binary === false) {
        return '';
    }

    $ext = strtolower(pathinfo((string) $originalName, PATHINFO_EXTENSION));
    $token = bin2hex(random_bytes(18));
    $filename = $token . '.bin';
    $relativePath = 'storage/private/' . trim((string) $category, '/') . '/' . $filename;
    $absolutePath = app_project_root() . '/' . $relativePath;
    $dir = dirname($absolutePath);
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }

    $aad = 'secure-file|' . trim((string) $category, '/') . '|' . $ext;
    $payload = app_encrypt_binary($binary, $aad);
    if (@file_put_contents($absolutePath, $payload) === false) {
        return '';
    }

    return app_encrypt_secure_reference($relativePath);
}

function app_read_encrypted_file($dbValue)
{
    $plainReference = app_plain_secure_reference($dbValue);
    if ($plainReference === '' || strpos($plainReference, 'storage/private/') !== 0) {
        return false;
    }

    $absolutePath = app_project_root() . '/' . $plainReference;
    if (!is_file($absolutePath)) {
        return false;
    }

    $category = trim(dirname(substr($plainReference, strlen('storage/private/'))), '/');
    $payload = @file_get_contents($absolutePath);
    if ($payload === false) {
        return false;
    }

    $candidates = [
        'secure-file|' . $category . '|jpg',
        'secure-file|' . $category . '|jpeg',
        'secure-file|' . $category . '|png',
        'secure-file|' . $category . '|webp',
        'secure-file|' . $category . '|',
    ];

    foreach ($candidates as $aad) {
        $plain = app_decrypt_binary($payload, $aad);
        if ($plain !== false) {
            return $plain;
        }
    }

    return false;
}

function app_remove_secure_file($dbValue)
{
    $absolutePath = app_secure_ref_to_absolute_path($dbValue);
    if ($absolutePath !== '' && is_file($absolutePath)) {
        @unlink($absolutePath);
    }
}

function app_migrate_legacy_secure_file($dbValue, $category, $legacyBaseDir, $deleteLegacy = true)
{
    $dbValue = trim((string) $dbValue);
    if ($dbValue === '') {
        return '';
    }

    $plainReference = app_plain_secure_reference($dbValue);
    if (strpos($plainReference, 'storage/private/') === 0 && is_file(app_project_root() . '/' . $plainReference)) {
        return app_reference_is_encrypted($dbValue) ? $dbValue : app_encrypt_secure_reference($plainReference);
    }

    $relativePath = '';
    if (strpos($dbValue, 'uploads/') === 0) {
        $relativePath = app_relative_project_path($dbValue);
    } else {
        $relativePath = trim($legacyBaseDir, '/') . '/' . ltrim($dbValue, '/');
    }

    $absoluteLegacy = app_project_root() . '/' . $relativePath;
    if (!is_file($absoluteLegacy)) {
        return $dbValue;
    }

    $newRef = app_encrypt_uploaded_file($absoluteLegacy, $category, basename($absoluteLegacy));
    if ($newRef !== '' && $deleteLegacy) {
        @unlink($absoluteLegacy);
    }

    return $newRef !== '' ? $newRef : $dbValue;
}

function app_migrate_sensitive_data($conn)
{
    $adminRows = @mysqli_query($conn, "SELECT id_admin, password FROM admin");
    if ($adminRows) {
        while ($row = mysqli_fetch_assoc($adminRows)) {
            if (!app_password_is_hashed($row['password'])) {
                $hash = mysqli_real_escape_string($conn, app_hash_password($row['password']));
                $id = (int) $row['id_admin'];
                @mysqli_query($conn, "UPDATE admin SET password='$hash' WHERE id_admin='$id'");
            }
        }
    }

    $customerRows = @mysqli_query($conn, "SELECT id_customer, no_hp, password, foto FROM customer");
    if ($customerRows) {
        while ($row = mysqli_fetch_assoc($customerRows)) {
            $updates = [];

            if (!app_password_is_hashed($row['password'])) {
                $updates[] = "password='" . mysqli_real_escape_string($conn, app_hash_password($row['password'])) . "'";
            }

            if (trim((string) $row['no_hp']) !== '' && !app_reference_is_encrypted($row['no_hp'])) {
                $updates[] = "no_hp='" . mysqli_real_escape_string($conn, app_encrypt_phone($row['no_hp'])) . "'";
            }

            $migratedFoto = app_migrate_legacy_secure_file($row['foto'], 'customer_ktp', 'uploads/customer');
            if ($migratedFoto !== (string) $row['foto'] && $migratedFoto !== '') {
                $updates[] = "foto='" . mysqli_real_escape_string($conn, $migratedFoto) . "'";
            }

            if ($updates) {
                $id = (int) $row['id_customer'];
                @mysqli_query($conn, "UPDATE customer SET " . implode(', ', $updates) . " WHERE id_customer='$id'");
            }
        }
    }

    $trxRows = @mysqli_query($conn, "SELECT id_transaksi, sim_a FROM transaksi");
    if ($trxRows) {
        while ($row = mysqli_fetch_assoc($trxRows)) {
            $migratedSim = app_migrate_legacy_secure_file($row['sim_a'], 'sim_a', 'uploads/sim');
            if ($migratedSim !== (string) $row['sim_a'] && $migratedSim !== '') {
                $id = (int) $row['id_transaksi'];
                $safe = mysqli_real_escape_string($conn, $migratedSim);
                @mysqli_query($conn, "UPDATE transaksi SET sim_a='$safe' WHERE id_transaksi='$id'");
            }
        }
    }
}

function secure_file_url($entity, $id)
{
    return '../secure_file.php?entity=' . rawurlencode((string) $entity) . '&id=' . (int) $id;
}

function customer_no_hp_plain($row)
{
    return app_decrypt_phone($row['no_hp'] ?? '');
}

function normalisasi_status_mobil($conn)
{
    if (!table_has_column($conn, 'mobil', 'status')) {
        return;
    }

    @mysqli_query($conn, "UPDATE mobil SET status=TRIM(status) WHERE status <> TRIM(status)");
    @mysqli_query($conn, "UPDATE mobil SET status='sedang diservice' WHERE LOWER(TRIM(status)) IN ('service','servis','sedang service','sedang servis','sedang diservice','diservice','di service','perbaikan','maintenance')");
    @mysqli_query($conn, "UPDATE mobil SET status='disewa' WHERE LOWER(TRIM(status)) IN ('disewa','sedang disewa','rented')");
    @mysqli_query($conn, "UPDATE mobil SET status='tersedia' WHERE status IS NULL OR TRIM(status)='' OR LOWER(TRIM(status)) IN ('tersedia','ready','available')");
}

function mobil_status_info($status)
{
    $status = trim(strtolower((string) $status));
    if ($status === 'sedang diservice') {
        return ['label' => 'Sedang Diservice', 'class' => 'status-warning'];
    }
    if ($status === 'disewa') {
        return ['label' => 'Disewa', 'class' => 'status-danger'];
    }
    return ['label' => 'Tersedia', 'class' => 'status-primary'];
}

function mobil_bisa_disewa($mobil)
{
    $status = strtolower(trim((string) ($mobil['status'] ?? '')));
    return $status === 'tersedia';
}

function mobil_punya_rental_aktif($conn, $idMobil)
{
    $idMobil = (int) $idMobil;
    $cek = mysqli_query($conn, "
        SELECT id_transaksi
        FROM transaksi
        WHERE id_mobil='$idMobil'
          AND status IN ('terverifikasi','disewa')
          AND (selesai_admin_at IS NULL OR selesai_admin_at = '0000-00-00 00:00:00')
          AND LOWER(COALESCE(catatan_admin,'')) NOT LIKE '%batal%'
          AND NOW() >= tanggal_sewa
        LIMIT 1
    ");
    return $cek && mysqli_num_rows($cek) > 0;
}

function hitung_overtime_data($row)
{
    $status = status_transaksi_efektif($row);
    $aktif = in_array($status, ['terverifikasi', 'disewa'], true);
    $selesaiAt = !empty($row['selesai_admin_at']) ? strtotime($row['selesai_admin_at']) : 0;
    $batas = !empty($row['tanggal_kembali']) ? strtotime($row['tanggal_kembali']) : 0;
    $pembanding = $selesaiAt > 0 ? $selesaiAt : time();

    $jam = (int) ($row['overtime_jam'] ?? 0);
    $biaya = (float) ($row['overtime_biaya'] ?? 0);

    if ($aktif && $batas > 0 && $pembanding > $batas) {
        $selisihDetik = $pembanding - $batas;
        $jam = (int) ceil($selisihDetik / 3600);
        $biaya = $jam * tarif_overtime_per_jam();
    }

    return [
        'jam' => max(0, $jam),
        'biaya' => max(0, $biaya),
        'is_overdue' => $aktif && $batas > 0 && time() > $batas,
    ];
}

function total_tagihan_transaksi($row)
{
    $dasar = (float) ($row['total_harga'] ?? 0);
    $tersimpan = isset($row['total_akhir']) ? (float) $row['total_akhir'] : 0;
    if ($tersimpan > 0 && in_array(($row['status'] ?? ''), ['selesai'], true)) {
        return $tersimpan;
    }
    $overtime = hitung_overtime_data($row);
    return $dasar + $overtime['biaya'];
}

function status_transaksi_efektif($row)
{
    $status = trim((string) ($row['status'] ?? ''));
    if ($status !== '') {
        return $status;
    }

    $catatan = strtolower((string) ($row['catatan_admin'] ?? ''));
    if (!empty($row['selesai_admin_at'])) {
        return 'selesai';
    }
    if (strpos($catatan, 'batal') !== false) {
        return 'dibatalkan';
    }
    if (strpos($catatan, 'diverifikasi') !== false) {
        $mulai = !empty($row['tanggal_sewa']) ? strtotime($row['tanggal_sewa']) : 0;
        return ($mulai > 0 && time() >= $mulai) ? 'disewa' : 'terverifikasi';
    }
    if (!empty($row['bukti_pembayaran']) && !empty($row['sim_a'])) {
        return 'menunggu konfirmasi';
    }
    return 'menunggu pembayaran';
}

function normalisasi_status_transaksi($conn)
{
    if (!table_has_column($conn, 'transaksi', 'status')) {
        return;
    }

    @mysqli_query($conn, "UPDATE transaksi SET status=TRIM(status) WHERE status <> TRIM(status)");
    @mysqli_query($conn, "UPDATE transaksi SET status='dibatalkan' WHERE LOWER(TRIM(status)) IN ('batal','dibatalkan','dibatalkan admin','batal admin','cancel','cancelled') OR LOWER(TRIM(status)) LIKE '%batal%'");
    @mysqli_query($conn, "UPDATE transaksi SET status='selesai' WHERE LOWER(TRIM(status)) IN ('selesai','selesai admin','completed','complete') OR LOWER(TRIM(status)) LIKE '%selesai%'");
    @mysqli_query($conn, "UPDATE transaksi SET status='selesai' WHERE selesai_admin_at IS NOT NULL AND selesai_admin_at <> '0000-00-00 00:00:00'");
    @mysqli_query($conn, "UPDATE transaksi SET status='menunggu pembayaran' WHERE LOWER(TRIM(status)) IN ('menunggu pembayaran','pending pembayaran','pending payment')");
    @mysqli_query($conn, "UPDATE transaksi SET status='menunggu konfirmasi' WHERE LOWER(TRIM(status)) IN ('menunggu konfirmasi','menunggu konfirmasi admin','pending konfirmasi','pending verifikasi')");
    @mysqli_query($conn, "UPDATE transaksi SET status='terverifikasi' WHERE LOWER(TRIM(status)) IN ('terverifikasi','verified')");
    @mysqli_query($conn, "UPDATE transaksi SET status='disewa' WHERE LOWER(TRIM(status)) IN ('disewa','aktif rental','aktif')");
    @mysqli_query($conn, "UPDATE transaksi SET status='selesai' WHERE (status IS NULL OR TRIM(status)='') AND selesai_admin_at IS NOT NULL");
    @mysqli_query($conn, "UPDATE transaksi SET status='dibatalkan' WHERE (status IS NULL OR TRIM(status)='') AND LOWER(COALESCE(catatan_admin,'')) LIKE '%batal%'");
    @mysqli_query($conn, "UPDATE transaksi SET status='terverifikasi' WHERE (status IS NULL OR TRIM(status)='') AND LOWER(COALESCE(catatan_admin,'')) LIKE '%diverifikasi%'");
    @mysqli_query($conn, "UPDATE transaksi SET status='menunggu konfirmasi' WHERE (status IS NULL OR TRIM(status)='') AND COALESCE(bukti_pembayaran,'') <> '' AND COALESCE(sim_a,'') <> ''");
    @mysqli_query($conn, "UPDATE transaksi SET status='menunggu pembayaran' WHERE (status IS NULL OR TRIM(status)='')");
}

function sql_status_transaksi_mengunci_jadwal()
{
    return "('menunggu pembayaran','menunggu konfirmasi','terverifikasi','disewa')";
}

function sinkronisasi_status_transaksi($conn)
{
    @mysqli_query($conn, "UPDATE transaksi SET status='disewa' WHERE status='terverifikasi' AND NOW() >= tanggal_sewa AND (selesai_admin_at IS NULL OR selesai_admin_at = '0000-00-00 00:00:00')");

    $result = @mysqli_query($conn, "SELECT id_transaksi, status, tanggal_kembali, overtime_jam, overtime_biaya, selesai_admin_at FROM transaksi WHERE status IN ('terverifikasi','disewa')");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data = hitung_overtime_data($row);
            $id = (int) $row['id_transaksi'];
            $jam = (int) $data['jam'];
            $biaya = (float) $data['biaya'];
            @mysqli_query($conn, "UPDATE transaksi SET overtime_jam='$jam', overtime_biaya='$biaya' WHERE id_transaksi='$id'");
        }
    }
}

function sinkronisasi_status_mobil($conn)
{
    if (!table_has_column($conn, 'mobil', 'status')) {
        return;
    }

    @mysqli_query($conn, "UPDATE mobil SET status='tersedia' WHERE status <> 'sedang diservice'");
    @mysqli_query($conn, "
        UPDATE mobil m
        INNER JOIN (
            SELECT DISTINCT id_mobil
            FROM transaksi
            WHERE status IN ('terverifikasi','disewa')
              AND (selesai_admin_at IS NULL OR selesai_admin_at = '0000-00-00 00:00:00')
              AND LOWER(COALESCE(catatan_admin,'')) NOT LIKE '%batal%'
              AND NOW() >= tanggal_sewa
        ) t ON m.id_mobil = t.id_mobil
        SET m.status='disewa'
        WHERE m.status <> 'sedang diservice'
    ");
}

function bootstrap_app($conn)
{
    ensure_schema($conn);
    app_migrate_sensitive_data($conn);
    normalisasi_status_transaksi($conn);
    normalisasi_status_mobil($conn);
    sinkronisasi_status_transaksi($conn);
    sinkronisasi_status_mobil($conn);
}

function esc($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function format_rupiah($angka)
{
    return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}

function format_tanggal_id($tanggal)
{
    if (empty($tanggal)) {
        return '-';
    }
    return date('d-m-Y H:i', strtotime($tanggal));
}

function customer_booking_notification($row)
{
    $status = status_transaksi_efektif($row);
    if (!in_array($status, ['terverifikasi', 'disewa'], true)) {
        return '';
    }

    $mulai = !empty($row['tanggal_sewa']) ? strtotime($row['tanggal_sewa']) : 0;
    if ($mulai <= 0) {
        return '';
    }

    $now = time();
    $batasNotif = $mulai - (20 * 60);

    if ($now >= $batasNotif && $now < $mulai) {
        return 'Status booking sudah aktif.';
    }

    if ($now < $batasNotif) {
        return 'Keterlambatan pengembalian mobil akan dikenakan biaya overtime Rp 30.000 per jam nya.';
    }

    $overtime = hitung_overtime_data($row);
    if ($overtime['jam'] > 0) {
        return 'Booking sudah diverifikasi.';
    }

    return 'Mobil dapat diambil sesuai jadwal sewa.';
}

function transaksi_status_info($row)
{
    $status = status_transaksi_efektif($row);
    $mulai = isset($row['tanggal_sewa']) ? strtotime($row['tanggal_sewa']) : 0;
    $selesai = isset($row['tanggal_kembali']) ? strtotime($row['tanggal_kembali']) : 0;
    $sekarang = time();
    $overtime = hitung_overtime_data($row);

    if ($status === 'dibatalkan') {
        return ['label' => 'Dibatalkan Admin', 'class' => 'danger'];
    }
    if ($status === 'selesai') {
        return ['label' => 'Selesai Admin', 'class' => 'success'];
    }
    if ($status === 'menunggu pembayaran') {
        return ['label' => 'Menunggu Pembayaran', 'class' => 'warning'];
    }
    if ($status === 'menunggu konfirmasi') {
        return ['label' => 'Menunggu Konfirmasi Admin', 'class' => 'info'];
    }
    if ($status === 'terverifikasi' || $status === 'disewa') {
        if ($sekarang < $mulai) {
            return ['label' => 'Terverifikasi - Menunggu Jam Sewa', 'class' => 'secondary'];
        }
        if ($overtime['is_overdue']) {
            return ['label' => 'Overtime - Menunggu Selesai Admin', 'class' => 'danger'];
        }
        if ($sekarang >= $mulai && $sekarang < $selesai) {
            return ['label' => 'Sedang Disewa', 'class' => 'primary'];
        }
        return ['label' => 'Menunggu Konfirmasi Selesai Admin', 'class' => 'warning'];
    }
    return ['label' => ucfirst($status), 'class' => 'dark'];
}

function transaksi_is_active_now($row)
{
    $status = status_transaksi_efektif($row);
    if (!in_array($status, ['terverifikasi', 'disewa'])) {
        return false;
    }
    $mulai = isset($row['tanggal_sewa']) ? strtotime($row['tanggal_sewa']) : 0;
    $sekarang = time();
    return $sekarang >= $mulai;
}

function ada_bentrok_jadwal($conn, $idMobil, $mulai, $selesai, $excludeId = 0)
{
    $idMobil = (int) $idMobil;
    $excludeId = (int) $excludeId;
    $mulai = mysqli_real_escape_string($conn, $mulai);
    $selesai = mysqli_real_escape_string($conn, $selesai);

    $sql = "
        SELECT COUNT(*) AS total
        FROM transaksi
        WHERE id_mobil = '$idMobil'
          AND status IN ('menunggu pembayaran','menunggu konfirmasi','terverifikasi','disewa')
          AND (selesai_admin_at IS NULL OR selesai_admin_at = '0000-00-00 00:00:00')
          AND LOWER(COALESCE(catatan_admin,'')) NOT LIKE '%batal%'
          AND NOT ('$selesai' <= tanggal_sewa OR '$mulai' >= tanggal_kembali)
    ";
    if ($excludeId > 0) {
        $sql .= " AND id_transaksi != '$excludeId'";
    }
    $result = mysqli_query($conn, $sql);
    $data = $result ? mysqli_fetch_assoc($result) : ['total' => 0];
    return ((int) ($data['total'] ?? 0)) > 0;
}

function upload_gambar($fieldName, $targetDir)
{
    if (!isset($_FILES[$fieldName]) || !is_dir($targetDir)) {
        return '';
    }
    if ($_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return '';
    }
    $ext = strtolower(pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($ext, $allowed, true)) {
        return '';
    }
    $namaBaru = time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
    $tujuan = rtrim($targetDir, '/') . '/' . $namaBaru;
    if (move_uploaded_file($_FILES[$fieldName]['tmp_name'], $tujuan)) {
        return $namaBaru;
    }
    return '';
}

function upload_gambar_terenkripsi($fieldName, $category)
{
    if (!isset($_FILES[$fieldName])) {
        return '';
    }
    if (($_FILES[$fieldName]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return '';
    }

    $ext = strtolower(pathinfo($_FILES[$fieldName]['name'] ?? '', PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($ext, $allowed, true)) {
        return '';
    }

    return app_encrypt_uploaded_file($_FILES[$fieldName]['tmp_name'], $category, $_FILES[$fieldName]['name'] ?? ('file.' . $ext));
}

function get_relative_upload_path($folderName, $filename)
{
    if (empty($filename)) {
        return '';
    }
    return 'uploads/' . trim($folderName, '/') . '/' . ltrim($filename, '/');
}

function status_pill_class($class)
{
    $class = trim((string) $class);
    if (strpos($class, 'warning') !== false) return 'status-warning';
    if (strpos($class, 'info') !== false) return 'status-info';
    if (strpos($class, 'primary') !== false) return 'status-primary';
    if (strpos($class, 'success') !== false) return 'status-success';
    if (strpos($class, 'danger') !== false) return 'status-danger';
    if (strpos($class, 'secondary') !== false) return 'status-secondary';
    return 'status-dark';
}

function badge_label_html($row)
{
    $status = transaksi_status_info($row);
    $pill = status_pill_class($status['class']);
    return '<span class="status-pill ' . $pill . '">' . esc($status['label']) . '</span>';
}

function info_kontak_footer_html($prefix = '')
{
    $c = company_info();
    $waLabel = esc($c['wa']);
    $waLink = esc($c['wa_link']);
    $alamat = esc($c['alamat']);
    $mapsLink = esc($c['maps_link'] ?? ('https://www.google.com/maps/search/?api=1&query=' . rawurlencode($c['alamat'])));
    $nama = esc($c['nama']);
    return '<footer class="site-footer mt-4"><div class="container-fluid"><div class="row g-4 align-items-start"><div class="col-md-5"><div class="footer-title">Tentang Kami</div><div class="footer-brand">' . $nama . '</div><div class="footer-copy">Layanan rental mobil harian dengan proses booking mudah dan jadwal yang rapi.</div></div><div class="col-md-7"><div class="footer-title">Hubungi Kami</div><div class="footer-copy mb-2">Hubungi kami untuk mengetahui informasi lebih lanjut. Dapatkan layanan terbaik dengan menyewa mobil.</div><div class="footer-copy"><i class="bi bi-whatsapp me-2"></i><a href="' . $waLink . '" target="_blank" rel="noopener" class="footer-link">' . $waLabel . '</a></div><div class="footer-copy"><i class="bi bi-geo-alt me-2"></i><a href="' . $mapsLink . '" target="_blank" rel="noopener" class="footer-link">' . $alamat . '</a></div></div></div></div></footer><div style="background:#000000;color:#ffffff;text-align:center;padding:18px 12px;font-size:16px;font-weight:500;">Copyright © 2025 Brawijaya Rental Mobil</div>';
}
?>
