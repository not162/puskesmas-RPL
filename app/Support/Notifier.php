<?php

namespace App\Support;

use App\Models\Akun;
use App\Models\Pasien;
use App\Models\Peran;
use App\Notifications\InfoStaf;
use App\Notifications\LangkahLayanan;
use Illuminate\Support\Facades\Notification;

class Notifier
{
    public static function pasien(?Pasien $pasien, int $langkah, array $param = [], ?string $url = null, string $tipe = 'info', ?string $pesan = null): void
    {
        if (\App\Models\TemplateNotifikasi::where('langkah', $langkah)->where('aktif', false)->exists()) return; // notifikasi langkah dimatikan
        $pasien?->akun?->notify(new LangkahLayanan($langkah, $param, $url, $tipe, $pesan));
    }

    /** Kirim ke semua akun aktif dengan peran tertentu. */
    public static function peran(array|string $peran, string $judul, string $pesan, ?string $url = null, string $tipe = 'info'): void
    {
        $akun = Akun::whereHas('peran', fn ($q) => $q->whereIn('nama_peran', (array) $peran))->where('status', 'aktif')->get();
        Notification::send($akun, new InfoStaf($judul, $pesan, $url, $tipe));
    }

    /** Peran dokter yang sesuai poli (Umum -> dokter_umum, Kandungan -> dokter_kandungan). */
    public static function peranDokterUntuk(string $namaSpesialisasi): string
    {
        return str_contains(strtolower($namaSpesialisasi), 'kandungan') ? Peran::DOKTER_KANDUNGAN : Peran::DOKTER_UMUM;
    }
}

