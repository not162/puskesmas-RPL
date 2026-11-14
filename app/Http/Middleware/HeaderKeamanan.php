<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Header keamanan standar (acuan ISO/IEC 27001:2022 kontrol 8.9 & 8.23, OWASP).
 * HSTS hanya dikirim saat HTTPS di produksi agar tidak mengunci pengembangan lokal.
 */
class HeaderKeamanan
{
    public function handle(Request $request, Closure $next): Response
    {
        $res = $next($request);
        $h = $res->headers;
        $h->set('X-Content-Type-Options', 'nosniff');
        $h->set('X-Frame-Options', 'SAMEORIGIN');
        $h->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        // Kamera hanya untuk situs sendiri (pindai QR di loket); lokasi, mikrofon, pembayaran dimatikan
        $h->set('Permissions-Policy', 'camera=(self), microphone=(), geolocation=(), payment=(), usb=()');
        $h->set('Cross-Origin-Opener-Policy', 'same-origin');
        $h->remove('X-Powered-By');
        if (! headers_sent()) header_remove('X-Powered-By'); // ditambahkan PHP sendiri (expose_php)
        if ($request->isSecure() && app()->isProduction()) {
            $h->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
        // Halaman berisi data pribadi tidak disimpan di cache browser/proxy bersama
        if ($request->user() && str_contains((string) $h->get('Content-Type'), 'text/html')) {
            $h->set('Cache-Control', 'no-store, private');
        }
        return $res;
    }
}
