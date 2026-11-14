<?php

namespace App\Console\Commands;

use App\Models\Akun;
use App\Models\Peran;
use App\Notifications\InfoStaf;
use App\Support\Pemantau;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

class Pantau extends Command
{
    protected $signature = 'puskesmas:pantau {--kirim : Kirim peringatan ke Super Admin bila ada masalah}';
    protected $description = 'Pantau kesehatan sistem (cadangan, error, antrean, keamanan, disk, kunci, pustaka)';

    public function handle(): int
    {
        $hasil = Pemantau::periksa();
        Cache::put('pantau-terakhir', ['waktu' => now()->toIso8601String(), 'hasil' => $hasil], now()->addDays(2));

        $ikon = ['ok' => '<fg=green>✔ OK</>', 'warn' => '<fg=yellow>⚠ PERHATIAN</>', 'gagal' => '<fg=red>✖ GAGAL</>'];
        $this->table(['Status', 'Item', 'Keterangan', 'Saran'], array_map(fn ($x) => [$ikon[$x['status']], $x['item'], $x['ket'], $x['saran']], $hasil));

        $masalah = collect($hasil)->whereIn('status', ['gagal', 'warn']);
        if ($this->option('kirim') && $masalah->isNotEmpty()) {
            // Satu peringatan per masalah per hari agar tidak membanjiri notifikasi
            $baru = $masalah->filter(fn ($x) => Cache::add('pantau-dikirim:'.$x['kode'].':'.$x['status'], 1, now()->endOfDay()));
            if ($baru->isNotEmpty()) {
                $admin = Akun::whereHas('peran', fn ($q) => $q->where('nama_peran', Peran::SUPER_ADMIN))->where('status', 'aktif')->get();
                $tipe = $baru->contains('status', 'gagal') ? 'danger' : 'warning';
                Notification::send($admin, new InfoStaf('Kesehatan sistem: '.$baru->count().' hal perlu dicek',
                    $baru->map(fn ($x) => $x['item'].': '.$x['ket'])->implode(' · '), route('admin.kesehatan'), $tipe));
                $this->warn('Peringatan dikirim ke Super Admin ('.$baru->count().' hal).');
            }
        }
        $s = Pemantau::ringkas($hasil);
        $this->line($s === 'ok' ? '<fg=green>Sistem sehat.</>' : ($s === 'warn' ? '<fg=yellow>Ada hal yang perlu diperhatikan.</>' : '<fg=red>Ada masalah yang harus segera diperbaiki.</>'));
        return $s === 'gagal' ? self::FAILURE : self::SUCCESS;
    }
}

