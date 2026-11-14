# Panduan Deploy — Sistem Informasi Puskesmas

Dua pilihan:

- **A. VPS (disarankan):** semua fitur aktif, termasuk pembaruan langsung (WebSocket) dan antrean email/WhatsApp di latar belakang.
- **B. Hosting biasa (cPanel):** lebih murah. Pembaruan otomatis tetap jalan dengan cara berkala (tiap beberapa detik), dan antrean dijalankan lewat cron.

Semua berkas konfigurasi ada di folder [`deploy/`](../deploy).

---

## A. VPS Ubuntu 24.04 (2 vCPU / 2–4 GB RAM)

### 1. Paket server

```bash
sudo apt update && sudo apt install -y nginx mysql-server supervisor git unzip certbot python3-certbot-nginx \
  php8.4-fpm php8.4-cli php8.4-mysql php8.4-mbstring php8.4-xml php8.4-zip php8.4-gd php8.4-curl php8.4-bcmath php8.4-intl php8.4-opcache
```

```bash
curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer
```

> Bila `php8.4` belum ada di repositori Ubuntu Anda, tambahkan dulu: `sudo add-apt-repository ppa:ondrej/php`.

### 2. Basis data

```bash
sudo mysql
```

```sql
CREATE DATABASE puskesmas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'puskesmas_app'@'localhost' IDENTIFIED BY 'GANTI-SANDI-KUAT';
GRANT ALL PRIVILEGES ON puskesmas.* TO 'puskesmas_app'@'localhost';
FLUSH PRIVILEGES;
```

### 3. Kode aplikasi

```bash
sudo mkdir -p /var/www/puskesmas && sudo chown $USER:www-data /var/www/puskesmas
git clone https://github.com/not162/puskesmas-RPL.git /var/www/puskesmas
cd /var/www/puskesmas
cp deploy/env.production.example .env
nano .env
```

Isi minimal di `.env`: `APP_URL`, `DB_PASSWORD`, tujuh `SEED_PASSWORD_*` (berbeda & kuat), `HMAC_SECRET`, `KIOSK_HMAC_SECRET`, `REVERB_APP_ID/KEY/SECRET`, `REVERB_HOST` (= domain), SMTP Gmail. Buat nilai acak dengan:

```bash
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Instalasi pertama:

```bash
bash deploy/deploy.sh --pertama
```

### 4. PHP, Nginx, HTTPS

```bash
sudo cp deploy/php/99-puskesmas.ini /etc/php/8.4/fpm/conf.d/ && sudo systemctl restart php8.4-fpm
sudo cp deploy/nginx/puskesmas.conf /etc/nginx/sites-available/puskesmas
sudo sed -i 's/puskesmas.example.go.id/DOMAIN-ANDA/g' /etc/nginx/sites-available/puskesmas
sudo ln -s /etc/nginx/sites-available/puskesmas /etc/nginx/sites-enabled/
```

Buat sertifikat HTTPS (Let's Encrypt, gratis). Domain harus sudah mengarah ke IP server:

```bash
sudo certbot certonly --nginx -d DOMAIN-ANDA
sudo nginx -t && sudo systemctl reload nginx
```

Sesuaikan `pm.max_children` di `/etc/php/8.4/fpm/pool.d/www.conf` dengan RAM (lihat [`deploy/php/fpm-pool.conf`](../deploy/php/fpm-pool.conf)).

### 5. Proses latar belakang & jadwal

```bash
sudo cp deploy/supervisor/*.conf /etc/supervisor/conf.d/
sudo supervisorctl reread && sudo supervisorctl update && sudo supervisorctl status
sudo cp deploy/cron/puskesmas /etc/cron.d/puskesmas
```

- `puskesmas-antrean`: mengirim email OTP & WhatsApp.
- `puskesmas-reverb`: pembaruan langsung (WebSocket) di `127.0.0.1:6001`, diteruskan Nginx lewat HTTPS.
- Cron menjalankan penjadwal: cadangan basis data pukul 02.00 (disimpan 14 hari), pembersihan kode OTP, dll.

### 6. Pembaruan berikutnya

```bash
cd /var/www/puskesmas && bash deploy/deploy.sh
```

Skrip ini: mode perawatan → ambil kode terbaru → pustaka tanpa paket pengembangan → **cadangan basis data** → migrasi → cache produksi → izin folder → muat ulang PHP-FPM, antrean, Reverb → `puskesmas:cek`.

> ⚠️ `deploy.sh` menyamakan folder dengan GitHub (`git reset --hard origin/main`). Jangan mengedit kode langsung di server; `.env`, `storage/` (termasuk foto KTP dan cadangan) tidak terpengaruh.

---

## B. Hosting biasa (cPanel)

1. Buat basis data & pengguna MySQL lewat menu **MySQL Databases**.
2. Unggah kode ke `/home/USER/puskesmas` (Git Version Control di cPanel, atau unggah zip). Jalankan `composer install --no-dev --optimize-autoloader` lewat **Terminal** bila tersedia; bila tidak, unggah folder `vendor/` dari komputer Anda.
3. Arahkan **Document Root** domain ke `/home/USER/puskesmas/public` (menu *Domains*). Bila tidak bisa, salin isi `public/` ke `public_html` dan pakai [`deploy/cpanel/public_html.htaccess`](../deploy/cpanel/public_html.htaccess) sebagai `.htaccess`, lalu sesuaikan dua baris `__DIR__.'/../` di `public_html/index.php` agar menunjuk ke `/home/USER/puskesmas/`.
4. `.env`: salin dari `deploy/env.production.example`, lalu ubah:
   - `BROADCAST_CONNECTION=log` (tanpa WebSocket; halaman otomatis memakai pembaruan berkala)
   - `ANTREAN_LEWAT_CRON=true` dan `OTP_PENGIRIMAN=antrean`, `WA_PENGIRIMAN=antrean`
5. Lewat Terminal: `php artisan key:generate`, `php artisan migrate --force`, `php artisan db:seed --force`, `php artisan optimize`.
6. Menu **Cron Jobs**, setiap menit:
   ```
   cd /home/USER/puskesmas && php artisan schedule:run >> /dev/null 2>&1
   ```
7. Aktifkan **SSL/TLS** (AutoSSL) di cPanel.

---

## Daftar periksa sebelum digunakan masyarakat (go-live)

| | Periksa | Cara |
|---|---|---|
| ☐ | `APP_ENV=production`, `APP_DEBUG=false` | `deploy.sh` menolak berjalan bila debug aktif |
| ☐ | HTTPS aktif, HTTP dialihkan ke HTTPS | buka `http://DOMAIN` |
| ☐ | Semua status hijau | `php artisan puskesmas:cek` |
| ☐ | Email OTP sampai ke Gmail | `php artisan puskesmas:cek --kirim-uji=alamat@gmail.com` |
| ☐ | WhatsApp terkirim (bila dipakai) | Super Admin → Log WhatsApp → Kirim uji |
| ☐ | Turnstile aktif (disarankan) | isi `TURNSTILE_*`, buka `/login` |
| ☐ | Cadangan berjalan & bisa dipulihkan | `php artisan puskesmas:cadangkan`, uji pulihkan ke basis data cadangan |
| ☐ | Password awal ketujuh peran sudah diganti | `puskesmas:cek` → "Password awal" |
| ☐ | Identitas Puskesmas, slogan, Kepala Puskesmas & NIP, nomor Permenkes 2025 | Super Admin → Pengaturan |
| ☐ | Uji beban di server sungguhan | `php tests/beban.php https://DOMAIN 50 2` dari komputer lain |
| ☐ | Berita acara uji ditandatangani | [BERITA-ACARA-UJI.md](BERITA-ACARA-UJI.md) |

## Memulihkan cadangan

```bash
ls /var/www/puskesmas/storage/app/private/cadangan/
gunzip -c storage/app/private/cadangan/puskesmas-YYYYMMDD-HHMMSS.sql.gz | mysql -u puskesmas_app -p puskesmas
```

> Simpan salinan cadangan di tempat lain juga (mis. unduh mingguan atau sinkron ke penyimpanan awan), agar data aman bila server rusak.

## Hasil uji susunan produksi (lokal)

Konfigurasi Nginx di folder ini diuji dengan Nginx 1.29 (`nginx -t` lulus). Susunan mirip produksi (Nginx + 6 proses PHP + OPcache + `php artisan optimize`), 50 pengguna serentak, 400 permintaan: **0 gagal, 43,7 permintaan/detik, p50 0,55–1,0 detik**. Sebagai pembanding, server pengembangan hanya 2,4 permintaan/detik dengan p50 ±10 detik. Server Linux dengan PHP-FPM umumnya lebih cepat lagi daripada pengujian di Windows ini.
