<?php

namespace App\Http\Controllers\Farmasi;

use App\Http\Controllers\Controller;
use App\Models\Obat;
use App\Support\RekomendasiObat;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** CRUD formularium obat — izin obat.kelola (Apoteker, Super Admin). Perubahan tersiar real-time (kanal "obat"). */
class ObatController extends Controller
{
    public function index(Request $request)
    {
        return view('farmasi.obat.index', [
            'kategori' => config('formularium.kategori'),
            'obat' => $this->query($request)->paginate(25)->withQueryString(),
            'menipis' => Obat::where('aktif', true)->whereColumn('stok', '<=', 'stok_minimum')->count(),
        ]);
    }

    /** Formularium baca-saja untuk dokter & petugas berizin obat.lihat. */
    public function formularium(Request $request)
    {
        return view('farmasi.obat.formularium', [
            'kategori' => config('formularium.kategori'),
            'kelompok' => $this->query($request)->where('aktif', true)->get()->groupBy('kategori'),
        ]);
    }

    private function query(Request $r)
    {
        return Obat::query()
            ->when($r->query('q'), fn ($q, $v) => $q->where(fn ($w) => $w->where('nama_obat', 'like', "%$v%")->orWhere('kode', 'like', "%$v%")->orWhere('zat_aktif', 'like', "%$v%")->orWhere('indikasi', 'like', "%$v%")))
            ->when($r->query('kategori'), fn ($q, $v) => $q->where('kategori', $v))
            ->when($r->query('poli'), fn ($q, $v) => $q->whereIn('poli', [$v, 'semua']))
            ->when($r->query('stok') === 'menipis', fn ($q) => $q->whereColumn('stok', '<=', 'stok_minimum'))
            ->orderByRaw('kode is null')->orderBy('kode')->orderBy('nama_obat');
    }

    public function create()
    {
        return view('farmasi.obat.form', ['obat' => new Obat(['kode' => Obat::kodeBerikut(), 'poli' => 'umum', 'keamanan_hamil' => 'konsultasi', 'satuan' => 'tablet', 'bentuk' => 'Tablet', 'jumlah_bawaan' => 10, 'stok_minimum' => 20, 'aktif' => true])]);
    }

    public function store(Request $request)
    {
        $o = Obat::create($this->valid($request));
        RekomendasiObat::lupakanGraf();
        return $this->kembali('success', 'Obat ditambahkan', "{$o->kode} {$o->nama_obat} tersimpan dan langsung muncul di pemilih obat dokter.", route('obat.index'));
    }

    public function edit(Obat $obat) { return view('farmasi.obat.form', compact('obat')); }

    public function update(Request $request, Obat $obat)
    {
        $obat->update($this->valid($request, $obat));
        RekomendasiObat::lupakanGraf();
        return $this->kembali('success', 'Obat diperbarui', "{$obat->kode} {$obat->nama_obat} tersimpan.", route('obat.index'));
    }

    public function destroy(Obat $obat)
    {
        if ($obat->newQuery()->getConnection()->table('detail_resep')->where('id_obat', $obat->id_obat)->exists()) {
            return $this->kembali('warning', 'Tidak dapat dihapus', 'Obat sudah tercatat di resep pasien. Nonaktifkan saja agar tidak bisa dipilih lagi.');
        }
        $obat->delete();
        RekomendasiObat::lupakanGraf();
        return $this->kembali('success', 'Obat dihapus', "{$obat->nama_obat} dihapus.");
    }

    public static function aturan(?Obat $o = null): array
    {
        return [
            'kode' => ['required', 'regex:/^OBT-\d{3,4}$/', Rule::unique('obat', 'kode')->ignore($o?->id_obat, 'id_obat')],
            'nama_obat' => ['required', 'max:100'],
            'kategori' => ['required', Rule::in(config('formularium.kategori'))],
            'bentuk' => ['required', Rule::in(Obat::BENTUK)],
            'zat_aktif' => ['nullable', 'max:80'],
            'golongan' => ['nullable', Rule::in(array_keys(Obat::GOLONGAN))],
            'poli' => ['required', Rule::in(array_keys(Obat::POLI))],
            'keamanan_hamil' => ['required', Rule::in(array_keys(Obat::KEAMANAN_HAMIL))],
            'kegunaan' => ['nullable', 'max:160'],
            'indikasi' => ['nullable', 'max:1000'],
            'satuan' => ['required', 'max:20'],
            'jumlah_bawaan' => ['required', 'integer', 'min:1', 'max:500'],
            'aturan_bawaan' => ['nullable', 'max:100'],
            'catatan' => ['nullable', 'max:255'],
            'stok' => ['required', 'integer', 'min:0'],
            'stok_minimum' => ['required', 'integer', 'min:0'],
        ];
    }

    private function valid(Request $r, ?Obat $o = null): array
    {
        $r->merge(['kode' => strtoupper(trim((string) $r->input('kode')))]);
        return $r->validate(self::aturan($o), ['kode.regex' => 'Format kode: OBT-001.'], ['nama_obat' => 'nama obat', 'jumlah_bawaan' => 'jumlah bawaan', 'aturan_bawaan' => 'aturan pakai bawaan', 'keamanan_hamil' => 'keamanan kehamilan'])
            + ['aktif' => $r->boolean('aktif')];
    }
}
