<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pengumuman;
use Illuminate\Http\Request;

class PengumumanApiController extends Controller
{
    public function index() { return Pengumuman::latest('tanggal')->paginate(20); }
    public function show(Pengumuman $pengumuman) { return ['data' => $pengumuman]; }
    public function store(Request $request) { return response()->json(['data' => Pengumuman::create($this->valid($request))], 201); }

    public function update(Request $request, Pengumuman $pengumuman)
    {
        $pengumuman->update($this->valid($request));
        return ['data' => $pengumuman];
    }

    public function destroy(Pengumuman $pengumuman)
    {
        $pengumuman->delete();
        return response()->noContent();
    }

    private function valid(Request $r): array
    {
        return $r->validate(['judul' => 'required|max:150', 'isi' => 'required', 'kategori' => 'required|in:pengumuman,agenda,edukasi', 'tanggal' => 'required|date', 'terbit' => 'boolean']);
    }
}

