<?php

namespace App\Http\Controllers\Loket;

use App\Http\Controllers\Controller;
use App\Models\Antrian;
use App\Models\Spesialisasi;
use App\Support\Notifier;
use Illuminate\Http\Request;

class AntrianController extends Controller
{
    public function index(Request $request)
    {
        $tgl = $request->query('tanggal', today()->toDateString());
        return view('loket.antrian', [
            'tanggal' => $tgl,
            'spesialisasi' => Spesialisasi::all(),
            'antrian' => Antrian::with('pasien', 'spesialisasi')->whereDate('tanggal', $tgl)
                ->when($request->query('poli'), fn ($q, $v) => $q->where('id_spesialisasi', $v))
                ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
                ->when($request->query('q'), fn ($q, $v) => $q->where(fn ($w) => $w->where('nomor', 'like', "%$v%")->orWhere('kode', $v)->orWhereHas('pasien', fn ($p) => $p->where('nama', 'like', "%$v%")->orWhere('no_rm', $v))))
                ->orderBy('id_antrian')->paginate(25)->withQueryString(),
        ]);
    }

    public function checkin(Antrian $antrian)
    {
        if ($antrian->status !== 'menunggu') {
            return $this->kembali('warning', 'Check-in gagal', "Status antrian {$antrian->nomor} saat ini: {$antrian->labelStatus()}.");
        }
        if (! $antrian->tanggal->isToday()) {
            return $this->kembali('warning', 'Bukan jadwal hari ini', "Antrian {$antrian->nomor} dijadwalkan {$antrian->tanggal->format('d/m/Y')}.");
        }
        $antrian->update(['status' => 'hadir', 'waktu_hadir' => now()]);
        Notifier::pasien($antrian->pasien, 6, [], route('pasien.antrian.show', $antrian), 'success');
        Notifier::peran(Notifier::peranDokterUntuk($antrian->spesialisasi->nama), 'Pasien siap diperiksa', "Antrian {$antrian->nomor} sudah check-in.", route('dokter.antrian'));
        return $this->kembali('success', 'Check-in berhasil', "Antrian {$antrian->nomor} ({$antrian->pasien->nama}) sudah hadir.");
    }

    public function panggil(Antrian $antrian)
    {
        if (! in_array($antrian->status, ['hadir', 'dipanggil'])) {
            return $this->kembali('warning', 'Belum check-in', 'Pasien harus check-in sebelum dipanggil.');
        }
        $antrian->update(['status' => 'dipanggil', 'waktu_panggil' => now()]);
        Notifier::pasien($antrian->pasien, 7, ['nomor' => $antrian->nomor, 'poli' => $antrian->spesialisasi->poli], route('pasien.antrian.show', $antrian), 'warning');
        return $this->kembali('info', 'Dipanggil', "Nomor {$antrian->nomor} ditampilkan di layar antrian.");
    }

    public function batal(Antrian $antrian)
    {
        if (in_array($antrian->status, ['selesai', 'batal', 'dilayani'])) {
            return $this->kembali('warning', 'Tidak bisa dibatalkan', 'Antrian sudah diproses.');
        }
        $antrian->update(['status' => 'batal']);
        Notifier::pasien($antrian->pasien, 5, [], route('pasien.antrian.index'), 'danger', "Antrian {$antrian->nomor} dibatalkan oleh petugas loket.");
        return $this->kembali('success', 'Dibatalkan', "Antrian {$antrian->nomor} dibatalkan.");
    }
}

