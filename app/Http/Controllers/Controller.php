<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

abstract class Controller
{
    /** Redirect + alert gaya Prisma (toast di pojok kanan atas). */
    protected function kembali(string $tipe, string $judul, string $pesan, ?string $ke = null): RedirectResponse
    {
        $r = $ke ? redirect($ke) : back();
        return $r->with('alert', compact('tipe', 'judul', 'pesan'));
    }
}

