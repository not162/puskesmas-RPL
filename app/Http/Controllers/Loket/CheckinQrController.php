<?php

namespace App\Http\Controllers\Loket;

use App\Http\Controllers\Controller;
use App\Models\Antrian;
use App\Models\AuditLog;
use App\Models\Pasien;
use App\Models\Spesialisasi;
use App\Support\Hmac;
use App\Support\Notifier;
use Illuminate\Http\Request;

class CheckinQrController extends Controller
{
    public function form()
    {
        return view('loket.checkin-qr', [
            'terakhir' => Antrian::with('pasien', 'spesialisasi')->whereDate('tanggal', today())->whereNotNull('waktu_hadir')->latest('waktu_hadir')->limit(8)->get(),
        ]);
    }

    /** Verifikasi tanda tangan HMAC pada QR tiket lalu check-in. */
    public function proses(Request $request)
    {
        $qr = (string) $request->validate(['qr' => ['required', 'string', 'max:120']])['qr'];
        $a = Hmac::verifikasiQr($qr);
        if (! $a) {
            AuditLog::catat('qr_tolak', 'antrian', ['qr' => substr($qr, 0, 40)]);
            return $this->kembali('danger', 'QR tidak valid', 'Tanda tangan tiket tidak cocok (palsu/rusak). Periksa KTP dan cari manual.');
        }
        return $this->checkin($a);
    }

    private function checkin(Antrian $a)
    {
        $kembali = fn (...$x) => $this->kembali(...$x);
        if (! $a->tanggal->isToday()) {
            return $kembali('warning', 'Bukan jadwal hari ini', "Antrean {$a->nomor} dijadwalkan {$a->tanggal->format('d/m/Y')}.");
        }
        if ($a->status !== 'menunggu') {
            return $kembali('warning', 'Sudah diproses', "Antrean {$a->nomor}: {$a->labelStatus()}.");
        }
        $a->update(['status' => 'hadir', 'waktu_hadir' => now()]);
        Notifier::pasien($a->pasien, 6, [], route('pasien.antrian.show', $a), 'success');
        Notifier::peran(Notifier::peranDokterUntuk($a->spesialisasi->nama), 'Pasien siap diperiksa', "Antrean {$a->nomor} sudah check-in.", route('dokter.antrian'));
        return $kembali('success', 'Check-in berhasil', "{$a->nomor} · {$a->pasien->nama} · {$a->spesialisasi->poli}");
    }

    /** Halaman "Pendaftaran": cari pasien (NIK / No. RM / BPJS) lalu daftarkan ke poli. */
    public function pendaftaran(Request $request)
    {
        $q = trim((string) $request->query('q'));
        return view('loket.pendaftaran', [
            'q' => $q,
            'hasil' => $q === '' ? collect() : Pasien::where('nik', $q)->orWhere('no_rm', $q)->orWhere('no_bpjs', $q)->orWhere('nama', 'like', "%$q%")->limit(10)->get(),
            'spesialisasi' => Spesialisasi::all(),
            'hariIni' => Antrian::with('pasien', 'spesialisasi')->whereDate('tanggal', today())->where('sumber', 'loket')->latest('id_antrian')->limit(10)->get(),
        ]);
    }
}

