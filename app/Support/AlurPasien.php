<?php

namespace App\Support;

use App\Models\Pasien;

/** Menghitung posisi pasien pada alur 10 langkah (config/alur.php). */
class AlurPasien
{
    public static function langkahSaatIni(Pasien $p): int
    {
        $antrian = $p->antrian()->whereNot('status', 'batal')->latest('id_antrian')->first();
        $resep = $p->resep()->latest('id_resep')->first();
        $kons = $p->konsultasi()->latest('id_konsultasi')->first();

        if ($resep && $antrian && $resep->tgl_resep >= $antrian->created_at) {
            return match ($resep->status) { 'diserahkan' => 10, 'diproses' => 9, default => 8 };
        }
        if ($antrian) {
            return match ($antrian->status) {
                'dipanggil', 'dilayani', 'selesai' => 7,
                'hadir' => 6,
                default => 5,
            };
        }
        if ($kons) {
            return $kons->status === 'menunggu' ? 3 : 4;
        }
        return 2;
    }
}

