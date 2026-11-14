<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Pengaturan;
use Illuminate\Console\Command;

/**
 * Rotasi kunci rahasia (ISO/IEC 27001:2022 kontrol 8.24).
 *  --hmac  : kunci tanda tangan QR tiket. Kunci lama tetap diterima (HMAC_SECRET_LAMA) agar tiket yang sudah
 *            diunduh pasien tidak tiba-tiba ditolak. Hapus HMAC_SECRET_LAMA setelah 8 hari (antrean maks 7 hari).
 *  --kiosk : kunci API kiosk. Kunci baru ditampilkan SEKALI untuk dipasang di perangkat kiosk.
 */
class RotasiKunci extends Command
{
    protected $signature = 'puskesmas:rotasi-kunci {--hmac} {--kiosk} {--berkas= : Berkas .env (bawaan: .env aplikasi)} {--paksa : Tanpa konfirmasi}';
    protected $description = 'Ganti kunci HMAC (QR tiket) dan/atau kunci API kiosk di .env';

    public function handle(): int
    {
        if (! $this->option('hmac') && ! $this->option('kiosk')) {
            $this->error('Pilih --hmac dan/atau --kiosk.');
            return self::INVALID;
        }
        $berkas = $this->option('berkas') ?: base_path('.env');
        if (! is_file($berkas) || ! is_writable($berkas)) {
            $this->error("Berkas $berkas tidak ditemukan/tidak dapat ditulis.");
            return self::FAILURE;
        }
        if (! $this->option('paksa') && ! $this->confirm('Kunci akan diganti dan .env diubah (salinan disimpan ke .env.backup). Lanjutkan?')) {
            return self::FAILURE;
        }

        $isi = file_get_contents($berkas);
        copy($berkas, dirname($berkas).'/.env.backup');
        $set = function (string $kunci, string $nilai) use (&$isi) {
            $isi = preg_match("/^{$kunci}=.*$/m", $isi)
                ? preg_replace("/^{$kunci}=.*$/m", $kunci.'='.$nilai, $isi)
                : rtrim($isi)."\n{$kunci}={$nilai}\n";
        };
        $ambil = fn (string $kunci) => preg_match("/^{$kunci}=(.*)$/m", $isi, $m) ? trim($m[1], " \"'") : '';

        if ($this->option('hmac')) {
            $lama = $ambil('HMAC_SECRET');
            $set('HMAC_SECRET_LAMA', $lama);
            $set('HMAC_SECRET', bin2hex(random_bytes(32)));
            Pengaturan::updateOrCreate(['kunci' => 'hmac_dirotasi'], ['nilai' => now()->toDateString()]);
            AuditLog::catat('rotasi_kunci', 'hmac', ['jenis' => 'qr_tiket']);
            $this->info('Kunci HMAC QR tiket diganti. Tiket lama tetap valid lewat HMAC_SECRET_LAMA; hapus baris itu setelah 8 hari.');
        }
        if ($this->option('kiosk')) {
            $baru = bin2hex(random_bytes(32));
            $set('KIOSK_HMAC_SECRET', $baru);
            AuditLog::catat('rotasi_kunci', 'kiosk', ['jenis' => 'api_kiosk']);
            $this->warn('Kunci kiosk baru (pasang di perangkat kiosk, hanya ditampilkan sekali):');
            $this->line($baru);
        }
        file_put_contents($berkas, $isi);
        if (! $this->option('berkas')) $this->call('config:clear');
        $this->info('Selesai. Jalankan ulang worker & PHP-FPM bila memakai cache konfigurasi (bash deploy/deploy.sh).');
        return self::SUCCESS;
    }
}

