<?php

namespace App\Http\Controllers\Farmasi;

use App\Http\Controllers\Controller;
use App\Models\Obat;
use App\Models\PenyerahanObat;
use App\Models\Resep;
use App\Support\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResepController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'aktif');
        return view('farmasi.resep.index', [
            'status' => $status,
            'resep' => Resep::with('pasien', 'dokter.spesialisasi', 'obat')
                ->when($status === 'aktif', fn ($q) => $q->whereIn('status', ['baru', 'diproses']), fn ($q) => $status === 'semua' ? $q : $q->where('status', $status))
                ->orderByRaw("FIELD(status,'baru','diproses','diserahkan')")->latest('tgl_resep')->paginate(15)->withQueryString(),
        ]);
    }

    public function show(Resep $resep)
    {
        return view('farmasi.resep.show', ['r' => $resep->load('pasien', 'dokter.spesialisasi', 'obat', 'apoteker', 'penyerahan', 'konsultasi')]);
    }

    public function proses(Request $request, Resep $resep)
    {
        if ($resep->status !== 'baru') {
            return $this->kembali('warning', 'Tidak dapat diproses', "Status resep: {$resep->status}.");
        }
        $resep->update(['status' => 'diproses', 'id_apoteker' => $request->user()->apoteker?->id_apoteker]);
        Notifier::pasien($resep->pasien, 9, [], route('pasien.resep'), 'info');
        return $this->kembali('success', 'Resep diproses', 'Siapkan obat lalu tekan "Serahkan".');
    }

    public function serahkan(Request $request, Resep $resep)
    {
        if ($resep->status !== 'diproses') {
            return $this->kembali('warning', 'Belum diproses', 'Resep harus diproses sebelum diserahkan.');
        }
        $apoteker = $request->user()->apoteker;
        if (! $apoteker) {
            return $this->kembali('danger', 'Profil apoteker', 'Hanya akun apoteker yang dapat mencatat penyerahan obat.');
        }

        try {
            DB::transaction(function () use ($resep, $apoteker) {
                foreach ($resep->obat as $o) {
                    $obat = Obat::lockForUpdate()->find($o->id_obat);
                    if ($obat->stok < $o->pivot->jumlah) {
                        throw new \RuntimeException("Stok {$obat->nama_obat} tidak cukup (sisa {$obat->stok}).");
                    }
                    $obat->decrement('stok', $o->pivot->jumlah);
                }
                PenyerahanObat::create(['id_resep' => $resep->id_resep, 'id_apoteker' => $apoteker->id_apoteker, 'id_pasien' => $resep->id_pasien, 'waktu' => now()]);
                $resep->update(['status' => 'diserahkan', 'id_apoteker' => $apoteker->id_apoteker]);
            });
        } catch (\RuntimeException $e) {
            return $this->kembali('danger', 'Stok tidak cukup', $e->getMessage());
        }

        Notifier::pasien($resep->pasien, 10, [], route('pasien.resep'), 'success');
        return $this->kembali('success', 'Obat diserahkan', 'Stok obat telah dikurangi otomatis.', route('farmasi.resep.index'));
    }
}

