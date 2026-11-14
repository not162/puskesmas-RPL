<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengaturan;
use App\Models\Spesialisasi;
use Illuminate\Http\Request;

class PengaturanController extends Controller
{
    public function index()
    {
        return view('admin.pengaturan', ['nilai' => Pengaturan::semua(), 'spesialisasi' => Spesialisasi::withCount('dokter')->get()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'nama_puskesmas' => ['required', 'max:80'], 'alamat' => ['required', 'max:200'], 'telepon' => ['nullable', 'max:30'],
            'whatsapp' => ['nullable', 'regex:/^62[0-9]{8,13}$/'], 'email' => ['nullable', 'email'], 'jam_layanan' => ['nullable', 'max:120'],
            'maps_url' => ['nullable', 'url'], 'maks_hari_antrean' => ['required', 'integer', 'between:0,30'],
        ], ['whatsapp.regex' => 'Nomor WhatsApp format 62xxxxxxxxxx.']);
        $data['tolak_nama_ganda'] = $request->boolean('tolak_nama_ganda') ? '1' : '0';
        $data['tolak_kk_ganda'] = $request->boolean('tolak_kk_ganda') ? '1' : '0';
        foreach ($data as $k => $v) {
            Pengaturan::updateOrCreate(['kunci' => $k], ['nilai' => $v]);
        }
        return $this->kembali('success', 'Pengaturan disimpan', 'Perubahan langsung tampil di seluruh halaman.');
    }
}

