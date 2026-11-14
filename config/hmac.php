<?php

return [
    // Kunci HMAC untuk QR tiket (default: APP_KEY)
    'secret' => env('HMAC_SECRET'),
    // Kunci sebelumnya, masih diterima selama masa transisi setelah rotasi (puskesmas:rotasi-kunci --hmac)
    'secret_lama' => env('HMAC_SECRET_LAMA'),
    // Kiosk / perangkat yang memanggil API bertanda tangan: id => secret
    'kiosk' => array_filter([
        env('KIOSK_ID', 'kiosk-loket-1') => env('KIOSK_HMAC_SECRET'),
    ]),
    // Toleransi selisih waktu (detik) untuk mencegah replay
    'jendela' => 300,
];

