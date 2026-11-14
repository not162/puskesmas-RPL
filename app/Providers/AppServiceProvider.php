<?php

namespace App\Providers;

use App\Models\Pengaturan;
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
        View::composer('*', function ($view) {
            $p = Pengaturan::semua();
            $view->with(['pengaturan' => $p, 'namaPkm' => $p['nama_puskesmas']]);
        });
    }
}
