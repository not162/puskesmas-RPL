<?php

namespace App\Support;

use App\Events\DataBerubah;
use Illuminate\Database\Eloquent\Model;

/**
 * Mengumpulkan perubahan selama satu request lalu menyiarkannya sekali (saat request selesai)
 * lewat WebSocket (Laravel Reverb). Bila server Reverb mati, kegagalan diabaikan —
 * klien otomatis tetap memakai polling.
 */
class Siaran
{
    /** model => kanal */
    public const PETA = [
        \App\Models\Antrian::class => 'antrian',
        \App\Models\Konsultasi::class => 'konsultasi',
        \App\Models\Resep::class => 'resep',
        \App\Models\PenyerahanObat::class => 'resep',
        \App\Models\Obat::class => 'obat',
        \App\Models\Pasien::class => 'pasien',
        \App\Models\Pengaduan::class => 'pengaduan',
        \App\Models\JadwalPraktik::class => 'jadwal',
        \App\Models\Pengumuman::class => 'pengumuman',
        \App\Models\Akun::class => 'akun',
    ];

    /** Kanal yang aman diumumkan ke publik (hanya sinyal; data publik tetap disaring AntrianLive). */
    private const PUBLIK = ['antrian', 'jadwal', 'pengumuman'];

    private static array $antre = [];   // saluran => [kanal...]
    private static bool $terdaftar = false;
    public static array $terkirim = [];  // untuk test & diagnosa

    public static function dariModel(Model $m): void
    {
        $kanal = self::PETA[$m::class] ?? null;
        if (! $kanal) return;
        self::tandai('staf', $kanal);
        if (in_array($kanal, self::PUBLIK, true)) self::tandai('publik', $kanal);

        // Pemilik data (pasien) ikut diberi tahu lewat kanal privatnya
        $idAkun = null;
        if ($m instanceof \App\Models\Pasien) $idAkun = $m->id_akun;
        elseif ($m instanceof \App\Models\Pengaduan) $idAkun = $m->id_akun;
        elseif (isset($m->id_pasien)) $idAkun = \App\Models\Pasien::whereKey($m->id_pasien)->value('id_akun');
        if ($idAkun) self::tandai('akun.'.$idAkun, $kanal);
    }

    public static function notifikasi(int $idAkun): void
    {
        self::tandai('akun.'.$idAkun, 'notifikasi');
    }

    public static function tandai(string $saluran, string $kanal): void
    {
        self::$antre[$saluran][$kanal] = $kanal;
        if (! self::$terdaftar) {
            self::$terdaftar = true;
            app()->terminating(fn () => self::kirim());
        }
    }

    public static function kirim(): void
    {
        $antre = self::$antre;
        self::$antre = [];
        self::$terdaftar = false;
        if (config('broadcasting.default') === 'null' || ! $antre) return;
        // Pemutus sirkuit: bila Reverb baru saja gagal, lewati 30 dtk agar request tidak ikut lambat
        if (\Illuminate\Support\Facades\Cache::get('siaran:mati')) return;
        foreach ($antre as $saluran => $kanal) {
            try {
                broadcast(new DataBerubah($saluran, $kanal));
                self::$terkirim[] = [$saluran, array_values($kanal)];
            } catch (\Throwable $e) {
                // Reverb tidak berjalan: abaikan, klien memakai polling
                \Illuminate\Support\Facades\Cache::put('siaran:mati', 1, 30);
                logger()->debug('Siaran gagal: '.$e->getMessage());
                return;
            }
        }
    }

    /** Hanya untuk test: kirim segera tanpa menunggu request selesai. */
    public static function kirimSekarang(): array
    {
        self::$terkirim = [];
        self::kirim();
        return self::$terkirim;
    }
}

