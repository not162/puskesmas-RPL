<?php

namespace App\Http\Controllers\Farmasi;

use App\Http\Controllers\Controller;
use App\Models\Obat;
use Illuminate\Http\Request;

class ObatController extends Controller
{
    public function index(Request $request)
    {
        return view('farmasi.obat.index', [
            'obat' => Obat::when($request->query('q'), fn ($q, $v) => $q->where('nama_obat', 'like', "%$v%"))
                ->orderBy('nama_obat')->paginate(20)->withQueryString(),
        ]);
    }

    public function create() { return view('farmasi.obat.form', ['obat' => new Obat]); }

    public function store(Request $request)
    {
        $o = Obat::create($this->valid($request));
        return $this->kembali('success', 'Obat ditambahkan', "{$o->nama_obat} tersimpan.", route('obat.index'));
    }

    public function edit(Obat $obat) { return view('farmasi.obat.form', compact('obat')); }

    public function update(Request $request, Obat $obat)
    {
        $obat->update($this->valid($request));
        return $this->kembali('success', 'Obat diperbarui', "{$obat->nama_obat} tersimpan.", route('obat.index'));
    }

    public function destroy(Obat $obat)
    {
        if ($obat->newQuery()->getConnection()->table('detail_resep')->where('id_obat', $obat->id_obat)->exists()) {
            return $this->kembali('warning', 'Tidak dapat dihapus', 'Obat sudah tercatat di resep. Ubah stok menjadi 0 sebagai gantinya.');
        }
        $obat->delete();
        return $this->kembali('success', 'Obat dihapus', "{$obat->nama_obat} dihapus.");
    }

    private function valid(Request $r): array
    {
        return $r->validate(['nama_obat' => ['required', 'max:100'], 'satuan' => ['required', 'max:20'], 'stok' => ['required', 'integer', 'min:0']]);
    }
}

