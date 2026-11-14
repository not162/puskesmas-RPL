<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Izin;
use App\Models\Peran;
use Illuminate\Http\Request;

class PeranController extends Controller
{
    public function index()
    {
        return view('admin.peran', ['peran' => Peran::with('izin')->withCount('akun')->get(), 'izin' => Izin::orderBy('kode_izin')->get()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate(['izin' => ['array'], 'izin.*' => ['array'], 'izin.*.*' => ['exists:izin,id_izin']]);
        foreach (Peran::whereNot('nama_peran', Peran::SUPER_ADMIN)->get() as $p) {
            $p->izin()->sync($data['izin'][$p->id_peran] ?? []);
        }
        return $this->kembali('success', 'Hak akses disimpan', 'Matriks izin peran diperbarui. Super Admin selalu memiliki semua izin.');
    }
}

