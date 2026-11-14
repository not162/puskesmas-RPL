<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dokter;
use App\Models\Pengumuman;
use App\Support\AntrianLive;

class PublicApiController extends Controller
{
    public function antrian() { return response()->json(AntrianLive::data()); }

    public function dokter()
    {
        return ['data' => Dokter::with('spesialisasi', 'jadwalPraktik')->orderBy('nama')->get()
            ->map(fn ($d) => ['nama' => $d->nama, 'spesialisasi' => $d->spesialisasi->nama, 'poli' => $d->spesialisasi->poli, 'jadwal' => $d->ringkasJadwal()])];
    }

    public function pengumuman()
    {
        return ['data' => Pengumuman::where('terbit', true)->latest('tanggal')->limit(20)->get(['judul', 'isi', 'kategori', 'tanggal'])];
    }

    public function alur() { return ['data' => array_values(config('alur.langkah'))]; }
}

