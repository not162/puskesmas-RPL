<?php

namespace App\Console\Commands;

use App\Models\Akun;
use App\Models\Pengaturan;
use App\Models\Peran;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/** Cek kesiapan sistem sebelum dipakai: DB, SMTP, HMAC, password awal, antrean, data contoh. */
class CekKesiapan extends Command
{
    protected $signature = 'puskesmas:cek {--kirim-uji= : Kirim email uji ke alamat ini lewat SMTP}';
    protected $description = 'Periksa konfigurasi & kesiapan Sistem Informasi Puskesmas';

    private array $baris = [];
    private int $gagal = 0;

    private function hasil(string $status, string $item, string $ket): void
    {
        $ikon = ['ok' => '<fg=green>✔ OK</>', 'warn' => '<fg=yellow>⚠ PERHATIAN</>', 'gagal' => '<fg=red>✖ GAGAL</>'][$status];
        if ($status === 'gagal') $this->gagal++;
        $this->baris[] = [$ikon, $item, $ket];
    }

    public function handle(): int
    {
        // --- Aplikasi ---
        config('app.key') ? $this->hasil('ok', 'APP_KEY', 'terisi') : $this->hasil('gagal', 'APP_KEY', 'jalankan php artisan key:generate');
        if (app()->isProduction() && config('app.debug')) $this->hasil('gagal', 'APP_DEBUG', 'harus false di produksi');
        else $this->hasil('ok', 'Lingkungan', app()->environment().' · debug '.(config('app.debug') ? 'on' : 'off'));

        // --- Database ---
        try {
            DB::connection()->getPdo();
            $this->hasil('ok', 'Database', config('database.default').' · '.DB::connection()->getDatabaseName().' · '.Akun::count().' akun');
        } catch (\Throwable $e) {
            $this->hasil('gagal', 'Database', 'tidak tersambung: '.$e->getMessage());
            $this->table(['Status', 'Item', 'Keterangan'], $this->baris);
            return self::FAILURE;
        }

        // --- Kunci HMAC ---
        config('hmac.secret') ? $this->hasil('ok', 'HMAC_SECRET', 'terisi ('.strlen(config('hmac.secret')).' karakter)') : $this->hasil('warn', 'HMAC_SECRET', 'kosong, memakai APP_KEY');
        config('hmac.kiosk') ? $this->hasil('ok', 'Kiosk HMAC', count(config('hmac.kiosk')).' kiosk terdaftar') : $this->hasil('warn', 'Kiosk HMAC', 'KIOSK_HMAC_SECRET kosong, API kiosk menolak semua permintaan');

        // --- Password awal masih dipakai? ---
        $masihAwal = [];
        foreach ([Peran::SUPER_ADMIN, Peran::ADMIN, Peran::DOKTER_UMUM, Peran::DOKTER_KANDUNGAN, Peran::APOTEKER, Peran::LOKET, Peran::PASIEN] as $p) {
            $awal = env('SEED_PASSWORD_'.strtoupper($p));
            if (! $awal) continue;
            foreach (Akun::whereHas('peran', fn ($q) => $q->where('nama_peran', $p))->get() as $a) {
                if (Hash::check($awal, $a->password)) $masihAwal[] = $a->username;
            }
        }
        $masihAwal ? $this->hasil('warn', 'Password awal', 'belum diganti: '.implode(', ', $masihAwal)) : $this->hasil('ok', 'Password awal', 'semua sudah diganti');

        // --- Email / SMTP ---
        $mailer = config('mail.default');
        if ($mailer === 'smtp') {
            if (! config('mail.mailers.smtp.username') || ! config('mail.mailers.smtp.password')) {
                $this->hasil('gagal', 'SMTP', 'MAIL_USERNAME / MAIL_PASSWORD kosong');
            } else {
                try {
                    $t = Mail::mailer('smtp')->getSymfonyTransport();
                    if (method_exists($t, 'start')) $t->start();
                    $this->hasil('ok', 'SMTP', 'login berhasil ke '.config('mail.mailers.smtp.host').':'.config('mail.mailers.smtp.port'));
                } catch (\Throwable $e) {
                    $this->hasil('gagal', 'SMTP', 'gagal login: '.str($e->getMessage())->limit(120));
                }
            }
        } else {
            $this->hasil('warn', 'Email OTP', "MAIL_MAILER=$mailer — OTP hanya tertulis di storage/logs/laravel.log");
        }
        if ($tujuan = $this->option('kirim-uji')) {
            try {
                Mail::raw('Email uji dari '.Pengaturan::nilai('nama_puskesmas').'. Konfigurasi SMTP berhasil.', fn ($m) => $m->to($tujuan)->subject('Uji SMTP Puskesmas'));
                $this->hasil('ok', 'Email uji', "terkirim ke $tujuan");
            } catch (\Throwable $e) {
                $this->hasil('gagal', 'Email uji', str($e->getMessage())->limit(120));
            }
        }
        $palsu = Akun::where('email', 'like', '%.test')->whereHas('peran', fn ($q) => $q->where('nama_peran', Peran::PASIEN))->pluck('username');
        $palsu->isNotEmpty() ? $this->hasil('warn', 'Email pasien', 'domain .test tidak bisa menerima OTP: '.$palsu->implode(', ')) : $this->hasil('ok', 'Email pasien', 'tidak ada email contoh');

        // --- Antrean (queue) ---
        $mode = config('otp.pengiriman');
        if ($mode === 'antrean') {
            $macet = DB::table('jobs')->where('created_at', '<', now()->subMinutes(2)->timestamp)->count();
            $macet ? $this->hasil('gagal', 'Antrean OTP', "$macet email tertahan > 2 menit — jalankan php artisan queue:work") : $this->hasil('ok', 'Antrean OTP', 'mode antrean · tidak ada email tertahan');
        } else {
            $this->hasil('ok', 'Antrean OTP', 'mode langsung (tanpa worker)');
        }
        $gagalJob = DB::table('failed_jobs')->count();
        $gagalJob ? $this->hasil('warn', 'Job gagal', "$gagalJob job gagal — cek php artisan queue:failed") : $this->hasil('ok', 'Job gagal', 'tidak ada');

        // --- Real-time WebSocket (Reverb) ---
        if (config('broadcasting.default') === 'reverb') {
            $h = config('broadcasting.connections.reverb.options.host'); $pt = (int) config('broadcasting.connections.reverb.options.port');
            $sock = @fsockopen($h === 'localhost' ? '127.0.0.1' : $h, $pt, $en, $es, 1.5);
            if ($sock) { fclose($sock); $this->hasil('ok', 'WebSocket (Reverb)', "berjalan di $h:$pt — pembaruan instan"); }
            else $this->hasil('warn', 'WebSocket (Reverb)', "tidak berjalan di $h:$pt — halaman memakai polling. Jalankan php artisan reverb:start");
        } else {
            $this->hasil('ok', 'Real-time', 'mode polling (BROADCAST_CONNECTION='.config('broadcasting.default').')');
        }

        // --- WhatsApp ---
        $drv = config('whatsapp.driver');
        if ($drv === 'fonnte') config('whatsapp.fonnte_token') ? $this->hasil('ok', 'WhatsApp', 'Fonnte · token terisi · '.Pengaturan::nilai('wa_maks_harian').' pesan/nomor/hari') : $this->hasil('gagal', 'WhatsApp', 'WA_DRIVER=fonnte tetapi FONNTE_TOKEN kosong');
        else $this->hasil('warn', 'WhatsApp', 'WA_DRIVER=log — pesan hanya dicatat (menu Log WhatsApp), belum terkirim');

        // --- Penyimpanan ---
        $ok = is_writable(storage_path('logs')) && Storage::disk('local')->put('.cek', '1');
        Storage::disk('local')->delete('.cek');
        $ok ? $this->hasil('ok', 'Penyimpanan', 'storage dapat ditulis (KTP disimpan privat)') : $this->hasil('gagal', 'Penyimpanan', 'storage tidak dapat ditulis');

        // --- Identitas ---
        $p = Pengaturan::semua();
        $p['alamat'] === Pengaturan::BAWAAN['alamat'] || $p['whatsapp'] === Pengaturan::BAWAAN['whatsapp']
            ? $this->hasil('warn', 'Identitas Puskesmas', 'alamat/WhatsApp masih contoh — ubah di menu Pengaturan')
            : $this->hasil('ok', 'Identitas Puskesmas', $p['nama_puskesmas']);

        $this->table(['Status', 'Item', 'Keterangan'], $this->baris);
        $this->line($this->gagal ? "<fg=red>{$this->gagal} masalah harus diperbaiki.</>" : '<fg=green>Tidak ada masalah kritis.</>');
        return $this->gagal ? self::FAILURE : self::SUCCESS;
    }
}
