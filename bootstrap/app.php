<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withBroadcasting(__DIR__.'/../routes/channels.php', ['middleware' => ['web', 'auth']])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'peran' => \App\Http\Middleware\CekPeran::class,
            'izin' => \App\Http\Middleware\CekIzin::class,
            'aktif' => \App\Http\Middleware\AkunAktif::class,
            'hmac' => \App\Http\Middleware\VerifikasiHmac::class,
        ]);
        // Header keamanan untuk semua respons; percayai proxy (Nginx/Cloudflare) agar HTTPS & IP pengguna terbaca benar
        $middleware->append(\App\Http\Middleware\HeaderKeamanan::class);
        $middleware->trustProxies(at: env('TRUSTED_PROXIES', '127.0.0.1'));
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

