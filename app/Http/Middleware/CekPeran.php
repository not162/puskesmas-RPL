<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Pemakaian: ->middleware('peran:apoteker,super_admin') */
class CekPeran
{
    public function handle(Request $request, Closure $next, string ...$peran): Response
    {
        $akun = $request->user();
        if (! $akun || ! $akun->hasRole(...$peran)) {
            return $this->tolak($request, 'Halaman ini tidak tersedia untuk peran Anda.');
        }
        return $next($request);
    }

    public static function tolak(Request $request, string $pesan): Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => $pesan], 403);
        }
        return redirect()->route('dashboard')->with('alert', ['tipe' => 'danger', 'judul' => 'Akses ditolak', 'pesan' => $pesan]);
    }
}

