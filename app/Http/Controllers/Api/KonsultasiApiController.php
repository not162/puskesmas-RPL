<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Konsultasi;
use App\Models\Spesialisasi;
use App\Support\Notifier;
use Illuminate\Http\Request;

class KonsultasiApiController extends Controller
{
    private function boleh(Request $r, Konsultasi $k): void
    {
        $u = $r->user();
        $ok = ($u->pasien && $k->id_pasien === $u->pasien->id_pasien)
            || ($u->dokter && $k->id_spesialisasi === $u->dokter->id_spesialisasi)
            || $u->punyaIzin('akun.kelola');
        abort_unless($ok, 404);
    }

    private function json(Konsultasi $k): array
    {
        return ['kode' => $k->kode, 'poli' => $k->spesialisasi->poli, 'tanggal' => $k->tanggal, 'jenis' => $k->jenis, 'status' => $k->status,
            'keluhan' => $k->keluhan, 'tanggapan' => $k->tanggapan, 'diagnosis' => $k->diagnosis, 'dokter' => $k->dokter?->nama];
    }

    public function index(Request $request)
    {
        $u = $request->user();
        $q = Konsultasi::with('spesialisasi', 'dokter')->latest('tanggal');
        if ($u->pasien) $q->where('id_pasien', $u->pasien->id_pasien);
        elseif ($u->dokter) $q->where('id_spesialisasi', $u->dokter->id_spesialisasi)->where('jenis', 'online');
        elseif (! $u->punyaIzin('akun.kelola')) abort(403);
        return $q->paginate(20)->through(fn ($k) => $this->json($k));
    }

    public function store(Request $request)
    {
        $pasien = $request->user()->pasien;
        abort_unless($pasien, 403);
        $data = $request->validate(['id_spesialisasi' => 'required|exists:spesialisasi,id_spesialisasi', 'keluhan' => 'required|min:10|max:2000']);
        $k = Konsultasi::create($data + ['id_pasien' => $pasien->id_pasien, 'tanggal' => now(), 'jenis' => 'online', 'status' => 'menunggu']);
        $sp = Spesialisasi::find($data['id_spesialisasi']);
        Notifier::pasien($pasien, 3, ['poli' => $sp->poli], null, 'success');
        Notifier::peran(Notifier::peranDokterUntuk($sp->nama), 'Konsultasi online baru', "Keluhan baru di {$sp->poli}.");
        return response()->json(['data' => $this->json($k->load('spesialisasi'))], 201);
    }

    public function show(Request $request, Konsultasi $konsultasi)
    {
        $this->boleh($request, $konsultasi);
        return ['data' => $this->json($konsultasi->load('spesialisasi', 'dokter'))];
    }

    /** Dokter menanggapi konsultasi online. */
    public function update(Request $request, Konsultasi $konsultasi)
    {
        $d = $request->user()->dokter;
        abort_unless($d && $d->id_spesialisasi === $konsultasi->id_spesialisasi, 403, 'Bukan konsultasi poli Anda.');
        $data = $request->validate(['tanggapan' => 'required|min:5', 'diagnosis' => 'nullable|max:1000']);
        $konsultasi->update($data + ['id_dokter' => $d->id_dokter, 'status' => 'ditanggapi']);
        Notifier::pasien($konsultasi->pasien, 4, ['dokter' => $d->nama], null, 'success');
        return ['data' => $this->json($konsultasi->fresh(['spesialisasi', 'dokter']))];
    }
}

