<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dokter;
use App\Models\JadwalPraktik;
use Illuminate\Http\Request;

class JadwalController extends Controller
{
    public function index()
    {
        return view('admin.jadwal', [
            'dokter' => Dokter::with('spesialisasi', 'jadwalPraktik')->orderBy('id_spesialisasi')->orderBy('nama')->get(),
        ]);
    }

    public function store(Request $request)
    {
        JadwalPraktik::create($this->valid($request) + ['aktif' => true]);
        return $this->kembali('success', 'Jadwal ditambahkan', 'Jadwal tampil di halaman publik.');
    }

    public function update(Request $request, JadwalPraktik $jadwal)
    {
        $jadwal->update($this->valid($request, true) + ['aktif' => $request->boolean('aktif')]);
        return $this->kembali('success', 'Jadwal diperbarui', $jadwal->label());
    }

    public function destroy(JadwalPraktik $jadwal)
    {
        $jadwal->delete();
        return $this->kembali('success', 'Jadwal dihapus', $jadwal->label());
    }

    private function valid(Request $r, bool $ubah = false): array
    {
        return $r->validate([
            'id_dokter' => [$ubah ? 'sometimes' : 'required', 'exists:dokter,id_dokter'],
            'hari' => ['required', 'integer', 'between:1,7'],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i', 'after:jam_mulai'],
            'kuota' => ['required', 'integer', 'min:1', 'max:200'],
        ]);
    }
}

