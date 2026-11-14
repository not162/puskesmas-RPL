<?php

namespace App\Http\Controllers\Pasien;

use App\Http\Controllers\Controller;
use App\Models\Pengaduan;
use App\Models\Peran;
use App\Support\Notifier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PengaduanController extends Controller
{
    public function index(Request $request)
    {
        return view('pasien.pengaduan', [
            'pengaduan' => Pengaduan::where('id_akun', $request->user()->id_akun)->latest()->paginate(10),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'kategori' => ['required', Rule::in(array_keys(Pengaduan::KATEGORI))],
            'judul' => ['required', 'max:150'],
            'isi' => ['required', 'min:10', 'max:3000'],
        ]);
        $p = Pengaduan::create($data + ['id_akun' => $request->user()->id_akun, 'status' => 'baru']);
        Notifier::peran([Peran::LOKET, Peran::ADMIN, Peran::SUPER_ADMIN], 'Pengaduan baru', "[{$p->kategori}] {$p->judul}", route('pengaduan.show', $p), 'warning');
        return $this->kembali('success', 'Pengaduan terkirim', 'Petugas akan menindaklanjuti. Anda mendapat notifikasi saat ditanggapi.');
    }
}

