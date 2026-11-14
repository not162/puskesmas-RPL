<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Process;

/** Memeriksa celah keamanan pada pustaka (composer audit) — dijadwalkan mingguan. */
class AuditDependensi extends Command
{
    protected $signature = 'puskesmas:audit-dependensi {--composer= : Lokasi composer bila tidak di PATH}';
    protected $description = 'Periksa celah keamanan pustaka PHP (composer audit) dan simpan hasilnya';

    public function handle(): int
    {
        $bin = $this->option('composer') ?: (env('COMPOSER_PATH') ?: 'composer');
        $p = new Process([$bin, 'audit', '--locked', '--format=json', '--no-interaction'], base_path(), null, null, 180);
        $p->run();
        $json = json_decode($p->getOutput(), true);
        if (! is_array($json)) {
            $this->error('composer audit tidak dapat dijalankan: '.trim($p->getErrorOutput() ?: $p->getOutput()));
            return self::FAILURE;
        }
        $paket = array_keys($json['advisories'] ?? []);
        $celah = array_sum(array_map('count', $json['advisories'] ?? []));
        Cache::forever('audit-dependensi', ['waktu' => now()->toIso8601String(), 'celah' => $celah, 'paket' => $paket, 'ditinggalkan' => array_keys($json['abandoned'] ?? [])]);
        $celah ? $this->error("$celah celah keamanan pada: ".implode(', ', $paket)) : $this->info('Tidak ada celah keamanan yang diketahui pada pustaka.');
        return $celah ? self::FAILURE : self::SUCCESS;
    }
}

