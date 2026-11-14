<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Antrian;
use App\Models\Peran;
use App\Models\Spesialisasi;
use App\Support\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AntrianApiController extends Controller
{
    private function boleh(Request $r, Antrian $a): void
    {
        $u = $r->user();
        abort_unless($u->punyaIzin('antrian.kelola') || ($u->hasRole(Peran::PASIEN) && $a->id_pasien === $u->pasien?->id_pasien), 404);
    }

    private function json(Antrian $a): array
    {
        return ['kode' => $a->kode, 'nomor' => $a->nomor, 'poli' => $a->spesialisasi->poli, 'tanggal' => $a->tanggal->toDateString(),
            'status' => $a->status, 'sumber' => $a->sumber, 'di_depan' => $a->posisi(), 'pasien' => $a->pasien->nama];
    }

    public function index(Request $request)
    {
        $u = $request->user();
        abort_unless($u->punyaIzin('antrian.kelola') || $u->hasRole(Peran::PASIEN), 403);
        $q = Antrian::with('spesialisasi', 'pasien')->latest('id_antrian');
        if (! $u->punyaIzin('antrian.kelola')) $q->where('id_pasien', $u->pasien->id_pasien);
        else $q->whereDate('tanggal', $request->query('tanggal', today()->toDateString()));
        return $q->paginate(25)->through(fn ($a) => $this->json($a));
    }

    public function store(Request $request)
    {
        $pasien = $request->user()->pasien;
        abort_unless($pasien, 403, 'Hanya pasien yang dapat mengambil antrian online.');
        $data = $request->validate([
            'id_spesialisasi' => 'required|exists:spesialisasi,id_spesialisasi',
            'tanggal' => 'required|date|after_or_equal:today|before_or_equal:'.today()->addDays(7)->toDateString(),
            'keluhan_awal' => 'nullable|max:500',
        ]);
        if (Antrian::where('id_pasien', $pasien->id_pasien)->where('id_spesialisasi', $data['id_spesialisasi'])->whereDate('tanggal', $data['tanggal'])->whereNot('status', 'batal')->exists()) {
            throw ValidationException::withMessages(['tanggal' => 'Sudah ada antrian aktif di poli ini pada tanggal tersebut.']);
        }
        $sp = Spesialisasi::find($data['id_spesialisasi']);
        $a = DB::transaction(fn () => Antrian::create($data + ['id_pasien' => $pasien->id_pasien, 'nomor' => Antrian::nomorBerikut($sp, $data['tanggal']), 'sumber' => 'online']));
        Notifier::pasien($pasien, 5, ['nomor' => $a->nomor, 'poli' => $sp->poli, 'tanggal' => $a->tanggal->format('d/m/Y')], null, 'success');
        return response()->json(['data' => $this->json($a->load('spesialisasi', 'pasien'))], 201);
    }

    public function show(Request $request, Antrian $antrian)
    {
        $this->boleh($request, $antrian);
        return ['data' => $this->json($antrian->load('spesialisasi', 'pasien'))];
    }

    /** Loket: ubah status (hadir/dipanggil/batal). */
    public function update(Request $request, Antrian $antrian)
    {
        abort_unless($request->user()->punyaIzin('antrian.kelola'), 403);
        $data = $request->validate(['status' => ['required', Rule::in(['hadir', 'dipanggil', 'batal'])]]);
        $antrian->update($data + match ($data['status']) { 'hadir' => ['waktu_hadir' => now()], 'dipanggil' => ['waktu_panggil' => now()], default => [] });
        $langkah = ['hadir' => 6, 'dipanggil' => 7, 'batal' => 5][$data['status']];
        Notifier::pasien($antrian->pasien, $langkah, ['nomor' => $antrian->nomor, 'poli' => $antrian->spesialisasi->poli], null, $data['status'] === 'batal' ? 'danger' : 'info',
            $data['status'] === 'batal' ? "Antrian {$antrian->nomor} dibatalkan." : null);
        return ['data' => $this->json($antrian->fresh(['spesialisasi', 'pasien']))];
    }

    /** Pasien membatalkan antrian sendiri (status menunggu). */
    public function destroy(Request $request, Antrian $antrian)
    {
        $this->boleh($request, $antrian);
        abort_if($antrian->status !== 'menunggu', 422, 'Antrian sudah check-in, tidak dapat dibatalkan.');
        $antrian->update(['status' => 'batal']);
        return response()->noContent();
    }
}

