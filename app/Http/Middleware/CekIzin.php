<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** RBAC berbasis tabel izin: ->middleware('izin:resep.buat') */
class CekIzin
{
    public function handle(Request $request, Closure $next, string $kode): Response
    {
        if (! $request->user()?->punyaIzin($kode)) {
            return CekPeran::tolak($request, "Anda tidak memiliki izin \"{$kode}\".");
        }
        return $next($request);
    }
}

