<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Akun;
use App\Models\LogLogin;
use App\Models\Pasien;
use App\Models\Peran;
use App\Support\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthApiController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate(['login' => 'required|max:100', 'password' => 'required', 'device' => 'nullable|max:60']);
        $login = strtolower(trim($data['login']));
        $akun = Akun::with('peran')->where(filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username', $login)->first();
        $ok = $akun && $akun->aktif() && Hash::check($data['password'], $akun->password);
        LogLogin::create(['id_akun' => $akun?->id_akun, 'email_dicoba' => $login, 'waktu' => now(), 'hasil' => $ok ? 'berhasil' : 'gagal', 'ip' => $request->ip()]);
        if (! $ok) {
            throw ValidationException::withMessages(['login' => 'Username/email atau password salah, atau akun nonaktif.']);
        }
        return ['token' => $akun->createToken($data['device'] ?? 'api', $akun->daftarIzin() ?: ['*'])->plainTextToken, 'akun' => $this->profil($akun)];
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'nik' => 'required|digits:16|unique:pasien,nik', 'nama' => 'required|max:100', 'tgl_lahir' => 'required|date|before:today',
            'jenis_kelamin' => 'required|in:L,P', 'no_tlp' => ['required', 'regex:/^(\+62|0)8[0-9]{8,12}$/'], 'alamat' => 'required|max:500',
            'username' => ['required', 'regex:/^[a-z0-9._]{4,30}$/', 'unique:akun,username'], 'email' => 'nullable|email|unique:akun,email',
            'no_bpjs' => 'nullable|digits:13|unique:pasien,no_bpjs', 'password' => ['required', 'confirmed', Password::min(8)],
        ]);
        $pasien = DB::transaction(function () use ($data) {
            $akun = Akun::create(['id_peran' => Peran::where('nama_peran', Peran::PASIEN)->value('id_peran'), 'username' => $data['username'], 'email' => $data['email'] ?? null, 'password' => $data['password']]);
            return Pasien::create(collect($data)->except('username', 'email', 'password', 'password_confirmation')->all() + ['id_akun' => $akun->id_akun, 'no_rm' => Pasien::nomorRmBaru()]);
        });
        Notifier::pasien($pasien, 1, ['no_rm' => $pasien->no_rm], null, 'success');
        $akun = $pasien->akun->load('peran');
        return response()->json(['token' => $akun->createToken('api')->plainTextToken, 'akun' => $this->profil($akun)], 201);
    }

    public function me(Request $request) { return ['akun' => $this->profil($request->user()->load('peran'))]; }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();
        return ['message' => 'Token dicabut.'];
    }

    private function profil(Akun $a): array
    {
        return ['username' => $a->username, 'email' => $a->email, 'nama' => $a->namaTampil(), 'peran' => $a->role(), 'label_peran' => $a->peran->label, 'izin' => $a->role() === Peran::SUPER_ADMIN ? ['*'] : $a->daftarIzin()];
    }
}

