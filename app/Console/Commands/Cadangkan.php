<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

/**
 * Cadangan basis data (mysqldump, terkompresi gzip) ke storage/app/private/cadangan.
 * Dijadwalkan harian (routes/console.php). Cadangan lebih tua dari --simpan hari dihapus.
 * Pulihkan: gunzip -c berkas.sql.gz | mysql -u USER -p NAMA_DB
 */
class Cadangkan extends Command
{
    protected $signature = 'puskesmas:cadangkan {--simpan=14 : Lama penyimpanan (hari)} {--mysqldump= : Lokasi mysqldump bila tidak ada di PATH}';
    protected $description = 'Cadangkan basis data ke storage/app/private/cadangan (gzip) dan hapus cadangan lama';

    public function handle(): int
    {
        $db = config('database.connections.'.config('database.default'));
        if (($db['driver'] ?? '') !== 'mysql' && ($db['driver'] ?? '') !== 'mariadb') {
            $this->error('Pencadangan hanya mendukung MySQL/MariaDB.');
            return self::FAILURE;
        }

        $dir = storage_path('app/private/cadangan');
        if (! is_dir($dir)) mkdir($dir, 0750, true);
        $berkas = $dir.'/'.$db['database'].'-'.now()->format('Ymd-His').'.sql.gz';

        $bin = $this->option('mysqldump') ?: (env('MYSQLDUMP_PATH') ?: 'mysqldump');
        $proses = new Process([
            $bin, '--single-transaction', '--quick', '--routines', '--no-tablespaces', '--default-character-set=utf8mb4',
            '--host='.$db['host'], '--port='.$db['port'], '--user='.$db['username'], $db['database'],
        ], null, ['MYSQL_PWD' => (string) $db['password']], null, 600); // sandi lewat env, tidak tampil di daftar proses

        $gz = gzopen($berkas, 'wb6');
        $galat = '';
        $proses->run(function ($tipe, $data) use ($gz, &$galat) {
            $tipe === Process::OUT ? gzwrite($gz, $data) : $galat .= $data;
        });
        gzclose($gz);

        if (! $proses->isSuccessful() || filesize($berkas) < 200) {
            @unlink($berkas);
            $this->error('Pencadangan gagal: '.trim($galat ?: $proses->getErrorOutput()));
            AuditLog::catat('cadangan_gagal', 'database', ['pesan' => mb_substr($galat, 0, 200)]);
            return self::FAILURE;
        }

        // Hapus cadangan lama
        $batas = now()->subDays(max(1, (int) $this->option('simpan')))->getTimestamp();
        $dihapus = 0;
        foreach (glob($dir.'/*.sql.gz') as $f) {
            if (filemtime($f) < $batas) { unlink($f); $dihapus++; }
        }

        $ukuran = round(filesize($berkas) / 1024, 1);
        AuditLog::catat('cadangan', 'database', ['berkas' => basename($berkas), 'kb' => $ukuran, 'dihapus' => $dihapus]);
        $this->info("Cadangan tersimpan: storage/app/private/cadangan/".basename($berkas)." ({$ukuran} KB). Cadangan lama dihapus: {$dihapus}.");
        return self::SUCCESS;
    }
}
