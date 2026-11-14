<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengumuman;
use Illuminate\Http\Request;

class PengumumanController extends Controller
{
    public function index() { return view('admin.pengumuman.index', ['pengumuman' => Pengumuman::latest('tanggal')->paginate(15)]); }
    public function create() { return view('admin.pengumuman.form', ['p' => new Pengumuman(['tanggal' => today(), 'terbit' => true])]); }

    public function store(Request $request)
    {
        Pengumuman::create($this->valid($request));
        return $this->kembali('success', 'Pengumuman dibuat', 'Tampil di beranda publik bila diterbitkan.', route('admin.pengumuman.index'));
    }

    public function edit(Pengumuman $pengumuman) { return view('admin.pengumuman.form', ['p' => $pengumuman]); }

    public function update(Request $request, Pengumuman $pengumuman)
    {
        $pengumuman->update($this->valid($request));
        return $this->kembali('success', 'Pengumuman diperbarui', $pengumuman->judul, route('admin.pengumuman.index'));
    }

    public function destroy(Pengumuman $pengumuman)
    {
        $pengumuman->delete();
        return $this->kembali('success', 'Pengumuman dihapus', $pengumuman->judul);
    }

    private function valid(Request $r): array
    {
        return $r->validate([
            'judul' => ['required', 'max:150'], 'isi' => ['required'], 'kategori' => ['required', 'in:pengumuman,agenda,edukasi'],
            'tanggal' => ['required', 'date'],
        ]) + ['terbit' => $r->boolean('terbit')];
    }
}

