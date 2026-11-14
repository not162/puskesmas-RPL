<?php

namespace App\Http\Middleware;

use App\Support\Hmac;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Header wajib:
 *   X-Kiosk-Id:  id perangkat (config/hmac.php)
 *   X-Timestamp: unix time (detik)
 *   X-Signature: hex HMAC-SHA256(secret, METHOD\nPATH\nTIMESTAMP\nSHA256(body))
 * Tanda tangan hanya bisa dipakai sekali (anti-replay).
 */
class VerifikasiHmac
{
    public function handle(Request $request, Closure $next): Response
    {
        $id = (string) $request->header('X-Kiosk-Id');
        $ts = (string) $request->header('X-Timestamp');
        $sig = strtolower((string) $request->header('X-Signature'));
        $secret = config("hmac.kiosk.$id");

        $tolak = fn (string $m) => response()->json(['message' => $m], 401);

        if (! $secret) return $tolak('Kiosk tidak dikenal.');
        if (! ctype_digit($ts) || abs(time() - (int) $ts) > config('hmac.jendela')) return $tolak('Timestamp kedaluwarsa.');

        $harap = Hmac::tanda(Hmac::kanonik($request->method(), $request->path(), $ts, $request->getContent()), $secret);
        if (! hash_equals($harap, $sig)) return $tolak('Tanda tangan HMAC tidak valid.');
        if (! Cache::add('hmac:'.$sig, 1, config('hmac.jendela') * 2)) return $tolak('Permintaan sudah pernah dipakai (replay).');

        $request->attributes->set('kiosk_id', $id);
        return $next($request);
    }
}

