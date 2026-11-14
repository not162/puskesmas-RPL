<?php

use Illuminate\Support\Facades\Schedule;

/*
| Jadwal tugas otomatis. Di server cukup satu baris cron:
|   * * * * * cd /var/www/puskesmas && php artisan schedule:run >> /dev/null 2>&1
| Lihat daftar: php artisan schedule:list
*/

// Cadangan basis data setiap hari pukul 02.00, disimpan 14 hari
Schedule::command('puskesmas:cadangkan --simpan=14')->dailyAt('02:00')->withoutOverlapping()->onOneServer();

// Kode OTP kedaluwarsa (lebih dari 1 hari) dihapus
Schedule::call(fn () => \App\Models\OtpKode::where('kedaluwarsa', '<', now()->subDay())->delete())
    ->name('hapus-otp-kedaluwarsa')->hourly();

// Token API kedaluwarsa & job gagal lama
Schedule::command('sanctum:prune-expired --hours=24')->daily();
Schedule::command('queue:prune-failed --hours=168')->daily();

// Notifikasi yang sudah dibaca > 180 hari dibersihkan (rekam medis & audit TIDAK dihapus)
Schedule::call(fn () => \Illuminate\Support\Facades\DB::table('notifications')->whereNotNull('read_at')->where('created_at', '<', now()->subDays(180))->delete())
    ->name('bersihkan-notifikasi-lama')->weekly();

// Shared hosting tanpa Supervisor: jalankan antrean lewat cron (abaikan bila memakai Supervisor)
if (env('ANTREAN_LEWAT_CRON', false)) {
    Schedule::command('queue:work --stop-when-empty --tries=3 --max-time=55')->everyMinute()->withoutOverlapping();
}
