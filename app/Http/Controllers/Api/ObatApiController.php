<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Obat;
use Illuminate\Http\Request;

class ObatApiController extends Controller
{
    public function index(Request $request)
    {
        return Obat::when($request->query('q'), fn ($q, $v) => $q->where('nama_obat', 'like', "%$v%"))->orderBy('nama_obat')->paginate(50);
    }

    public function show(Obat $obat) { return ['data' => $obat]; }

    public function store(Request $request)
    {
        return response()->json(['data' => Obat::create($this->valid($request))], 201);
    }

    public function update(Request $request, Obat $obat)
    {
        $obat->update($this->valid($request));
        return ['data' => $obat];
    }

    public function destroy(Obat $obat)
    {
        abort_if(\DB::table('detail_resep')->where('id_obat', $obat->id_obat)->exists(), 422, 'Obat sudah dipakai di resep.');
        $obat->delete();
        return response()->noContent();
    }

    private function valid(Request $r): array
    {
        $aturan = \App\Http\Controllers\Farmasi\ObatController::aturan($r->route('obat'));
        if (! $r->filled('kode')) $r->merge(['kode' => $r->route('obat')?->kode ?? Obat::kodeBerikut()]);
        foreach (['kategori' => 'Vitamin & Pemulihan', 'bentuk' => 'Tablet', 'poli' => 'umum', 'keamanan_hamil' => 'konsultasi', 'jumlah_bawaan' => 10, 'stok_minimum' => 20] as $k => $v) {
            if (! $r->filled($k)) $r->merge([$k => $r->route('obat')?->$k ?? $v]);
        }
        return $r->validate($aturan);
    }
}

