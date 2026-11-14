<?php

/*
| Alur (requirement) step-by-step dan notifikasi tiap langkah.
| Dipakai oleh halaman "Alur Layanan", stepper di dashboard pasien,
| dan App\Notifications\LangkahLayanan.
*/
return [
    'langkah' => [
        1 => ['kode' => 'registrasi',  'judul' => 'Registrasi akun',        'syarat' => 'NIK 16 digit, nama sesuai KTP, nomor HP aktif, email, kata sandi minimal 8 karakter.', 'notif' => 'Akun berhasil dibuat. Nomor rekam medis Anda: :no_rm.'],
        2 => ['kode' => 'login',       'judul' => 'Masuk ke akun',          'syarat' => 'Email dan kata sandi yang didaftarkan. Akun harus berstatus aktif.', 'notif' => 'Login berhasil dari perangkat baru.'],
        3 => ['kode' => 'konsultasi',  'judul' => 'Konsultasi online (opsional)', 'syarat' => 'Pilih poli (Umum / Kandungan) dan tuliskan keluhan minimal 10 karakter.', 'notif' => 'Konsultasi online terkirim ke Poli :poli. Dokter akan menanggapi.'],
        4 => ['kode' => 'tanggapan',   'judul' => 'Tanggapan dokter',       'syarat' => 'Dokter membaca keluhan dan memberi saran awal / anjuran datang.', 'notif' => 'Dokter :dokter telah menanggapi konsultasi Anda.'],
        5 => ['kode' => 'antrian',     'judul' => 'Ambil antrean online',   'syarat' => 'Pilih poli dan tanggal kunjungan (hari ini s.d. 7 hari ke depan). Maksimal 1 antrean aktif per poli per hari.', 'notif' => 'Nomor antrean :nomor untuk :poli tanggal :tanggal. Unduh tiket Anda.'],
        6 => ['kode' => 'checkin',     'judul' => 'Check-in di loket',      'syarat' => 'Datang ke loket, tunjukkan tiket/kode antrean dan KTP.', 'notif' => 'Check-in berhasil. Silakan menunggu panggilan di ruang tunggu.'],
        7 => ['kode' => 'dipanggil',   'judul' => 'Dipanggil ke ruang periksa', 'syarat' => 'Pantau layar antrean publik atau notifikasi.', 'notif' => 'Nomor :nomor dipanggil. Silakan menuju :poli.'],
        8 => ['kode' => 'resep',       'judul' => 'Pemeriksaan & resep',    'syarat' => 'Dokter mencatat diagnosis dan menerbitkan resep bila perlu.', 'notif' => 'Resep diterbitkan dan diteruskan ke farmasi.'],
        9 => ['kode' => 'diproses',    'judul' => 'Resep diproses farmasi', 'syarat' => 'Apoteker menyiapkan obat sesuai resep.', 'notif' => 'Resep Anda sedang disiapkan apoteker.'],
        10 => ['kode' => 'diserahkan', 'judul' => 'Obat diserahkan',        'syarat' => 'Ambil obat di loket farmasi dengan menunjukkan kode resep.', 'notif' => 'Obat telah diserahkan. Semoga lekas sembuh!'],
    ],
];
