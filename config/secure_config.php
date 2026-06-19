<?php
// Ganti nilai APP_SECRET_KEY_BASE64 setelah deploy untuk keamanan yang lebih baik.
// Key ini harus tetap sama selama data terenkripsi lama masih ingin dibuka.
if (!defined('APP_SECRET_KEY_BASE64')) {
    define('APP_SECRET_KEY_BASE64', 'fAgnPEen4t5w+Oxt4Z4A5u80kx2M/K1J0vX7CngQkR4=');
}
?>
