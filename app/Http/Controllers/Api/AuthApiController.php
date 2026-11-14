<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Controller;
use App\Models\Akun;
use App\Models\LogLogin;
use App\Models\OtpKode;
use App\Models\Peran;
use App\Support\CekDuplikat;
use App\Support\Otp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Login & registrasi API.
 *  - Pasien: login dan registrasi dua langkah (kode OTP email).
 *  - Petugas: login langsung mendapat token.
 */
class AuthApiController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate(['login' => 'required|max:100', 'password' => 'required', 'device' => 'nullable|max:60']);
        $login = strtolower(trim($data['login']));
        $akun = Akun::with('peran')->where(filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username', $login)->first();
        if ($akun?->terkunci()) {
            return response()->json(['message' => 'Akun terkunci sementara sampai '.$akun->terkunci_sampai->format('H:i').'.'], 423);
        }
        $ok = $akun && $akun->aktif() && Hash::check($data['password'], $akun->password);
        LogLogin::create(['id_akun' => $akun?->id_akun, 'email_dicoba' => $login, 'waktu' => now(), 'hasil' => $ok ? 'berhasil' : 'gagal', 'ip' => $request->ip()]);
        if (! $ok) {
            if ($akun && $akun->aktif()) AuthController::catatGagal($akun);
            throw ValidationException::withMessages(['login' => 'Username/email atau password salah, atau akun nonaktif.']);
        }
        $akun->forceFill(['gagal_login' => 0, 'terkunci_sampai' => null])->save();
        if (AuthController::wajibOtpPerangkat($akun)) {
            $otp = Otp::kirim($akun->email, 'login', ['device' => $data['device'] ?? 'api'], $akun->id_akun);
            return response()->json(['message' => 'Kode OTP dikirim ke email.', 'otp_ref' => $otp->ref, 'email' => $otp->emailSamaran()], 202);
        }
        return $this->token($akun, $data['device'] ?? 'api');
    }

    public function verifikasiLogin(Request $request)
    {
        $data = $request->validate(['otp_ref' => 'required|string', 'kode' => 'required|digits:6']);
        $otp = Otp::verifikasi(OtpKode::where('ref', $data['otp_ref'])->where('tujuan', 'login')->first(), $data['kode']);
        return $this->token(Akun::with('peran')->findOrFail($otp->id_akun), $otp->payload['device'] ?? 'api');
    }

    public function register(Request $request)
    {
        $ganda = CekDuplikat::cari($request->only('username', 'email', 'nik', 'nama', 'no_kk'));
        if ($ganda) {
            return response()->json(['message' => CekDuplikat::pesan($ganda).' sudah terdaftar. Silakan login atau gunakan lupa password.', 'terdaftar' => $ganda], 409);
        }
        $data = $request->validate([
            'nik' => 'required|digits:16', 'no_kk' => 'required|digits:16', 'nama' => 'required|max:100', 'tgl_lahir' => 'required|date|before:today',
            'jenis_kelamin' => 'required|in:L,P', 'no_tlp' => ['required', 'regex:/^(\+62|62|0)8[0-9]{8,12}$/'], 'alamat' => 'required|max:500',
            'username' => ['required', 'regex:/^[a-z0-9._]{4,30}$/'], 'email' => 'required|email:rfc|max:100',
            'no_bpjs' => 'nullable|digits:13|unique:pasien,no_bpjs', 'password' => ['required', 'confirmed', Password::min(8)],
        ]);
        $payload = collect($data)->except('password', 'password_confirmation')->all();
        $payload['username'] = strtolower($payload['username']);
        $payload['email'] = strtolower($payload['email']);
        $payload['password'] = Hash::make($data['password']);
        $payload['wa_opt_in'] = $request->boolean('wa_opt_in');
        if (! config('otp.registrasi')) {
            $pasien = AuthController::buatPasienDariPayload($payload);
            if (! $pasien) return response()->json(['message' => 'Data sudah terdaftar. Silakan login atau gunakan lupa password.'], 409);
            return $this->token($pasien->akun->load('peran'), 'api', 201);
        }
        $otp = Otp::kirim($payload['email'], 'registrasi', $payload);
        return response()->json(['message' => 'Kode OTP dikirim ke email.', 'otp_ref' => $otp->ref, 'email' => $otp->emailSamaran()], 202);
    }

    public function verifikasiRegister(Request $request)
    {
        $data = $request->validate(['otp_ref' => 'required|string', 'kode' => 'required|digits:6']);
        $otp = Otp::verifikasi(OtpKode::where('ref', $data['otp_ref'])->where('tujuan', 'registrasi')->first(), $data['kode']);
        $pasien = AuthController::buatPasienDariPayload($otp->payload);
        if (! $pasien) {
            return response()->json(['message' => 'Data sudah terdaftar. Silakan login atau gunakan lupa password.'], 409);
        }
        return $this->token($pasien->akun->load('peran'), 'api', 201);
    }

    public function me(Request $request) { return ['akun' => $this->profil($request->user()->load('peran'))]; }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();
        return ['message' => 'Token dicabut.'];
    }

    private function token(Akun $akun, string $device, int $status = 200)
    {
        return response()->json(['token' => $akun->createToken($device, $akun->daftarIzin() ?: ['*'])->plainTextToken, 'akun' => $this->profil($akun)], $status);
    }

    private function profil(Akun $a): array
    {
        return ['username' => $a->username, 'email' => $a->email, 'nama' => $a->namaTampil(), 'peran' => $a->role(), 'label_peran' => $a->peran->label, 'izin' => $a->role() === Peran::SUPER_ADMIN ? ['*'] : $a->daftarIzin()];
    }
}
