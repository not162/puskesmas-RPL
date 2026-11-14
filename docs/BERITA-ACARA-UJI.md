# Berita Acara Pengujian Sistem (UAT) — Fase 7

**Aplikasi:** Sistem Informasi Pelayanan Puskesmas
**Lingkungan uji:** Laragon (Windows 10), PHP 8.4.12, MySQL 8.4.3, Laravel 13.42, server pengembangan `php artisan serve`
**Tanggal:** 15 November 2026
**Hasil akhir:** ✅ **Lulus dengan catatan** (lihat bagian 5)

---

## 1. Ruang lingkup

| Area | Cara uji |
|---|---|
| 7 peran (Super Admin, Admin, Dokter Umum, Dokter Kandungan, Apoteker, Loket, Pasien) | Uji otomatis + penjelajahan semua tautan yang tampil di tiap peran |
| Alur 10 langkah pasien + notifikasi | Uji otomatis ujung ke ujung |
| Keamanan (OTP, kunci akun, QR & API ber-tanda tangan, kanal privat) | Uji otomatis termasuk percobaan pemalsuan |
| Tampilan desktop & ponsel (375×812), tema gelap/terang | Browser, diperiksa manual + skrip |
| Menu hamburger publik & menu samping petugas | Audit aksesibilitas + uji perilaku |
| Real-time & cadangannya | Browser dengan server WebSocket hidup lalu dimatikan |
| Beban ringan 50 pengguna serentak | Skrip `tests/beban.php` |

## 2. Skenario uji per peran

| ID | Peran | Skenario | Hasil |
|---|---|---|---|
| P-01 | Pasien | Daftar akun baru → kode OTP email → masuk dasbor | ✅ |
| P-02 | Pasien | Daftar dengan NIK/username/email/nama/No. KK yang sudah ada → diarahkan ke halaman masuk | ✅ |
| P-03 | Pasien | Masuk dari perangkat baru → wajib OTP; perangkat dipercaya 30 hari | ✅ |
| P-04 | Pasien | Konsultasi online → ditanggapi dokter poli yang sesuai | ✅ |
| P-05 | Pasien | Ambil antrean, unduh tiket QR (tampil tanpa internet), batalkan antrean | ✅ |
| P-06 | Pasien | Lihat resep sampai obat diserahkan, menerima notifikasi tiap langkah | ✅ |
| P-07 | Pasien | Kirim pengaduan → ditanggapi loket → notifikasi | ✅ |
| P-08 | Pasien | Lupa password → OTP → password baru (juga membuka kunci akun) | ✅ |
| P-09 | Pasien | Setuju WhatsApp → pesan antrean & panggilan; tidak setuju → tidak ada pesan | ✅ |
| L-01 | Loket | Check-in dengan QR asli diterima; QR yang diubah ditolak & tercatat di audit | ✅ |
| L-02 | Loket | Daftar pasien datang langsung + antrean di tempat | ✅ |
| L-03 | Loket | Tabel antrean berubah sendiri saat pasien mengambil antrean dari luar | ✅ |
| D-01 | Dokter Umum | Tidak bisa membuka konsultasi/antrean Poli Kandungan (403) | ✅ |
| D-02 | Dokter Kandungan | Panggil → periksa → diagnosis & resep → diteruskan ke farmasi | ✅ |
| D-03 | Dokter | Riwayat pasien hanya untuk pasien poli sendiri; jadwal saya | ✅ |
| F-01 | Apoteker | Proses & serahkan resep, stok berkurang otomatis, riwayat penyerahan | ✅ |
| F-02 | Apoteker | Tidak bisa membuka laporan, loket, atau praktik dokter | ✅ |
| A-01 | Admin | Jadwal praktik, pengumuman, pengaduan, kirim alert, laporan; tidak bisa kelola akun/izin/audit | ✅ (setelah perbaikan T-01) |
| S-01 | Super Admin | Akun staf, peran & izin, template notifikasi, audit, pengaturan, log WhatsApp | ✅ |
| S-02 | Super Admin | Laporan bulanan Excel (.xlsx) & PDF ber-kop | ✅ |
| S-03 | Super Admin | 5x salah password → akun terkunci, Super Admin diberi tahu, tombol Buka kunci | ✅ |
| U-01 | Publik | Beranda, layar antrean, jadwal, alur, info, kebijakan privasi tanpa login; tanpa nama/NIK pasien | ✅ |
| U-02 | Publik | Halaman privat mengarah ke halaman masuk | ✅ |
| P-10 | Pasien | Profil → Unduh data saya (hak akses UU PDP): hanya data milik sendiri, tercatat di audit | ✅ |
| K-01 | Kiosk | API ber-tanda tangan: kunci salah, kedaluwarsa, dan pengulangan ditolak | ✅ |

**Penjelajahan tautan otomatis** (`tests/Feature/UatJelajahTest.php`): 161 halaman dibuka dari menu masing-masing peran (Super Admin 40, Admin 24, Apoteker 24, Pasien 22, Loket 21, Dokter Umum 15, Dokter Kandungan 15), tidak ada error server, halaman tidak ditemukan, atau tautan yang tampil tetapi ditolak, setelah perbaikan T-01.

**Total uji otomatis:** 22 skenario, 422 pemeriksaan, semua lulus.

## 3. Temuan & perbaikan

| ID | Temuan | Dampak | Status |
|---|---|---|---|
| T-01 | Dasbor peran Admin menampilkan tombol "Tambah staf" yang tidak boleh diakses Admin | Pengguna bingung, klik berakhir "Akses ditolak" | ✅ Diperbaiki |
| T-02 | Tombol hamburger tampil di desktop (aturan CSS tombol menimpa aturan sembunyi) | Tampilan ganda di desktop | ✅ Diperbaiki |
| T-03 | Menu hamburger: tidak ada status buka/tutup untuk pembaca layar, ikon tidak berubah, tidak menutup saat klik tautan/luar menu/Esc, halaman belakang masih bisa di-scroll, fokus tidak pindah | Aksesibilitas & kenyamanan di ponsel | ✅ Diperbaiki |
| T-04 | Kartu angka di dalam grid tidak sejajar (jarak antar-kartu bertumpuk ikut berlaku) | Tampilan berantakan di desktop & ponsel | ✅ Diperbaiki |
| T-05 | Judul bilah atas ponsel patah dua baris; kartu angka satu kolom terlalu panjang | Tampilan ponsel | ✅ Diperbaiki (judul satu baris, kartu dua kolom) |
| T-06 | Konsol browser penuh error saat server WebSocket mati (dicoba dua protokol, berulang di setiap halaman) | Beban & log tidak perlu | ✅ Diperbaiki (satu protokol, berhenti saat gagal, jeda 1 menit) |
| T-07 | Layar pembuka tidak muncul bila perangkat memakai mode "kurangi animasi" | Branding tidak tampil | ✅ Diperbaiki (tetap tampil tanpa animasi) |
| T-08 | Emoji bendera tampil sebagai huruf "ID" di Windows | Tampilan | ✅ Diganti gambar bendera |
| T-10 | Kebijakan privasi menyebut fitur "Unduh data saya" yang belum ada | Janji hak UU PDP tidak terpenuhi | ✅ Fitur dibuat |
| T-09 | Istilah teknis di layar pengguna ("real-time", "Live", "HMAC-SHA256", "Dashboard") | Sulit dipahami masyarakat umum | ✅ Diganti bahasa sehari-hari |

## 4. Uji beban ringan (50 pengguna serentak)

Perintah: `php tests/beban.php http://localhost:8000 50 2`

| Halaman | Permintaan | Gagal | p50 | p95 |
|---|---|---|---|---|
| Data antrean langsung | 100 | 0 | 9,5 dtk | 17,7 dtk |
| Beranda | 100 | 0 | 11,8 dtk | 21,8 dtk |
| Layar antrean | 100 | 0 | 9,8 dtk | 18,3 dtk |
| Jadwal dokter | 100 | 0 | 10,6 dtk | 21,0 dtk |

**Kesimpulan:** tidak ada permintaan yang gagal, tetapi waktu tunggu tinggi. Penyebabnya server pengembangan `php artisan serve` hanya melayani satu permintaan sekaligus dan PHP di Laragon tidak memakai OPcache (setiap permintaan ±0,6 dtk memuat ulang aplikasi). Pengujian ulang dengan `php artisan optimize` tidak memperbaiki angka, menandakan hambatan ada di server, bukan basis data. Data layar antrean publik kini disimpan sementara 5 detik (dibuang otomatis saat antrean berubah) untuk mengurangi kueri saat banyak layar terbuka.

**Uji ulang Fase 8 (susunan mirip produksi):** Nginx 1.29 + 6 proses PHP + OPcache + `php artisan optimize`, 50 pengguna serentak, 400 permintaan:

| Halaman | Gagal | p50 | p95 |
|---|---|---|---|
| Data antrean langsung | 0 | 0,55 dtk | 0,71 dtk |
| Beranda | 0 | 1,03 dtk | 1,58 dtk |
| Layar antrean | 0 | 0,57 dtk | 0,80 dtk |
| Jadwal dokter | 0 | 0,83 dtk | 1,20 dtk |

Kecepatan naik dari 2,4 menjadi **43,7 permintaan/detik** (±18×). ✅ Lulus. Tetap diulang di server sungguhan saat go-live (lihat docs/DEPLOY.md).

## 5. Catatan yang belum dapat diuji di lingkungan ini

1. Pengiriman email OTP ke Gmail sungguhan (SMTP belum diisi; diuji dengan email palsu).
2. Pengiriman WhatsApp lewat Fonnte sungguhan (token belum ada; diuji dengan respons palsu).
3. Cloudflare Turnstile sungguhan (kunci belum ada; diuji dengan respons palsu dan saat offline).
4. Uji beban di server sungguhan (susunan mirip produksi sudah diuji lokal, lihat bagian 4).
5. Uji di perangkat fisik (Android/iPhone); yang diuji adalah emulasi ukuran 375×812 di browser.
6. Nomor "Permenkes 2025" untuk Kebijakan Privasi belum diisi (menu Pengaturan → Permenkes tambahan).

## 6. Persetujuan

| Peran | Nama | Tanda tangan | Tanggal |
|---|---|---|---|
| Kepala Puskesmas | | | |
| Penanggung jawab TI | | | |
| Perwakilan pengguna (loket/dokter/farmasi) | | | |

Dibuat di Indonesia · © 2026 Seluruh hak cipta dilindungi · Grup NussCorp Technologies Indonesia
