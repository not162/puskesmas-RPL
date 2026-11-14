<?php

namespace App\Http\Controllers\Dokter;

use App\Http\Controllers\Controller;
use App\Models\Antrian;
use App\Models\Konsultasi;
use App\Models\Pasien;
use Illuminate\Http\Request;

class RiwayatController extends Controller
{
    /** Pasien yang sedang diperiksa dokter ini (status dilayani). */
    public function pemeriksaan(Request $request)
    {
        $d = $request->user()->dokter;
        return view('dokter.pemeriksaan', [
            'antrian' => Antrian::with('pasien')->whereDate('tanggal', today())->where('id_spesialisasi', $d->id_spesialisasi)
                ->whereIn('status', ['dilayani', 'dipanggil'])->orderByRaw("FIELD(status,'dilayani','dipanggil')")->get(),
        ]);
    }

    /** Pasien yang pernah dilayani di poli dokter ini. */
    public function index(Request $request)
    {
        $d = $request->user()->dokter;
        return view('dokter.riwayat.index', [
            'pasien' => Pasien::whereHas('konsultasi', fn ($q) => $q->where('id_spesialisasi', $d->id_spesialisasi))
                ->when($request->query('q'), fn ($q, $v) => $q->where(fn ($w) => $w->where('nama', 'like', "%$v%")->orWhere('no_rm', $v)))
                ->withCount(['konsultasi' => fn ($q) => $q->where('id_spesialisasi', $d->id_spesialisasi)])
                ->orderBy('nama')->paginate(20)->withQueryString(),
        ]);
    }

    public function show(Request $request, Pasien $pasien)
    {
        $d = $request->user()->dokter;
        abort_unless($pasien->konsultasi()->where('id_spesialisasi', $d->id_spesialisasi)->exists(), 403, 'Pasien bukan pasien poli Anda.');
        return view('dokter.riwayat.show', [
            'p' => $pasien,
            'konsultasi' => Konsultasi::with('dokter', 'resep.obat')->where('id_pasien', $pasien->id_pasien)->latest('tanggal')->get(),
        ]);
    }

    public function jadwal(Request $request)
    {
        return view('dokter.jadwal', ['dokter' => $request->user()->dokter->load('jadwalPraktik', 'spesialisasi')]);
    }
}

