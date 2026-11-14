<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pasien;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Data pasien untuk staf (izin pasien.lihat). Tulis/hapus butuh izin tambahan. */
class PasienApiController extends Controller
{
    public function index(Request $request)
    {
        return Pasien::when($request->query('q'), fn ($q, $v) => $q->where('nama', 'like', "%$v%")->orWhere('no_rm', $v)->orWhere('nik', $v))
            ->latest('id_pasien')->paginate(25);
    }

    public function show(Pasien $pasien) { return ['data' => $pasien->load('konsultasi', 'resep')]; }

    public function store(Request $request)
    {
        abort_unless($request->user()->punyaIzin('pasien.daftar'), 403);
        return response()->json(['data' => Pasien::create($this->valid($request) + ['no_rm' => Pasien::nomorRmBaru()])], 201);
    }

    public function update(Request $request, Pasien $pasien)
    {
        abort_unless($request->user()->punyaIzin('pasien.daftar'), 403);
        $pasien->update($this->valid($request, $pasien));
        return ['data' => $pasien];
    }

    public function destroy(Request $request, Pasien $pasien)
    {
        abort_unless($request->user()->punyaIzin('akun.kelola'), 403, 'Hanya super admin.');
        $pasien->akun?->delete();
        $pasien->delete();
        return response()->noContent();
    }

    private function valid(Request $r, ?Pasien $p = null): array
    {
        return $r->validate([
            'nik' => ['required', 'digits:16', Rule::unique('pasien', 'nik')->ignore($p?->id_pasien, 'id_pasien')],
            'nama' => 'required|max:100', 'tgl_lahir' => 'nullable|date', 'jenis_kelamin' => 'nullable|in:L,P',
            'no_tlp' => 'nullable|max:20', 'alamat' => 'nullable|max:500',
        ]);
    }
}

