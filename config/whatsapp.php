<?php

return [
    // log = tidak mengirim, hanya dicatat (lokal) · fonnte = kirim lewat api.fonnte.com
    'driver' => env('WA_DRIVER', 'log'),
    'fonnte_token' => env('FONNTE_TOKEN'),
    // antrean = lewat queue (butuh queue:work) · langsung = saat itu juga
    'pengiriman' => env('WA_PENGIRIMAN', 'langsung'),
    'timeout' => 8,
];

