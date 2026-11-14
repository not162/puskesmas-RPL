# SOP Pemeliharaan — Sistem Informasi Puskesmas

Untuk penanggung jawab TI Puskesmas. Sebagian besar tugas sudah **berjalan otomatis**; petugas cukup menanggapi peringatan dan menjalankan pemeriksaan berkala di bawah ini.

## 1. Yang berjalan otomatis (lewat cron penjadwal)

| Tugas | Jadwal | Bila bermasalah |
|---|---|---|
| Pantau kesehatan sistem (`puskesmas:pantau --kirim`) | setiap jam | Super Admin mendapat notifikasi (maks. 1x per masalah per hari) |
| Cadangan basis data (`puskesmas:cadangkan`), disimpan 14 hari | setiap hari 02.00 | tercatat di Audit sebagai `cadangan_gagal` dan muncul di pemantauan |
| Periksa celah keamanan pustaka (`puskesmas:audit-dependensi`) | setiap Senin 03.00 | muncul sebagai "Masalah" di pemantauan |
| Hapus kode OTP kedaluwarsa, token API & tugas gagal lama, notifikasi terbaca > 180 hari | harian/mingguan | — |

Rekam medis dan catatan audit **tidak pernah dihapus otomatis** (Permenkes 24/2022, ISO/IEC 27001:2022 kontrol 8.15).

Lihat semua: **Super Admin → Lainnya → Kesehatan Sistem**, atau di server:

```bash
php artisan puskesmas:pantau
```

## 2. Pemeriksaan rutin oleh petugas

### Harian (±5 menit)
- Buka **Kesehatan Sistem**; semua harus "Baik". Tindak lanjuti kolom *Yang perlu dilakukan*.
- Cek **Pengaduan** baru, tanggapi paling lambat 3 x 24 jam (janji di Kebijakan Privasi).

### Mingguan
- **Log Login**: cari percobaan masuk gagal yang tidak wajar.
- **Log WhatsApp**: pastikan tidak banyak status *gagal* (cek saldo/token Fonnte).
- **Unduh 1 cadangan terbaru** ke penyimpanan terpisah (laptop terenkripsi / penyimpanan awan resmi Puskesmas).

### Bulanan
- **Uji pulihkan cadangan** ke basis data uji (lihat bagian 4). Cadangan yang tidak pernah diuji tidak bisa diandalkan.
- **Audit akun**: nonaktifkan akun petugas yang sudah pindah tugas atau berhenti (Akun Staf → Nonaktifkan).
- **Tinjau Peran & Izin**: pastikan tidak ada izin berlebih.
- Pembaruan pustaka keamanan, bila pemantauan menunjukkan celah:
  ```bash
  composer update NAMA/PAKET --with-dependencies
  php artisan test
  bash deploy/deploy.sh
  ```
  Jalankan di komputer pengembang dulu, lalu commit ke GitHub dan deploy. Jangan mengubah kode langsung di server.

### Tiap 6 bulan
- **Rotasi kunci tanda tangan QR tiket**:
  ```bash
  php artisan puskesmas:rotasi-kunci --hmac
  bash deploy/deploy.sh
  ```
  Tiket lama tetap berlaku selama transisi. **Setelah 8 hari**, hapus baris `HMAC_SECRET_LAMA=` di `.env` lalu jalankan `php artisan optimize`.
- **Rotasi kunci kiosk** (bila memakai kiosk): `php artisan puskesmas:rotasi-kunci --kiosk`, lalu pasang kunci baru yang ditampilkan di perangkat kiosk.
- **Ganti sandi** akun Super Admin dan sandi basis data.

### Tahunan
- Perpanjang domain. Sertifikat HTTPS Let's Encrypt diperpanjang otomatis oleh certbot; periksa `sudo certbot renew --dry-run`.
- Tinjau ulang Kebijakan Privasi bila ada peraturan baru (isi nomor Permenkes terbaru di Pengaturan).
- Uji beban ulang: `php tests/beban.php https://DOMAIN 50 2`.

## 3. Bila terjadi insiden

| Kejadian | Langkah |
|---|---|
| Situs tidak bisa dibuka | `sudo systemctl status nginx php8.4-fpm mysql` → jalankan ulang layanan yang mati → `php artisan puskesmas:cek` |
| Pembaruan langsung tidak jalan | `sudo supervisorctl status`, restart `puskesmas-reverb`. Aplikasi tetap jalan dengan pembaruan berkala. |
| Email OTP tidak sampai | `php artisan puskesmas:cek --kirim-uji=alamat@gmail.com`; cek sandi aplikasi Gmail; `php artisan queue:failed` lalu `queue:retry all` |
| Akun petugas terkunci | Super Admin → Akun Staf → **Buka kunci**, atau petugas memakai Lupa password |
| Dugaan kebocoran data | 1) Nonaktifkan akun yang dicurigai, cabut token (Akun Staf → Nonaktifkan). 2) Simpan bukti: menu Audit, Log Login, `storage/logs`. 3) Rotasi kunci (`--hmac --kiosk`) & ganti sandi basis data. 4) **Beri tahu pasien terdampak dan lembaga berwenang paling lambat 3 x 24 jam** (UU PDP Pasal 46). 5) Catat kronologi & perbaikan. |
| Data terhapus/rusak | Mode perawatan `php artisan down`, pulihkan cadangan terakhir (bagian 4), `php artisan up`. Catat di laporan insiden. |

## 4. Memulihkan cadangan

```bash
ls -lt /var/www/puskesmas/storage/app/private/cadangan/ | head
```

Uji di basis data terpisah dulu (bulanan):

```bash
mysql -u root -p -e "CREATE DATABASE puskesmas_uji"
gunzip -c storage/app/private/cadangan/NAMA-BERKAS.sql.gz | mysql -u root -p puskesmas_uji
mysql -u root -p puskesmas_uji -e "select count(*) from akun; select count(*) from pasien;"
mysql -u root -p -e "DROP DATABASE puskesmas_uji"
```

Pemulihan sungguhan (menimpa data saat ini):

```bash
php artisan down
gunzip -c storage/app/private/cadangan/NAMA-BERKAS.sql.gz | mysql -u puskesmas_app -p puskesmas
php artisan up
```

## 5. Catatan perubahan

Setiap perubahan sistem (pembaruan, rotasi kunci, perubahan izin) dicatat otomatis di menu **Audit**. Untuk perubahan besar, catat juga di riwayat commit GitHub dengan pesan yang jelas.

---

Dibuat di Indonesia · © 2026 Seluruh hak cipta dilindungi · Grup NussCorp Technologies Indonesia
