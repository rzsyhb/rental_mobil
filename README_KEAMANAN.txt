Perubahan keamanan yang diterapkan:
1. Password admin dan customer sekarang di-hash dengan Argon2id.
2. Nomor HP customer sekarang dienkripsi sebelum disimpan ke database.
3. Foto KTP customer dan SIM A transaksi sekarang disimpan sebagai file terenkripsi di folder storage/private.
4. Akses file sensitif dilakukan melalui secure_file.php, bukan URL file langsung.
5. Saat aplikasi dijalankan, data lama plaintext akan dimigrasikan otomatis oleh bootstrap_app().

Catatan penting:
- Segera ganti APP_SECRET_KEY_BASE64 di config/secure_config.php setelah deploy.
- Jangan ubah key jika masih ingin membuka data terenkripsi lama.
- Bukti pembayaran tetap disimpan seperti semula agar fitur lama tetap kompatibel.
