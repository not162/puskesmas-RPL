<?php

namespace App\Http\Controllers\Pasien;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ResepController extends Controller
{
    public function index(Request $request)
    {
        return view('pasien.resep', [
            'resep' => $request->user()->pasien->resep()->with('dokter.spesialisasi', 'obat', 'penyerahan')->latest('tgl_resep')->paginate(10),
        ]);
    }
}

