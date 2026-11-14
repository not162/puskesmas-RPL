<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Spesialisasi;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Spesialisasi dikunci pada Umum & Kandungan; admin hanya mengubah nama poli. */
class SpesialisasiController extends Controller
{
    public function index()
    {
        return view('admin.spesialisasi', ['spesialisasi' => Spesialisasi::withCount('dokter')->get()]);
    }

    public function update(Request $request, Spesialisasi $spesialisasi)
    {
        $spesialisasi->update($request->validate(['poli' => ['required', 'max:50', Rule::unique('spesialisasi', 'poli')->ignore($spesialisasi->id_spesialisasi, 'id_spesialisasi')]]));
        return $this->kembali('success', 'Poli diperbarui', "Spesialisasi {$spesialisasi->nama} → {$spesialisasi->poli}.");
    }
}

