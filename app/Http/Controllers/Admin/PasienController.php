<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pasien;
use Illuminate\Http\Request;

class PasienController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.pasien', [
            'pasien' => Pasien::with('akun')->withCount('antrian', 'resep')
                ->when($request->query('q'), fn ($q, $v) => $q->where('nama', 'like', "%$v%")->orWhere('nik', $v)->orWhere('no_rm', $v))
                ->latest('id_pasien')->paginate(20)->withQueryString(),
        ]);
    }

    public function destroy(Pasien $pasien)
    {
        $nama = $pasien->nama;
        $pasien->akun?->delete();
        $pasien->delete();
        return $this->kembali('success', 'Pasien dihapus', "Data {$nama} beserta riwayatnya dihapus.");
    }
}

