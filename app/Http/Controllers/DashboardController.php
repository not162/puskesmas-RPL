<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Models\Antrian;
use App\Models\Konsultasi;
use App\Models\Obat;
use App\Models\Pasien;
use App\Models\Peran;
use App\Models\Resep;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $akun = $request->user()->load('peran');
        $hariIni = today()->toDateString();

        return match (true) {
            $akun->hasRole(Peran::PASIEN) => $this->pasien($akun),
            $akun->isDokter() => view('dashboard.dokter', [
                'dokter' => $d = $akun->dokter->load('spesialisasi'),
                'antrianHariIni' => Antrian::whereDate('tanggal', $hariIni)->where('id_spesialisasi', $d->id_spesialisasi)->whereNot('status', 'batal')->count(),
                'siapPeriksa' => Antrian::whereDate('tanggal', $hariIni)->where('id_spesialisasi', $d->id_spesialisasi)->whereIn('status', ['hadir', 'dipanggil'])->count(),
                'konsultasiMenunggu' => Konsultasi::where('id_spesialisasi', $d->id_spesialisasi)->where('jenis', 'online')->where('status', 'menunggu')->count(),
                'resepBulanIni' => Resep::where('id_dokter', $d->id_dokter)->whereMonth('tgl_resep', now()->month)->whereYear('tgl_resep', now()->year)->count(),
            ]),
            $akun->hasRole(Peran::APOTEKER) => view('dashboard.apoteker', [
                'baru' => Resep::where('status', 'baru')->count(),
                'diproses' => Resep::where('status', 'diproses')->count(),
                'diserahkanHariIni' => Resep::where('status', 'diserahkan')->whereHas('penyerahan', fn ($q) => $q->whereDate('waktu', $hariIni))->count(),
                'stokMenipis' => Obat::where('stok', '<', 20)->orderBy('stok')->get(),
            ]),
            $akun->hasRole(Peran::LOKET) => view('dashboard.loket', [
                'perStatus' => Antrian::whereDate('tanggal', $hariIni)->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status'),
                'terbaru' => Antrian::with('spesialisasi', 'pasien')->whereDate('tanggal', $hariIni)->latest('id_antrian')->limit(8)->get(),
            ]),
            default => view('dashboard.admin', [
                'stat' => [
                    'Pasien terdaftar' => Pasien::count(),
                    'Antrian hari ini' => Antrian::whereDate('tanggal', $hariIni)->whereNot('status', 'batal')->count(),
                    'Konsultasi menunggu' => Konsultasi::where('status', 'menunggu')->count(),
                    'Resep belum diserahkan' => Resep::whereIn('status', ['baru', 'diproses'])->count(),
                    'Akun staf' => Akun::whereHas('peran', fn ($q) => $q->whereNot('nama_peran', Peran::PASIEN))->count(),
                    'Obat stok < 20' => Obat::where('stok', '<', 20)->count(),
                ],
                'tujuhHari' => collect(range(6, 0))->mapWithKeys(fn ($i) => [
                    now()->subDays($i)->format('d/m') => Antrian::whereDate('tanggal', now()->subDays($i)->toDateString())->whereNot('status', 'batal')->count(),
                ]),
            ]),
        };
    }

    private function pasien(Akun $akun)
    {
        $p = $akun->pasien;
        return view('dashboard.pasien', [
            'pasien' => $p,
            'antrianAktif' => $p->antrian()->with('spesialisasi')->whereIn('status', ['menunggu', 'hadir', 'dipanggil', 'dilayani'])->whereDate('tanggal', '>=', today())->orderBy('tanggal')->get(),
            'konsultasi' => $p->konsultasi()->with('spesialisasi', 'dokter')->latest('id_konsultasi')->limit(3)->get(),
            'resep' => $p->resep()->latest('id_resep')->limit(3)->get(),
        ]);
    }
}

