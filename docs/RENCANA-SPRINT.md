# Rencana Perbaikan Bertahap — Model Waterfall

Setiap fase **selesai, diuji, dan disetujui** sebelum fase berikutnya dimulai (tidak tumpang tindih).
Status: ✅ selesai · 🔄 berjalan · ⏳ menunggu persetujuan · ⬜ belum dimulai

| Fase | Nama | Isi | Status |
|---|---|---|---|
| 1 | Analisis kebutuhan | Inventaris kekurangan, prioritas, kriteria selesai (dokumen ini) | ✅ |
| 2 | Sprint 1 — Keandalan & mandiri offline | OTP lewat antrean (queue), perintah cek kesiapan sistem, aset tanpa CDN | ✅ |
| 3 | Sprint 2 — Laporan profesional | Ekspor `.xlsx` asli & PDF dari server | ⏳ |
| 4 | Sprint 3 — Keamanan login lanjutan | Cloudflare Turnstile (fallback soal hitung), OTP opsional petugas | ⬜ |
| 5 | Sprint 4 — Real-time WebSocket | Laravel Reverb + fallback polling | ⬜ |
| 6 | Sprint 5 — Notifikasi WhatsApp | Gateway WA (mis. Fonnte) untuk panggilan antrean & OTP cadangan | ⬜ |
| 7 | Pengujian sistem (UAT) | Uji menyeluruh 7 peran, skenario lapangan, uji beban ringan | ⬜ |
| 8 | Deploy | VPS + HTTPS, Nginx, Supervisor (queue/reverb), cron, backup DB | ⬜ |
| 9 | Pemeliharaan | Monitoring log, rotasi kunci, pembaruan dependensi | ⬜ |

---

## Fase 1 — Analisis kebutuhan (✅)

### Kekurangan saat ini

| # | Kekurangan | Dampak | Prioritas | Ditangani di |
|---|---|---|---|---|
| K1 | Email OTP dikirim sinkron; bila SMTP lambat/gagal halaman ikut tertahan | UX daftar/login lambat | Tinggi | Sprint 1 |
| K2 | Tidak ada cara cepat mengecek konfigurasi (SMTP, kunci HMAC, password awal, email contoh) | Salah konfigurasi baru ketahuan saat dipakai | Tinggi | Sprint 1 |
| K3 | QR tiket, Swagger UI, dan font bergantung CDN | Tidak jalan tanpa internet | Sedang | Sprint 1 |
| K4 | Ekspor laporan hanya CSV & cetak browser | Dinas biasanya minta `.xlsx`/PDF rapi | Sedang | Sprint 2 |
| K5 | Verifikasi login hanya soal hitung; petugas tanpa OTP | Rentan bot / pencurian password staf | Sedang | Sprint 3 |
| K6 | Real-time memakai polling 4 detik | Beban server naik saat pengguna banyak | Rendah–Sedang | Sprint 4 |
| K7 | Panggilan antrean hanya di aplikasi/layar | Pasien yang tidak membuka aplikasi tidak tahu | Sedang | Sprint 5 |
| K8 | Belum ada panduan & konfigurasi produksi | Risiko salah deploy, tanpa HTTPS | Tinggi (sebelum go-live) | Fase 8 |

### Kriteria selesai umum (Definition of Done)
- Fitur berjalan di Laragon (PHP 8.4, MySQL 8.4) dan lolos `php artisan test`.
- Dicoba di browser untuk peran terkait; tidak ada error konsol.
- README & Swagger diperbarui bila ada perubahan perilaku/API.
- Commit + push ke `main`.

---

## Fase 2 — Sprint 1: Keandalan & mandiri offline (✅)

| Item | Kriteria selesai |
|---|---|
| S1.1 OTP via queue | Email OTP masuk antrean `database`, dicoba ulang 3× bila SMTP gagal; mode `OTP_PENGIRIMAN=langsung` tetap tersedia |
| S1.2 `php artisan puskesmas:cek` | Menampilkan status DB, SMTP (uji koneksi), kunci HMAC, password awal, worker antrean, email contoh tidak nyata, storage |
| S1.3 Aset offline | QR dibuat di server (SVG, bacon-qr-code), Swagger UI & font Plus Jakarta Sans disajikan dari `public/vendor` |

**Hasil verifikasi:** 9/9 test (264 assertion) lolos; beranda & Swagger memuat 0 sumber dari luar server; tiket unduhan menampilkan QR tanpa internet; `php artisan puskesmas:cek` berjalan di Laragon.

## Fase 3 — Sprint 2: Laporan profesional (⬜)
- S2.1 Ekspor `.xlsx` multi-sheet (Ringkasan, Per Poli, Per Dokter, Obat Keluar, Harian) dengan PhpSpreadsheet.
- S2.2 PDF laporan dari server (Dompdf) dengan kop surat & tanda tangan Kepala Puskesmas.
- Kriteria: file terbuka di Excel/LibreOffice, angka sama dengan halaman laporan.

## Fase 4 — Sprint 3: Keamanan login lanjutan (⬜)
- S3.1 Cloudflare Turnstile bila kunci diisi; otomatis kembali ke soal hitung bila offline/kosong.
- S3.2 OTP perangkat baru untuk petugas (opsional per peran, diatur di Pengaturan).
- S3.3 Kunci akun sementara setelah N kali gagal + notifikasi ke Super Admin.

## Fase 5 — Sprint 4: Real-time WebSocket (⬜)
- S4.1 Laravel Reverb + event `DataBerubah` per kanal (antrean, resep, konsultasi, pengaduan).
- S4.2 Kanal privat per peran; kanal publik hanya data aman (nomor & poli).
- S4.3 Fallback otomatis ke polling bila WebSocket tidak tersambung.

## Fase 6 — Sprint 5: Notifikasi WhatsApp (⬜)
- S5.1 Driver WA (Fonnte/WA Business API) + mode log untuk lokal.
- S5.2 Pesan saat nomor dipanggil, resep siap, dan OTP cadangan bila email gagal.
- S5.3 Persetujuan pasien (opt-in) & batas kirim.

## Fase 7 — Pengujian sistem / UAT (⬜)
- Skenario per peran (7 peran), alur 10 langkah, data ganda, OTP, QR, kiosk HMAC.
- Uji beban ringan (mis. 50 pengguna serentak di layar antrean).
- Berita acara uji.

## Fase 8 — Deploy (⬜)
- VPS Ubuntu, Nginx + PHP-FPM 8.4, MySQL 8, HTTPS (Let's Encrypt).
- Supervisor: `queue:work` (+ `reverb:start` bila Sprint 4), cron `schedule:run`.
- Backup database harian, `.env` produksi, `APP_DEBUG=false`.

## Fase 9 — Pemeliharaan (⬜)
- Pantau `storage/logs`, rotasi `HMAC_SECRET`/`KIOSK_HMAC_SECRET` berkala, `composer audit` bulanan.
