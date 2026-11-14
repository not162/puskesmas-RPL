<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Models\AuditLog;
use App\Models\LogLogin;
use App\Models\Pasien;
use App\Models\Peran;
use App\Support\Notifier;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as AturanSandi;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /** Soal hitung sederhana sebagai verifikasi keamanan (pengganti Turnstile saat offline). */
    public static function captcha(): string
    {
        $a = random_int(2, 9);
        $b = random_int(1, 9);
        [$soal, $jawab] = random_int(0, 1) ? ["$a + $b", $a + $b] : ["$a × $b", $a * $b];
        session(['captcha' => $jawab]);
        return $soal;
    }

    /** Layout C — gateway login utama untuk SEMUA peran. */
    public function formLogin()
    {
        return view('auth.login', ['soal' => self::captcha()]);
    }

    /** Layout B — kartu login ringkas di alur pasien (endpoint sama). */
    public function formLoginPasien()
    {
        return view('auth.login-pasien', ['soal' => self::captcha()]);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string'],
            'captcha' => ['required', 'integer'],
        ], [], ['login' => 'username atau email', 'password' => 'password', 'captcha' => 'verifikasi keamanan']);

        $benar = (int) session()->pull('captcha', -1) === (int) $data['captcha'];
        if (! $benar) {
            throw ValidationException::withMessages(['captcha' => 'Jawaban verifikasi keamanan salah.']);
        }

        $login = Str::lower(trim($data['login']));
        $kolom = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $kunci = $login.'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($kunci, 5)) {
            throw ValidationException::withMessages(['login' => 'Terlalu banyak percobaan. Coba lagi dalam '.RateLimiter::availableIn($kunci).' detik.']);
        }

        $akun = Akun::where($kolom, $login)->first();
        $ok = Auth::attempt([$kolom => $login, 'password' => $data['password'], 'status' => 'aktif'], $request->boolean('ingat'));
        LogLogin::create(['id_akun' => $akun?->id_akun, 'email_dicoba' => $login, 'waktu' => now(), 'hasil' => $ok ? 'berhasil' : 'gagal', 'ip' => $request->ip()]);

        if (! $ok) {
            RateLimiter::hit($kunci, 60);
            $pesan = $akun && ! $akun->aktif() ? 'Akun Anda dinonaktifkan. Hubungi pihak Puskesmas.' : 'Username/email atau password salah.';
            throw ValidationException::withMessages(['login' => $pesan]);
        }

        RateLimiter::clear($kunci);
        $request->session()->regenerate();
        $akun = Auth::user()->load('peran');
        AuditLog::catat('login', $akun);

        if ($akun->hasRole(Peran::PASIEN) && ! $akun->notifications()->whereDate('created_at', today())->where('data->langkah', 2)->exists()) {
            Notifier::pasien($akun->pasien, 2, [], route('dashboard'), 'success');
        }

        return redirect()->intended(route('dashboard'))
            ->with('alert', ['tipe' => 'success', 'judul' => 'Selamat datang', 'pesan' => 'Halo, '.$akun->namaTampil().'. Anda masuk sebagai '.$akun->peran->label.'.']);
    }

    public function formRegister()
    {
        return view('auth.register');
    }

    /** Registrasi HANYA untuk pasien. Akun staf dibuat oleh Super Admin. */
    public function register(Request $request)
    {
        $request->merge(['username' => Str::lower(trim((string) $request->input('username'))), 'email' => $request->filled('email') ? Str::lower(trim($request->input('email'))) : null]);
        $data = $request->validate([
            'nik' => ['required', 'digits:16'],
            'nama' => ['required', 'string', 'max:100'],
            'tgl_lahir' => ['required', 'date', 'before:today'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'alamat' => ['required', 'string', 'max:500'],
            'username' => ['required', 'regex:/^[a-z0-9._]{4,30}$/', 'unique:akun,username'],
            'no_tlp' => ['required', 'regex:/^(\+62|62|0)8[0-9]{8,12}$/'],
            'email' => ['nullable', 'email', 'max:100', 'unique:akun,email'],
            'no_bpjs' => ['nullable', 'digits:13', 'unique:pasien,no_bpjs'],
            'password' => ['required', 'confirmed', AturanSandi::min(8)],
            'foto_ktp' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'setuju' => ['accepted'],
        ], [
            'username.regex' => 'Username 4–30 karakter: huruf kecil, angka, titik, atau garis bawah.',
            'username.unique' => 'Username sudah dipakai.',
            'no_tlp.regex' => 'Nomor HP harus diawali 08, 628, atau +628.',
            'setuju.accepted' => 'Anda wajib menyetujui Kebijakan Privasi.',
            'foto_ktp.max' => 'Ukuran file KTP maksimal 5 MB.',
        ], ['nik' => 'NIK', 'no_tlp' => 'nomor HP', 'tgl_lahir' => 'tanggal lahir', 'no_bpjs' => 'nomor BPJS', 'foto_ktp' => 'foto/scan KTP']);

        $pasienLama = Pasien::where('nik', $data['nik'])->first();
        if ($pasienLama?->id_akun) {
            throw ValidationException::withMessages(['nik' => 'NIK sudah terdaftar. Silakan masuk atau gunakan Lupa password.']);
        }

        $pasien = DB::transaction(function () use ($data, $pasienLama, $request) {
            $akun = Akun::create([
                'id_peran' => Peran::where('nama_peran', Peran::PASIEN)->value('id_peran'),
                'username' => $data['username'], 'email' => $data['email'], 'password' => $data['password'], 'status' => 'aktif',
            ]);
            $profil = collect($data)->only(['nik', 'nama', 'tgl_lahir', 'jenis_kelamin', 'no_tlp', 'alamat', 'no_bpjs'])->all();
            if ($request->hasFile('foto_ktp')) {
                $profil['foto_ktp'] = $request->file('foto_ktp')->store('ktp', 'local'); // privat, tidak bisa diakses publik
            }
            if ($pasienLama) {
                $pasienLama->update($profil + ['id_akun' => $akun->id_akun]);
                return $pasienLama;
            }
            return Pasien::create($profil + ['id_akun' => $akun->id_akun, 'no_rm' => Pasien::nomorRmBaru()]);
        });

        Auth::login($pasien->akun);
        $request->session()->regenerate();
        LogLogin::create(['id_akun' => $pasien->id_akun, 'email_dicoba' => $data['username'], 'waktu' => now(), 'hasil' => 'berhasil', 'ip' => $request->ip()]);
        Notifier::pasien($pasien, 1, ['no_rm' => $pasien->no_rm], route('dashboard'), 'success');
        Notifier::peran([Peran::LOKET], 'Pasien baru terdaftar online', "{$pasien->nama} ({$pasien->no_rm}) mendaftar lewat aplikasi.", route('loket.pasien.index', ['q' => $pasien->no_rm]));

        return redirect()->route('dashboard')->with('alert', ['tipe' => 'success', 'judul' => 'Registrasi berhasil', 'pesan' => "Nomor rekam medis Anda {$pasien->no_rm}. Lanjutkan ke Daftar Berobat."]);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login')->with('alert', ['tipe' => 'info', 'judul' => 'Sampai jumpa', 'pesan' => 'Anda telah keluar.']);
    }

    // ---------------- Lupa password ----------------

    public function formLupa()
    {
        return view('auth.lupa-password');
    }

    public function kirimLupa(Request $request)
    {
        $data = $request->validate(['login' => ['required', 'string', 'max:100']], [], ['login' => 'username atau email']);
        $login = Str::lower(trim($data['login']));
        $akun = Akun::where(filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username', $login)->first();

        // Pesan sama untuk akun ada/tidak ada agar tidak membocorkan keberadaan akun.
        $pesanUmum = 'Jika akun terdaftar dan memiliki email, tautan reset telah dikirim. Akun tanpa email: datang ke loket dengan KTP.';
        if ($akun?->email && $akun->aktif()) {
            Password::broker()->sendResetLink(['email' => $akun->email]);
        }
        return back()->with('alert', ['tipe' => 'info', 'judul' => 'Permintaan diterima', 'pesan' => $pesanUmum])->with('terkirim', true);
    }

    public function formReset(Request $request, string $token)
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function simpanReset(Request $request)
    {
        $request->validate([
            'token' => ['required'], 'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', AturanSandi::min(8)],
        ]);
        $status = Password::broker()->reset($request->only('email', 'password', 'password_confirmation', 'token'), function (Akun $akun, string $pw) {
            $akun->forceFill(['password' => $pw, 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($akun));
        });
        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => 'Tautan reset tidak valid atau sudah kedaluwarsa.']);
        }
        return redirect()->route('login')->with('alert', ['tipe' => 'success', 'judul' => 'Password diperbarui', 'pesan' => 'Silakan masuk dengan password baru.']);
    }
}
