<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Sinkronisasi real-time antar peran: klien mem-polling "versi" tiap kanal;
 * bila berubah, bagian halaman bertanda data-live dimuat ulang tanpa reload penuh.
 */
class RealtimeController extends Controller
{
    private const KANAL = [
        'antrian' => ['antrian', 'id_antrian', 'updated_at'],
        'konsultasi' => ['konsultasi', 'id_konsultasi', 'updated_at'],
        'resep' => ['resep', 'id_resep', null],
        'obat' => ['obat', 'id_obat', 'updated_at'],
        'pasien' => ['pasien', 'id_pasien', 'updated_at'],
        'pengaduan' => ['pengaduan', 'id_pengaduan', 'updated_at'],
        'jadwal' => ['jadwal_praktik', 'id_jadwal', null],
        'pengumuman' => ['pengumuman', 'id', 'updated_at'],
        'akun' => ['akun', 'id_akun', 'updated_at'],
    ];

    public static function versi(): array
    {
        return Cache::remember('realtime:versi', 2, function () {
            $out = [];
            foreach (self::KANAL as $k => [$tabel, $pk, $upd]) {
                $q = DB::table($tabel)->selectRaw("count(*) c, max($pk) m".($upd ? ", max($upd) u" : ''));
                if (in_array($tabel, ['antrian', 'resep', 'konsultasi', 'pengaduan'])) {
                    $q->selectRaw("sum(crc32(status)) s");
                }
                $out[$k] = substr(md5(json_encode($q->first())), 0, 10);
            }
            return $out;
        });
    }

    public function __invoke(Request $request)
    {
        return response()->json(['v' => self::versi(), 't' => now()->timestamp])->header('Cache-Control', 'no-store');
    }
}
