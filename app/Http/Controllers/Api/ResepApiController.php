<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Obat;
use App\Models\PenyerahanObat;
use App\Models\Resep;
use App\Support\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ResepApiController extends Controller
{
    private function json(Resep $r): array
    {
        return ['kode' => $r->kode, 'status' => $r->status, 'tgl_resep' => $r->tgl_resep, 'dokter' => $r->dokter->nama, 'pasien' => $r->pasien->nama,
            'obat' => $r->obat->map(fn ($o) => ['nama' => $o->nama_obat, 'jumlah' => $o->pivot->jumlah, 'satuan' => $o->satuan, 'aturan_pakai' => $o->pivot->aturan_pakai])];
    }

    private function query(Request $request)
    {
        $u = $request->user();
        $q = Resep::with('dokter', 'pasien', 'obat');
        return match (true) {
            (bool) $u->pasien => $q->where('id_pasien', $u->pasien->id_pasien),
            (bool) $u->dokter => $q->where('id_dokter', $u->dokter->id_dokter),
            $u->punyaIzin('resep.proses') => $q,
            default => abort(403),
        };
    }

    public function index(Request $request)
    {
        return $this->query($request)->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest('tgl_resep')->paginate(20)->through(fn ($r) => $this->json($r));
    }

    public function show(Request $request, Resep $resep)
    {
        abort_unless($this->query($request)->whereKey($resep->id_resep)->exists(), 404);
        return ['data' => $this->json($resep->load('dokter', 'pasien', 'obat'))];
    }

    /** Apoteker: status -> diproses / diserahkan (stok berkurang otomatis). */
    public function update(Request $request, Resep $resep)
    {
        $data = $request->validate(['status' => ['required', Rule::in(['diproses', 'diserahkan'])]]);
        $apt = $request->user()->apoteker;
        abort_unless($apt, 403, 'Hanya apoteker.');
        if ($data['status'] === 'diproses') {
            abort_unless($resep->status === 'baru', 422, 'Resep bukan berstatus baru.');
            $resep->update(['status' => 'diproses', 'id_apoteker' => $apt->id_apoteker]);
            Notifier::pasien($resep->pasien, 9);
        } else {
            abort_unless($resep->status === 'diproses', 422, 'Resep harus diproses dulu.');
            DB::transaction(function () use ($resep, $apt) {
                foreach ($resep->obat as $o) {
                    $obat = Obat::lockForUpdate()->find($o->id_obat);
                    abort_if($obat->stok < $o->pivot->jumlah, 422, "Stok {$obat->nama_obat} tidak cukup.");
                    $obat->decrement('stok', $o->pivot->jumlah);
                }
                PenyerahanObat::create(['id_resep' => $resep->id_resep, 'id_apoteker' => $apt->id_apoteker, 'id_pasien' => $resep->id_pasien, 'waktu' => now()]);
                $resep->update(['status' => 'diserahkan', 'id_apoteker' => $apt->id_apoteker]);
            });
            Notifier::pasien($resep->pasien, 10, [], null, 'success');
        }
        return ['data' => $this->json($resep->fresh(['dokter', 'pasien', 'obat']))];
    }
}

