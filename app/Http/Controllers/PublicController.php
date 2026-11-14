<?php

namespace App\Http\Controllers;

use App\Models\Dokter;
use App\Models\Obat;
use App\Models\Pengumuman;
use App\Models\Spesialisasi;
use App\Support\AntrianLive;

class PublicController extends Controller
{
    public function beranda()
    {
        return view('public.beranda', [
            'pengumuman' => Pengumuman::where('terbit', true)->latest('tanggal')->limit(6)->get(),
            'spesialisasi' => Spesialisasi::withCount('dokter')->get(),
            'dokter' => $this->dokterPublik(),
            // Angka ringkasan saja (aman untuk publik)
            'angka' => [
                'pasien' => \App\Models\Pasien::count(),
                'dokter' => Dokter::count(),
                'dilayani' => \App\Models\Antrian::where('status', 'selesai')->whereMonth('tanggal', now()->month)->whereYear('tanggal', now()->year)->count(),
                'info' => Pengumuman::where('terbit', true)->count(),
            ],
        ]);
    }

    public function layarAntrian()
    {
        return view('public.layar-antrian');
    }

    public function liveAntrian()
    {
        return response()->json(AntrianLive::data())->header('Cache-Control', 'no-store');
    }

    public function dokter()
    {
        return view('public.dokter', ['dokter' => $this->dokterPublik()]);
    }

    public function alur()
    {
        return view('public.alur', ['langkah' => config('alur.langkah')]);
    }

    public function info()
    {
        return view('public.info', ['pengumuman' => Pengumuman::where('terbit', true)->latest('tanggal')->paginate(9)]);
    }

    public function stats()
    {
        return response()->json([
            'dokter' => Dokter::count(),
            'poli' => Spesialisasi::count(),
            'obat_tersedia' => Obat::where('stok', '>', 0)->count(),
            'antrian_hari_ini' => collect(AntrianLive::data()['poli'])->sum('total'),
        ]);
    }

    /** Profil dokter versi publik: tanpa id_dokter, no_str, email. */
    private function dokterPublik()
    {
        return Dokter::with('spesialisasi', 'jadwalPraktik')->orderBy('nama')->get()
            ->map(function (Dokter $d) {
                $hariIni = $d->jadwalPraktik->where('aktif', true)->firstWhere('hari', now()->dayOfWeekIso);
                return ['nama' => $d->nama, 'spesialisasi' => $d->spesialisasi->nama, 'poli' => $d->spesialisasi->poli, 'jadwal' => $d->ringkasJadwal(),
                    'hari_ini' => $hariIni ? substr($hariIni->jam_mulai, 0, 5).'–'.substr($hariIni->jam_selesai, 0, 5) : null];
            });
    }
}

