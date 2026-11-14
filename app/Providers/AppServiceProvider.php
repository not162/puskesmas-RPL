<?php

namespace App\Providers;

use App\Models\Pengaturan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Nama & kontak Puskesmas dari menu Pengaturan, tersedia di semua view
        // Real-time (Sprint 4): setiap perubahan model terpetakan -> sinyal WebSocket
        foreach (array_keys(\App\Support\Siaran::PETA) as $model) {
            $model::saved(fn ($m) => \App\Support\Siaran::dariModel($m));
            $model::deleted(fn ($m) => \App\Support\Siaran::dariModel($m));
        }
        // Notifikasi baru (channel database) -> sinyal ke kanal pribadi akun penerima
        Event::listen(\Illuminate\Notifications\Events\NotificationSent::class, function ($e) {
            if ($e->notifiable instanceof \App\Models\Akun) \App\Support\Siaran::notifikasi($e->notifiable->id_akun);
        });

        View::composer('*', function ($view) {
            $p = Pengaturan::semua();
            $view->with(['pengaturan' => $p, 'namaPkm' => $p['nama_puskesmas']]);
        });
    }
}
