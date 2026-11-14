# Puskesmas Sehat — Sistem Informasi Pelayanan Puskesmas

Laravel 13 · PHP 8.4 · MySQL 8 (Laragon) · OpenAPI/Swagger · Tema gelap & terang

Dibangun dari **ERD_Puskesmas_Chen.xlsx** (Akun, Peran, Izin, Log_login, Pasien, Dokter, Spesialisasi, Apoteker, Admin, Konsultasi, Resep, Detail_resep, Obat, Penyerahan_obat). Navigasi, kartu "Apa yang ingin Anda lakukan?", dan toast alert mengikuti gaya PRISMA. Pemisahan layout publik real-time tanpa data pribadi mengikuti pola Zavora.

## Menjalankan di Laragon

1. Taruh folder ini di `C:\laragon\www\puskesmas` (atau biarkan di sini lalu jalankan `php artisan serve`).
2. Laragon → **Start All** (MySQL harus jalan).
3. Buat database dan isi data awal:

```bash
mysql -uroot -e "CREATE DATABASE IF NOT EXISTS puskesmas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
```

```bash
php artisan migrate:fresh --seed
```

4. Buka `http://puskesmas.test` (virtual host Laragon) atau:

```bash
php artisan serve
```

lalu `http://localhost:8000`.

Database `puskesmas` langsung terlihat di HeidiSQL/phpMyAdmin Laragon. Pengaturan koneksi ada di `.env` (`DB_DATABASE=puskesmas`, user `root`, tanpa sandi).

## Login & 7 peran

Tidak ada akun demo di halaman login. Semua peran masuk dari **gateway** `/login` (username **atau** email, password dengan tombol tampil/sembunyi, *Ingat saya*, *Lupa password?*, dan verifikasi soal hitung). Versi kartu ringkas untuk alur pasien ada di `/pasien/masuk` (endpoint sama). **Registrasi hanya untuk pasien** (`/register`); akun petugas dibuat Super Admin.

| Username | Peran |
|---|---|
| `superadmin` | Super Admin (pihak Puskesmas) |
| `admin` | Admin Puskesmas (operasional) |
| `dr.umum`, `dr.umum2` | Dokter Umum |
| `dr.kandungan` | Dokter Kandungan |
| `apoteker` | Apoteker / Farmasi |
| `loket` | Petugas Loket |
| `pasien` | Pasien |

Kata sandi awal **berbeda untuk tiap peran** dan dibaca seeder dari `.env` (`SEED_PASSWORD_SUPER_ADMIN`, `…_ADMIN`, `…_DOKTER_UMUM`, `…_DOKTER_KANDUNGAN`, `…_APOTEKER`, `…_LOKET`, `…_PASIEN`). Ganti setelah login pertama lewat **Profil**. Jika `.env` baru disalin dari `.env.example`, isi dulu nilai-nilai tersebut (min. 8 karakter) sebelum `migrate --seed`.

**Pendaftaran tanpa data ganda:** username, email, NIK, nama lengkap, atau Nomor KK yang sudah terdaftar langsung diarahkan ke halaman masuk dengan pesan dan tautan *Lupa password* (peringatan juga muncul saat mengetik). Cek nama dan No. KK bisa dimatikan di **Pengaturan** (mis. agar anggota satu keluarga bisa mendaftar sendiri-sendiri). Pasien yang dulu didaftarkan loket tanpa akun tetap bisa menautkan akun lewat NIK yang sama.

## Kode OTP lewat email (SMTP)

Kode 6 digit dikirim ke email pengguna (mis. `@gmail.com`) untuk:

1. **Registrasi** — akun baru dibuat setelah kode benar (email terverifikasi).
2. **Lupa password** — kode + password baru di `/lupa-password`.
3. **Login pasien dari perangkat baru** — setelah lolos, perangkat bisa dipercaya 30 hari.

Kode berlaku 10 menit, maksimal 5 kali salah, kirim ulang tiap 60 detik, dan disimpan sebagai HMAC (bukan teks asli).

Agar email benar-benar terkirim ke Gmail, isi di `.env`:

```
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=alamat-gmail-pengirim@gmail.com
MAIL_PASSWORD=sandi-aplikasi-16-karakter
MAIL_FROM_ADDRESS=alamat-gmail-pengirim@gmail.com
```

Sandi aplikasi dibuat di akun Google → Keamanan → Verifikasi 2 Langkah → Sandi aplikasi. Lalu jalankan `php artisan config:clear`. Selama `MAIL_MAILER=log`, kode OTP ditulis ke `storage/logs/laravel.log`.

## Navigasi per peran (sidebar desktop, bottom-nav ponsel)

| Peran | Menu |
|---|---|
| Pasien | Beranda · Daftar Berobat · Antrean Saya · Riwayat · Resep & Obat · Notifikasi · Pengaduan · Profil |
| Dokter | Antrean Poli · Pemeriksaan · Resep · Riwayat Pasien · Jadwal Saya · Notifikasi · Konsultasi Online |
| Apoteker | Resep Masuk · Proses & Serahkan · Obat & Stok · Riwayat Penyerahan · Notifikasi |
| Loket | Cari Pasien · Pasien Baru · Pendaftaran · Antrean Hari Ini · Check-in QR · Pengaduan · Notifikasi |
| Super Admin | Dasbor · Akun Staf · Peran & Izin · Jadwal Praktik · Pengumuman · Template Notifikasi · Laporan · Audit · Pengaturan |
| Admin | Dasbor · Jadwal Praktik · Pengumuman · Pengaduan · Kirim Alert · Laporan · Notifikasi |

Izin tiap peran tersimpan di `peran_izin` dan bisa diubah di **Peran & Izin**. Dokter hanya ada Dokter Umum dan Dokter Kandungan, dan hanya melihat pasien poli sendiri.

## Real-time antar peran & tombol alert

- Halaman memantau `/realtime/versi` tiap 4 detik. Saat peran lain mengubah data (pasien daftar/ambil antrean, loket check-in, dokter menerbitkan resep, apoteker menyerahkan obat, pengaduan baru), bagian `data-live` diperbarui **tanpa reload** dan berkedip singkat.
- Lonceng notifikasi + toast diperbarui tiap 15 detik.
- Tombol **alert** (ikon lonceng bergaris) di bilah atas mengaktifkan bunyi + pop-up notifikasi perangkat (izin browser).
- Admin/Super Admin dapat **Kirim Alert** ke satu atau beberapa peran sekaligus (menu Template Notifikasi / Kirim Alert).

## HMAC

1. **QR tiket antrean**: isi QR `PKM1.<kode>.<tanda tangan>`, tanda tangan = HMAC-SHA256(`HMAC_SECRET`, kode|nomor|tanggal|pasien). Loket memindai di **Check-in QR** (pemindai USB atau kamera); tiket yang diubah/dipalsukan ditolak dan dicatat di Audit.
2. **API kiosk** `/api/v1/kiosk/antrian` dan `/api/v1/kiosk/checkin`: wajib header `X-Kiosk-Id`, `X-Timestamp`, `X-Signature` = HMAC-SHA256(`KIOSK_HMAC_SECRET`, `METHOD
PATH
TIMESTAMP
SHA256(body)`), toleransi 300 detik, tiap tanda tangan sekali pakai (anti-replay). Contoh header:

```bash
php artisan hmac:contoh GET api/v1/kiosk/antrian
```

Kunci ada di `.env` (`HMAC_SECRET`, `KIOSK_ID`, `KIOSK_HMAC_SECRET`).

## Hak akses per peran (RBAC: tabel `peran`, `izin`, `peran_izin`)

| Fitur | Super Admin | Admin | Dokter Umum / Kandungan | Apoteker | Loket | Pasien |
|---|:-:|:-:|:-:|:-:|:-:|:-:|
| Akun staf, peran & izin, audit, pengaturan, template notifikasi | ✔ | | | | | |
| Jadwal praktik, pengumuman, laporan bulanan (export CSV/cetak PDF), kirim alert | ✔ | ✔ | | | | |
| Tanggapi pengaduan | ✔ | ✔ | | | ✔ | |
| Check-in (QR/manual), panggil, batal antrean; daftar pasien datang langsung | ✔ | | | | ✔ | |
| Antrean & konsultasi **poli sendiri**, periksa, terbitkan resep, riwayat pasien | | | ✔ | | | |
| Proses resep, serahkan obat, CRUD stok obat, riwayat penyerahan | ✔* | | | ✔ | | |
| Daftar berobat online, tiket QR, konsultasi online, resep, pengaduan | | | | | | ✔ |

\* Pencatatan penyerahan obat wajib dari akun apoteker.

## Alur layanan: persyaratan & notifikasi tiap langkah

Didefinisikan di `config/alur.php`, ditampilkan di `/alur-layanan` dan sebagai stepper di dashboard pasien. Setiap langkah mengirim notifikasi (tabel `notifications`, lonceng + toast, diperbarui otomatis tiap 15 detik).

| # | Langkah | Persyaratan | Pemicu notifikasi |
|---|---|---|---|
| 1 | Registrasi | NIK 16 digit, nama, tgl lahir, JK, alamat, username unik, HP/WA; email, BPJS, foto KTP opsional; password ≥ 8; setuju Kebijakan Privasi | Akun dibuat + No. RM; loket diberi tahu |
| 2 | Login | Akun aktif | Login (sekali per hari) |
| 3 | Konsultasi online (opsional) | Pilih poli, keluhan ≥ 10 karakter | Konsultasi terkirim; dokter poli diberi tahu |
| 4 | Tanggapan dokter | Dokter poli yang sesuai | Dokter menanggapi |
| 5 | Ambil antrian online | Poli + tanggal (hari ini s.d. +7 hari), 1 antrian aktif/poli/hari | Nomor antrian + tautan unduh tiket |
| 6 | Check-in di loket | QR tiket (diverifikasi HMAC) + KTP | Check-in berhasil; dokter poli diberi tahu |
| 7 | Dipanggil | Sudah check-in | Nomor dipanggil (layar publik berkedip + suara) |
| 8 | Pemeriksaan & resep | Diagnosis wajib | Resep diterbitkan → apoteker diberi tahu |
| 9 | Resep diproses | Apoteker | Obat disiapkan |
| 10 | Obat diserahkan | Stok cukup (berkurang otomatis) | Obat diserahkan |

## Halaman publik (tanpa login, tanpa data pribadi)

- `/` beranda: kartu niat, antrian per poli real-time, dokter & jadwal, pengumuman, bantuan
- `/layar-antrean` layar TV ruang tunggu: auto-refresh 5 detik, suara panggilan, mode layar penuh
- `/jadwal` (dari tabel `jadwal_praktik`), `/alur-layanan`, `/informasi`, `/kebijakan-privasi`

Endpoint publik (`/live/antrian`, `/api/v1/public/*`) hanya mengembalikan nomor antrian, nama poli, dan nama/jadwal dokter. Tidak ada ID dokter, No. STR, nama/NIK/No. RM pasien. URL antrian, konsultasi, dan resep memakai kode ULID, bukan ID database.

## API & Swagger

- Swagger UI: `/api/docs` · Spesifikasi: `public/openapi.yaml`
- Login: `POST /api/v1/auth/login` dengan `login` (username/email) + `password` → Bearer token (Laravel Sanctum)
- Kiosk ber-HMAC: `GET /api/v1/kiosk/antrian`, `POST /api/v1/kiosk/checkin`
- CRUD: obat, pengumuman, pasien (GET/POST/PUT/DELETE); antrian, konsultasi, resep (GET/POST/PUT/PATCH/DELETE sesuai peran); laporan bulanan (GET)

## Laporan bulanan

**Laporan Bulanan** (Super Admin & Admin): pilih bulan, lalu **Excel (.xlsx)** — 5 sheet dengan rumus total dan grafik harian — atau **PDF** dari server dengan kop surat, nomor halaman, dan tanda tangan Kepala Puskesmas (isi nama, NIP, dan kota di menu Pengaturan). CSV dan cetak browser tetap tersedia. Isinya ringkasan, kunjungan per poli (online/loket), layanan per dokter, obat keluar & sisa stok, dan grafik kunjungan harian.

## Tes

Tes memakai database `puskesmas_test` (buat sekali):

```bash
mysql -uroot -e "CREATE DATABASE IF NOT EXISTS puskesmas_test"
```

```bash
php artisan test
```

Mencakup login 7 peran (password berbeda, captcha), lupa password, registrasi pasien + upload KTP, alur 10 langkah beserta notifikasi, QR HMAC asli vs palsu, API kiosk (tanda tangan salah/kedaluwarsa/replay), real-time, navigasi & pembatasan tiap peran, pengaduan, audit, dan CRUD API.

## Cek kesiapan & antrean email

```bash
php artisan puskesmas:cek
```

Menampilkan status database, SMTP (uji login), kunci HMAC, akun yang masih memakai password awal, email contoh yang tidak bisa menerima OTP, antrean, penyimpanan, dan identitas Puskesmas. Tambahkan `--kirim-uji=alamat@gmail.com` untuk mengirim email uji.

`OTP_PENGIRIMAN=antrean` (disarankan untuk produksi) membuat halaman tidak menunggu SMTP; jalankan worker:

```bash
php artisan queue:work --tries=3
```

Di Laragon tanpa worker, pakai `OTP_PENGIRIMAN=langsung`. Semua aset (font, Swagger UI, QR) disajikan dari server sendiri, tanpa CDN.

Rencana perbaikan bertahap ada di [docs/RENCANA-SPRINT.md](docs/RENCANA-SPRINT.md).

## Hak cipta

🇮🇩 Dibuat di Indonesia · © 2026 Seluruh hak cipta dilindungi · Grup NussCorp Technologies Indonesia

## Struktur penting

```
app/Models/                 Model sesuai ERD (+ Antrian, PetugasLoket, Pengumuman)
app/Http/Controllers/       Web: Pasien/, Loket/, Dokter/, Farmasi/, Admin/  ·  Api/
app/Http/Middleware/        CekPeran, CekIzin (RBAC), AkunAktif
app/Notifications/          LangkahLayanan (pasien), InfoStaf (petugas)
app/Support/                AlurPasien, AntrianLive (data publik aman), LaporanBulanan, Notifier
config/alur.php             10 langkah: persyaratan + teks notifikasi
database/migrations/        Skema ERD (tanda [EXT] = tambahan di luar ERD)
public/css/app.css          Tema gelap/terang
public/js/app.js            Toggle tema, toast, modal konfirmasi, polling notifikasi
public/openapi.yaml         Spesifikasi OpenAPI 3
```
