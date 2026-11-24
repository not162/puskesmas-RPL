<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

/**
 * Memulihkan basis data dari berkas cadangan (.sql.gz) yang ada di storage/app/private/cadangan.
 * Menyelaraskan database antara Laragon lokal dan server deploy hosting.
 */
class Pulihkan extends Command
{
    protected $signature = 'puskesmas:pulihkan {berkas? : Nama atau path berkas .sql.gz / .sql} {--terbaru : Pulihkan berkas cadangan paling baru} {--paksa : Jalankan tanpa konfirmasi} {--cli : Paksa gunakan binary mysql CLI}';
    protected $description = 'Pulihkan basis data dari berkas cadangan (menyelaraskan database lokal dan deploy)';

    public function handle(): int
    {
        $dir = storage_path('app/private/cadangan');
        $namaBerkas = $this->argument('berkas');

        if (! $namaBerkas && $this->option('terbaru')) {
            $files = glob($dir.'/*.sql.gz');
            if (empty($files)) {
                $files = glob($dir.'/*.sql');
            }
            if (empty($files)) {
                $this->error('Tidak ditemukan berkas cadangan di '.$dir);
                return self::FAILURE;
            }
            usort($files, fn ($a, $b) => filemtime($b) - filemtime($a));
            $pathBerkas = $files[0];
        } elseif ($namaBerkas) {
            $pathBerkas = file_exists($namaBerkas) ? $namaBerkas : $dir.'/'.$namaBerkas;
        } else {
            $files = glob($dir.'/*.{sql.gz,sql}', GLOB_BRACE);
            if (empty($files)) {
                $files = glob($dir.'/*.sql.gz');
                if (empty($files)) {
                    $files = glob($dir.'/*.sql');
                }
            }
            if (empty($files)) {
                $this->error('Tidak ditemukan berkas cadangan di '.$dir);
                return self::FAILURE;
            }
            usort($files, fn ($a, $b) => filemtime($b) - filemtime($a));
            $pathBerkas = $files[0];
        }

        if (! file_exists($pathBerkas)) {
            $this->error("Berkas cadangan tidak ditemukan: {$pathBerkas}");
            return self::FAILURE;
        }

        $db = config('database.connections.'.config('database.default'));
        $targetDb = $db['database'];

        if (! $this->option('paksa') && ! $this->confirm("PERINGATAN: Memulihkan cadangan akan menimpa data di database '{$targetDb}'. Lanjutkan?")) {
            $this->info('Pemulihan dibatalkan.');
            return self::SUCCESS;
        }

        $this->info("Memulihkan basis data '{$targetDb}' dari ".basename($pathBerkas)."...");

        $isGz = str_ends_with($pathBerkas, '.gz');
        $berhasil = false;

        // 1. Eksekusi menggunakan PHP PDO (Universal: berjalan di Windows Laragon, Linux, VPS, dan cPanel)
        if (! $this->option('cli')) {
            try {
                if ($isGz) {
                    $gz = gzopen($pathBerkas, 'rb');
                    $sql = '';
                    while (! gzeof($gz)) {
                        $sql .= gzread($gz, 65536);
                    }
                    gzclose($gz);
                } else {
                    $sql = file_get_contents($pathBerkas);
                }

                if (trim($sql) === '') {
                    $this->error('Berkas cadangan kosong.');
                    return self::FAILURE;
                }

                // Nonaktifkan pemeriksaan foreign key selama import untuk mencegah benturan constraint
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                DB::connection()->getPdo()->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);
                DB::unprepared($sql);
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');

                $berhasil = true;
            } catch (\Throwable $e) {
                // Pulihkan foreign key checks bila terjadi galat
                try { DB::statement('SET FOREIGN_KEY_CHECKS=1;'); } catch (\Throwable) {}
                $this->warn('Gagal memulihkan via PDO: '.$e->getMessage().'. Mencoba via CLI...');
            }
        }

        // 2. Fallback atau bila opsi --cli digunakan
        if (! $berhasil) {
            $mysqlBin = (string) (getenv('MYSQL_PATH') ?: 'mysql');
            if ($isGz) {
                $cmd = "gunzip -c \"{$pathBerkas}\" | {$mysqlBin} --host={$db['host']} --port={$db['port']} --user={$db['username']} {$targetDb}";
            } else {
                $cmd = "{$mysqlBin} --host={$db['host']} --port={$db['port']} --user={$db['username']} {$targetDb} < \"{$pathBerkas}\"";
            }

            try {
                $proc = Process::fromShellCommandline($cmd, null, ['MYSQL_PWD' => (string) $db['password']], null, 600);
                $proc->run();
                if ($proc->isSuccessful()) {
                    $berhasil = true;
                } else {
                    $this->error('Pemulihan via CLI gagal: '.$proc->getErrorOutput());
                }
            } catch (\Throwable $e) {
                $this->error('Pemulihan via CLI gagal: '.$e->getMessage());
            }
        }

        if (! $berhasil) {
            AuditLog::catat('pemulihan_gagal', 'database', ['berkas' => basename($pathBerkas)]);
            $this->error('Gagal memulihkan basis data.');
            return self::FAILURE;
        }

        AuditLog::catat('pemulihan', 'database', ['berkas' => basename($pathBerkas)]);
        $this->info("Pemulihan selesai! Basis data '{$targetDb}' berhasil diselaraskan dengan cadangan.");
        return self::SUCCESS;
    }
}
