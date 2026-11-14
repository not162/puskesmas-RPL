<?php

return [
    // 'antrean' = email OTP lewat queue (butuh `php artisan queue:work`), 'langsung' = kirim saat itu juga
    'pengiriman' => env('OTP_PENGIRIMAN', 'antrean'),
];

