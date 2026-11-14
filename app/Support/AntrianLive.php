<?php

namespace App\Support;

use App\Models\Antrian;
use App\Models\Spesialisasi;

/**
 * Data layar antrian publik (pola Zavora: data publik terpisah).
 * HANYA nomor, nama poli, dan status. Tanpa ID dokter, ID/nama/NIK pasien.
 */
class AntrianLive
{
    /** Disimpan sementara 5 dtk (dibuang otomatis setiap antrean berubah) agar banyak layar tidak membebani basis data. */
    public static function data(): array
    {
        return \Illuminate\Support\Facades\Cache::remember('antrian-live', 5, fn () => self::hitung());
    }

    private static function hitung(): array
    {
        $hariIni = now()->toDateString();
        $poli = Spesialisasi::orderBy('id_spesialisasi')->get()->map(function (Spesialisasi $sp) use ($hariIni) {
            $q = fn () => Antrian::whereDate('tanggal', $hariIni)->where('id_spesialisasi', $sp->id_spesialisasi);
            $dipanggil = $q()->whereIn('status', ['dipanggil', 'dilayani'])->orderByDesc('waktu_panggil')->value('nomor');
            return [
                'poli' => $sp->poli,
                'sedang_dipanggil' => $dipanggil,
                'berikutnya' => $q()->whereIn('status', ['hadir', 'menunggu'])->orderBy('id_antrian')->limit(5)->pluck('nomor'),
                'menunggu' => $q()->whereIn('status', ['menunggu', 'hadir'])->count(),
                'selesai' => $q()->where('status', 'selesai')->count(),
                'total' => $q()->whereNot('status', 'batal')->count(),
            ];
        });

        return ['tanggal' => $hariIni, 'diperbarui' => now()->format('H:i:s'), 'poli' => $poli];
    }
}

