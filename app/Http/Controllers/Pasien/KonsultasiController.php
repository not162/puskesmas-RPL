<?php

namespace App\Http\Controllers\Pasien;

use App\Http\Controllers\Controller;
use App\Models\Konsultasi;
use App\Models\Spesialisasi;
use App\Support\Notifier;
use Illuminate\Http\Request;

class KonsultasiController extends Controller
{
    public function index(Request $request)
    {
        return view('pasien.konsultasi.index', [
            'konsultasi' => $request->user()->pasien->konsultasi()->with('spesialisasi', 'dokter')->latest('tanggal')->paginate(10),
        ]);
    }

    public function create()
    {
        return view('pasien.konsultasi.create', ['spesialisasi' => Spesialisasi::all()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_spesialisasi' => ['required', 'exists:spesialisasi,id_spesialisasi'],
            'keluhan' => ['required', 'string', 'min:10', 'max:2000'],
            'sejak' => ['nullable', 'string', 'max:100'],
        ], [], ['id_spesialisasi' => 'poli']);

        $pasien = $request->user()->pasien;
        $k = Konsultasi::create([
            'id_pasien' => $pasien->id_pasien,
            'id_spesialisasi' => $data['id_spesialisasi'],
            'tanggal' => now(),
            'keluhan' => $data['keluhan'].(! empty($data['sejak']) ? "\n\nDirasakan sejak: ".$data['sejak'] : ''),
            'jenis' => 'online',
            'status' => 'menunggu',
        ]);
        $sp = Spesialisasi::find($data['id_spesialisasi']);

        Notifier::pasien($pasien, 3, ['poli' => $sp->poli], route('pasien.konsultasi.show', $k), 'success');
        Notifier::peran(Notifier::peranDokterUntuk($sp->nama), 'Konsultasi online baru', "Ada keluhan baru di {$sp->poli} menunggu tanggapan.", route('dokter.konsultasi.show', $k));

        return redirect()->route('pasien.konsultasi.show', $k)
            ->with('alert', ['tipe' => 'success', 'judul' => 'Konsultasi terkirim', 'pesan' => 'Dokter akan menanggapi. Anda akan mendapat notifikasi.']);
    }

    public function show(Request $request, Konsultasi $konsultasi)
    {
        abort_unless($konsultasi->id_pasien === $request->user()->pasien->id_pasien, 404);
        return view('pasien.konsultasi.show', ['k' => $konsultasi->load('spesialisasi', 'dokter', 'resep.obat')]);
    }
}

