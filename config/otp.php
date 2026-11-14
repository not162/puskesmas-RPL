<?php

return [
    // 'antrean' = email OTP lewat queue (butuh `php artisan queue:work`), 'langsung' = kirim saat itu juga
    'pengiriman' => env('OTP_PENGIRIMAN', 'antrean'),

    // Kode OTP saat ini HANYA untuk lupa password. Dua fitur di bawah dimatikan (bisa diaktifkan lagi lewat .env).
    'registrasi' => (bool) env('OTP_REGISTRASI', false),        // verifikasi email saat daftar
    'login_perangkat' => (bool) env('OTP_LOGIN_PERANGKAT', false), // kode saat masuk dari perangkat baru
];

