<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Akun;
use App\Models\Apoteker;
use App\Models\Dokter;
use App\Models\Peran;
use App\Models\PetugasLoket;
use App\Models\Spesialisasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** Super admin: CRUD akun staf (dokter umum/kandungan, apoteker, loket, super admin) + status akun pasien. */
class AkunController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.akun.index', [
            'peran' => Peran::all(),
            'akun' => Akun::with('peran', 'dokter.spesialisasi', 'apoteker', 'admin', 'petugasLoket', 'pasien')
                ->when($request->query('peran'), fn ($q, $v) => $q->whereHas('peran', fn ($p) => $p->where('nama_peran', $v)))
                ->when($request->query('q'), fn ($q, $v) => $q->where(fn ($w) => $w->where('email', 'like', "%$v%")->orWhere('username', 'like', "%$v%")))
                ->latest('id_akun')->paginate(20)->withQueryString(),
        ]);
    }

    public function create()
    {
        return view('admin.akun.form', ['akun' => new Akun, 'peran' => Peran::whereNot('nama_peran', Peran::PASIEN)->get()]);
    }

    public function store(Request $request)
    {
        $data = $this->valid($request);
        DB::transaction(function () use ($data, $request) {
            $peran = Peran::findOrFail($data['id_peran']);
            $akun = Akun::create(['id_peran' => $peran->id_peran, 'username' => $data['username'], 'email' => $data['email'], 'password' => $data['password'], 'status' => $data['status']]);
            $this->simpanProfil($akun, $peran, $data, $request);
        });
        return $this->kembali('success', 'Akun dibuat', "{$data['nama']} ({$data['email']}) ditambahkan.", route('admin.akun.index'));
    }

    public function edit(Akun $akun)
    {
        abort_if($akun->hasRole(Peran::PASIEN), 404);
        return view('admin.akun.form', ['akun' => $akun->load('dokter', 'apoteker'), 'peran' => Peran::whereNot('nama_peran', Peran::PASIEN)->get()]);
    }

    public function update(Request $request, Akun $akun)
    {
        abort_if($akun->hasRole(Peran::PASIEN), 404);
        $data = $this->valid($request, $akun);
        DB::transaction(function () use ($akun, $data, $request) {
            $akun->fill(['username' => $data['username'], 'email' => $data['email'], 'status' => $data['status']]);
            if (! empty($data['password'])) $akun->password = $data['password'];
            $akun->save();
            $this->simpanProfil($akun, $akun->peran, $data, $request);
        });
        return $this->kembali('success', 'Akun diperbarui', "{$data['email']} tersimpan.", route('admin.akun.index'));
    }

    public function status(Request $request, Akun $akun)
    {
        if ($akun->is($request->user())) {
            return $this->kembali('warning', 'Tidak diizinkan', 'Anda tidak dapat menonaktifkan akun sendiri.');
        }
        $akun->update(['status' => $akun->aktif() ? 'nonaktif' : 'aktif']);
        if (! $akun->aktif()) $akun->tokens()->delete();
        return $this->kembali('success', 'Status akun', "{$akun->email} sekarang {$akun->status}.");
    }

    public function destroy(Request $request, Akun $akun)
    {
        if ($akun->is($request->user())) {
            return $this->kembali('warning', 'Tidak diizinkan', 'Anda tidak dapat menghapus akun sendiri.');
        }
        try {
            $akun->delete();
        } catch (\Illuminate\Database\QueryException) {
            return $this->kembali('warning', 'Tidak dapat dihapus', 'Akun memiliki riwayat layanan. Nonaktifkan saja.');
        }
        return $this->kembali('success', 'Akun dihapus', "{$akun->email} dihapus.");
    }

    private function simpanProfil(Akun $akun, Peran $peran, array $d, Request $request): void
    {
        $id = ['id_akun' => $akun->id_akun];
        match ($peran->nama_peran) {
            Peran::DOKTER_UMUM, Peran::DOKTER_KANDUNGAN => Dokter::updateOrCreate($id, [
                'nama' => $d['nama'], 'no_str' => $d['no_str'], 'jadwal' => $d['jadwal'] ?? null,
                'id_spesialisasi' => Spesialisasi::where('nama', $peran->nama_peran === Peran::DOKTER_KANDUNGAN ? 'Kandungan' : 'Umum')->value('id_spesialisasi'),
                'id_admin' => $request->user()->admin?->id_admin,
            ]),
            Peran::APOTEKER => Apoteker::updateOrCreate($id, ['nama' => $d['nama'], 'no_sipa' => $d['no_sipa']]),
            Peran::LOKET => PetugasLoket::updateOrCreate($id, ['nama' => $d['nama']]),
            Peran::SUPER_ADMIN => Admin::updateOrCreate($id, ['nama' => $d['nama']]),
            default => null,
        };
    }

    private function valid(Request $request, ?Akun $akun = null): array
    {
        $peranNama = $akun?->role() ?? Peran::find($request->input('id_peran'))?->nama_peran;
        return $request->validate([
            'id_peran' => [$akun ? 'nullable' : 'required', Rule::exists('peran', 'id_peran')->whereNot('nama_peran', Peran::PASIEN)],
            'nama' => ['required', 'max:100'],
            'username' => ['required', 'regex:/^[a-z0-9._]{4,30}$/', Rule::unique('akun', 'username')->ignore($akun?->id_akun, 'id_akun')],
            'email' => ['required', 'email', 'max:100', Rule::unique('akun', 'email')->ignore($akun?->id_akun, 'id_akun')],
            'password' => [$akun ? 'nullable' : 'required', Password::min(8)],
            'status' => ['required', 'in:aktif,nonaktif'],
            'no_str' => [in_array($peranNama, [Peran::DOKTER_UMUM, Peran::DOKTER_KANDUNGAN]) ? 'required' : 'nullable', 'max:30'],
            'jadwal' => ['nullable', 'max:100'],
            'no_sipa' => [$peranNama === Peran::APOTEKER ? 'required' : 'nullable', 'max:30'],
        ], [], ['no_str' => 'No. STR', 'no_sipa' => 'No. SIPA', 'id_peran' => 'peran']);
    }
}

