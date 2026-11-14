<?php

namespace App\Support;

use App\Models\Akun;
use App\Models\AuditLog;
use App\Models\LogLogin;
use App\Models\Pengaduan;
use App\Models\Pengaturan;
use App\Models\WaLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Pemeriksaan kesehatan sistem yang berjalan terus (Fase 9 — pemeliharaan).
 * Hasil: daftar [status ok|warn|gagal, kode, item, keterangan, saran].
 */
class Pemantau
{
    public static function periksa(): array
    {
        $h = [];
        $tambah = function (string $status, string $kode, string $item, string $ket, string $saran = '') use (&$h) {
            $h[] = compact('status', 'kode', 'item', 'ket', 'saran');
        };

        // 1. Basis data
        try {
            $mulai = microtime(true);
            DB::select('select 1');
            $ms = round((microtime(true) - $mulai) * 1000);
            $tambah($ms > 500 ? 'warn' : 'ok', 'db', 'Basis data', "tersambung ({$ms} ms)", $ms > 500 ? 'Periksa beban server basis data.' : '');
        } catch (\Throwable $e) {
            $tambah('gagal', 'db', 'Basis data', 'tidak tersambung', 'Periksa layanan MySQL dan isi DB_* di .env.');
            return $h;
        }

        // 2. Cadangan terakhir
        $berkas = glob(storage_path('app/private/cadangan/*.sql.gz')) ?: [];
        if (! $berkas) {
            $tambah(app()->isProduction() ? 'gagal' : 'warn', 'cadangan', 'Cadangan basis data', 'belum ada cadangan', 'Jalankan php artisan puskesmas:cadangkan dan pastikan cron aktif.');
        } else {
            $umur = (time() - max(array_map('filemtime', $berkas))) / 3600;
            $umur > 26
                ? $tambah('gagal', 'cadangan', 'Cadangan basis data', sprintf('terakhir %.0f jam lalu', $umur), 'Cadangan harian tidak berjalan. Periksa cron & log.')
                : $tambah('ok', 'cadangan', 'Cadangan basis data', sprintf('terakhir %.0f jam lalu · %d berkas', $umur, count($berkas)));
        }
        if (AuditLog::where('aksi', 'cadangan_gagal')->where('waktu', '>=', now()->subDay())->exists()) {
            $tambah('gagal', 'cadangan_gagal', 'Cadangan gagal', 'ada cadangan yang gagal dalam 24 jam', 'Lihat menu Audit (aksi cadangan_gagal).');
        }

        // 3. Error aplikasi 24 jam terakhir
        $err = self::hitungError(24);
        $err === 0 ? $tambah('ok', 'error', 'Error aplikasi (24 jam)', 'tidak ada')
            : $tambah($err > 20 ? 'gagal' : 'warn', 'error', 'Error aplikasi (24 jam)', "$err catatan error", 'Periksa storage/logs/laravel*.log.');

        // 4. Antrean latar belakang (email OTP / WhatsApp)
        $tertahan = DB::table('jobs')->where('created_at', '<', now()->subMinutes(5)->timestamp)->count();
        $gagalJob = DB::table('failed_jobs')->where('failed_at', '>=', now()->subDay())->count();
        $tertahan ? $tambah('gagal', 'antrean', 'Antrean email/WhatsApp', "$tertahan tugas tertahan > 5 menit", 'Worker antrean mati: supervisorctl status / queue:work.')
            : $tambah($gagalJob ? 'warn' : 'ok', 'antrean', 'Antrean email/WhatsApp', $gagalJob ? "$gagalJob tugas gagal (24 jam)" : 'lancar', $gagalJob ? 'php artisan queue:failed lalu queue:retry all.' : '');

        // 5. WhatsApp
        $waGagal = WaLog::where('status', 'gagal')->where('created_at', '>=', now()->subDay())->count();
        if ($waGagal) $tambah($waGagal > 10 ? 'gagal' : 'warn', 'wa', 'WhatsApp gagal (24 jam)', "$waGagal pesan", 'Periksa saldo/token Fonnte dan menu Log WhatsApp.');

        // 6. Keamanan masuk
        $gagalMasuk = LogLogin::where('hasil', 'gagal')->where('waktu', '>=', now()->subHour())->count();
        $terkunci = Akun::where('terkunci_sampai', '>', now())->count();
        $gagalMasuk > 50
            ? $tambah('warn', 'masuk', 'Percobaan masuk gagal (1 jam)', "$gagalMasuk kali, $terkunci akun terkunci", 'Kemungkinan serangan tebak sandi. Aktifkan Turnstile & periksa Log Login.')
            : $tambah('ok', 'masuk', 'Percobaan masuk gagal (1 jam)', "$gagalMasuk kali · $terkunci akun terkunci");

        // 7. Pengaduan menunggu > 3 hari (janji tanggapan 3x24 jam di Kebijakan Privasi)
        $telat = Pengaduan::where('status', 'baru')->where('created_at', '<', now()->subDays(3))->count();
        $telat ? $tambah('warn', 'pengaduan', 'Pengaduan belum ditanggapi', "$telat pengaduan > 3 hari", 'Tanggapi di menu Pengaduan (janji layanan 3x24 jam).')
            : $tambah('ok', 'pengaduan', 'Pengaduan', 'semua ditanggapi tepat waktu');

        // 8. Ruang disk
        $bebas = @disk_free_space(storage_path());
        if ($bebas !== false) {
            $gb = $bebas / 1073741824;
            $tambah($gb < 1 ? 'gagal' : ($gb < 5 ? 'warn' : 'ok'), 'disk', 'Ruang disk', sprintf('%.1f GB tersisa', $gb), $gb < 5 ? 'Hapus log/cadangan lama atau tambah kapasitas.' : '');
        }

        // 9. Umur kunci HMAC (rotasi disarankan tiap 180 hari)
        $rotasi = Pengaturan::nilai('hmac_dirotasi');
        $hari = $rotasi ? (int) \Carbon\Carbon::parse($rotasi)->diffInDays(now()) : null;
        $hari === null ? $tambah('warn', 'kunci', 'Kunci tanda tangan (HMAC)', 'belum pernah dirotasi', 'php artisan puskesmas:rotasi-kunci --hmac')
            : $tambah($hari > 180 ? 'warn' : 'ok', 'kunci', 'Kunci tanda tangan (HMAC)', "dirotasi $hari hari lalu", $hari > 180 ? 'php artisan puskesmas:rotasi-kunci --hmac' : '');

        // 10. Celah keamanan pustaka (hasil audit mingguan)
        $audit = Cache::get('audit-dependensi');
        if (! $audit) $tambah('warn', 'dependensi', 'Celah keamanan pustaka', 'belum diperiksa', 'php artisan puskesmas:audit-dependensi');
        else $audit['celah'] > 0
            ? $tambah('gagal', 'dependensi', 'Celah keamanan pustaka', $audit['celah'].' celah: '.implode(', ', array_slice($audit['paket'], 0, 4)), 'Jalankan composer update untuk paket tersebut, uji, lalu deploy.')
            : $tambah('ok', 'dependensi', 'Celah keamanan pustaka', 'tidak ada celah · diperiksa '.\Carbon\Carbon::parse($audit['waktu'])->locale('id')->diffForHumans());

        return $h;
    }

    /** Hitung baris ".ERROR:" / ".CRITICAL:" di log dalam N jam terakhir. */
    public static function hitungError(int $jam): int
    {
        $batas = now()->subHours($jam);
        $n = 0;
        foreach (glob(storage_path('logs/laravel*.log')) ?: [] as $f) {
            if (filemtime($f) < $batas->getTimestamp()) continue;
            $h = fopen($f, 'r');
            while (($baris = fgets($h)) !== false) {
                if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] \w+\.(ERROR|CRITICAL|ALERT|EMERGENCY):/', $baris, $m)
                    && $m[1] >= $batas->format('Y-m-d H:i:s')) $n++;
            }
            fclose($h);
        }
        return $n;
    }

    public static function ringkas(array $hasil): string
    {
        $g = collect($hasil)->where('status', 'gagal')->count();
        $w = collect($hasil)->where('status', 'warn')->count();
        return $g ? 'gagal' : ($w ? 'warn' : 'ok');
    }
}

