<?php

namespace App\Http\Controllers\Farmasi;

use App\Http\Controllers\Controller;
use App\Models\PenyerahanObat;
use Illuminate\Http\Request;

class PenyerahanController extends Controller
{
    public function __invoke(Request $request)
    {
        return view('farmasi.penyerahan', [
            'penyerahan' => PenyerahanObat::with('pasien', 'apoteker', 'resep.obat', 'resep.dokter')
                ->when($request->query('tanggal'), fn ($q, $t) => $q->whereDate('waktu', $t))
                ->latest('waktu')->paginate(20)->withQueryString(),
        ]);
    }
}

