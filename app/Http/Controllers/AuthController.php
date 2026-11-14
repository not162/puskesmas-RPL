<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Models\AuditLog;
use App\Models\LogLogin;
use App\Models\OtpKode;
use App\Models\Pasien;
use App\Models\Peran;
use App\Support\CekDuplikat;
use App\Support\Hmac;
use App\Support\Notifier;
use App\Support\Otp;
use App\Support\Verifikasi;
use App\Models\Pengaturan;
use App\Notifications\InfoStaf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as AturanSandi;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private const HARI_PERANGKAT = 30;

    public function formLogin() { return view('auth.login'); }
    public function formLoginPasien() { return view('auth.login-pasien'); }

    // ======================= LOGIN =======================

    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string'],
        ], [], ['login' => 'username atau email']);
        Verifikasi::periksa($request);

        $login = Str::lower(trim($data['login']));
        $kolom = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $kunci = $login.'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($kunci, 5)) {
            throw ValidationException::withMessages(['login' => 'Terlalu banyak percobaan. Coba lagi dalam '.RateLimiter::availableIn($kunci).' detik.']);
        }

        $akun = Akun::where($kolom, $login)->first();
        if ($akun?->terkunci()) {
            LogLogin::create(['id_akun' => $akun->id_akun, 'email_dicoba' => $login, 'waktu' => now(), 'hasil' => 'terkunci', 'ip' => $request->ip()]);
            throw ValidationException::withMessages(['login' => 'Akun terkunci sementara sampai '.$akun->terkunci_sampai->format('H:i').' karena terlalu banyak percobaan gagal. Gunakan Lupa password untuk membuka kunci.']);
        }
        $ok = $akun && $akun->aktif() && Hash::check($data['password'], $akun->password);
        LogLogin::create(['id_akun' => $akun?->id_akun, 'email_dicoba' => $login, 'waktu' => now(), 'hasil' => $ok ? 'berhasil' : 'gagal', 'ip' => $request->ip()]);

        if (! $ok) {
            RateLimiter::hit($kunci, 60);
            if ($akun && $akun->aktif()) self::catatGagal($akun);
            $pesan = $akun && ! $akun->aktif() ? 'Akun Anda dinonaktifkan. Hubungi pihak Puskesmas.' : 'Username/email atau password salah.';
            if ($akun?->fresh()?->terkunci()) $pesan = 'Terlalu banyak percobaan gagal. Akun dikunci '.Pengaturan::nilai('menit_kunci').' menit. Gunakan Lupa password untuk membuka kunci.';
            throw ValidationException::withMessages(['login' => $pesan]);
        }
        RateLimiter::clear($kunci);
        $akun->forceFill(['gagal_login' => 0, 'terkunci_sampai' => null])->save();

        // Perangkat baru wajib OTP email: pasien selalu, petugas bila perannya diaktifkan di Pengaturan
        if (self::wajibOtpPerangkat($akun) && ! $this->perangkatTepercaya($request, $akun)) {
            $otp = Otp::kirim($akun->email, 'login', ['ingat' => $request->boolean('ingat')], $akun->id_akun);
            session(['otp_login' => $otp->id]);
            return redirect()->route('login.otp')->with('alert', ['tipe' => 'info', 'judul' => 'Perangkat baru terdeteksi', 'pesan' => 'Masukkan kode OTP yang dikirim ke '.$otp->emailSamaran().'.']);
        }

        return $this->masuk($request, $akun, $request->boolean('ingat'));
    }

    public function formOtpLogin()
    {
        $otp = OtpKode::find(session('otp_login'));
        abort_unless($otp, 404);
        return view('auth.otp', [
            'judul' => 'Verifikasi perangkat baru', 'email' => $otp->emailSamaran(), 'ingatPerangkat' => true,
            'aksi' => route('login.otp'), 'aksiUlang' => route('login.otp.ulang'), 'tunggu' => Otp::JEDA_KIRIM - $otp->created_at->diffInSeconds(now()),
        ]);
    }

    public function verifikasiOtpLogin(Request $request)
    {
        $request->validate(['kode' => ['required', 'digits:6']], [], ['kode' => 'kode OTP']);
        $otp = Otp::verifikasi(OtpKode::find(session('otp_login')), $request->input('kode'));
        session()->forget('otp_login');
        $akun = Akun::findOrFail($otp->id_akun);
        abort_unless($akun->aktif(), 403);
        $res = $this->masuk($request, $akun, (bool) ($otp->payload['ingat'] ?? false));
        if ($request->boolean('percaya')) {
            Cookie::queue($this->namaCookiePerangkat($akun), $this->tokenPerangkat($akun), 60 * 24 * self::HARI_PERANGKAT);
        }
        return $res;
    }

    public function kirimUlangOtpLogin()
    {
        $otp = Otp::kirimUlang(OtpKode::findOrFail(session('otp_login')));
        session(['otp_login' => $otp->id]);
        return back()->with('alert', ['tipe' => 'success', 'judul' => 'Kode baru dikirim', 'pesan' => 'Periksa email '.$otp->emailSamaran().'.']);
    }

    public static function wajibOtpPerangkat(Akun $a): bool
    {
        if (! $a->email || str_ends_with($a->email, '.test') && ! $a->hasRole(Peran::PASIEN)) return false;
        $peran = array_filter(explode(',', (string) Pengaturan::nilai('otp_petugas')));
        return $a->hasRole(Peran::PASIEN) || in_array($a->role(), $peran, true);
    }

    /** Tambah hitungan gagal; kunci sementara & beri tahu Super Admin bila melewati batas. */
    public static function catatGagal(Akun $akun): void
    {
        $batas = max(3, (int) Pengaturan::nilai('maks_gagal_login'));
        $akun->increment('gagal_login');
        if ($akun->gagal_login < $batas) return;
        $menit = max(1, (int) Pengaturan::nilai('menit_kunci'));
        $akun->forceFill(['terkunci_sampai' => now()->addMinutes($menit), 'gagal_login' => 0])->save();
        AuditLog::catat('kunci_akun', $akun, ['menit' => $menit, 'ip' => request()->ip()]);
        \Illuminate\Support\Facades\Notification::send(
            Akun::whereHas('peran', fn ($q) => $q->where('nama_peran', Peran::SUPER_ADMIN))->where('status', 'aktif')->get(),
            new InfoStaf('Akun dikunci sementara', "@{$akun->username} gagal login {$batas}x dari IP ".request()->ip().". Terkunci {$menit} menit.", route('admin.akun.index', ['q' => $akun->username]), 'danger')
        );
    }

    private function namaCookiePerangkat(Akun $a): string { return 'pkm_perangkat_'.$a->id_akun; }

    /** Token perangkat terikat akun & hash password (otomatis tidak berlaku saat password diganti). */
    private function tokenPerangkat(Akun $a): string { return Hmac::tanda('perangkat|'.$a->id_akun.'|'.substr($a->password, -20)); }

    private function perangkatTepercaya(Request $r, Akun $a): bool
    {
        $c = (string) $r->cookie($this->namaCookiePerangkat($a));
        return $c !== '' && hash_equals($this->tokenPerangkat($a), $c);
    }

    private function masuk(Request $request, Akun $akun, bool $ingat)
    {
        Auth::login($akun, $ingat);
        $request->session()->regenerate();
        $akun->load('peran');
        AuditLog::catat('login', $akun);
        if ($akun->hasRole(Peran::PASIEN) && ! $akun->notifications()->whereDate('created_at', today())->where('data->langkah', 2)->exists()) {
            Notifier::pasien($akun->pasien, 2, [], route('dashboard'), 'success');
        }
        return redirect()->intended(route('dashboard'))
            ->with('alert', ['tipe' => 'success', 'judul' => 'Selamat datang', 'pesan' => 'Halo, '.$akun->namaTampil().'. Anda masuk sebagai '.$akun->peran->label.'.']);
    }

    // ======================= REGISTRASI (pasien saja) =======================

    public function formRegister() { return view('auth.register'); }

    /** Cek cepat saat mengetik: apakah data sudah terdaftar. */
    public function cekRegister(Request $request)
    {
        $kolom = $request->query('kolom');
        abort_unless(array_key_exists($kolom, CekDuplikat::LABEL), 422);
        $nilai = (string) $request->query('nilai');
        return ['terdaftar' => $nilai !== '' && in_array($kolom, CekDuplikat::cari([$kolom => $nilai]), true), 'label' => CekDuplikat::LABEL[$kolom]];
    }

    public function register(Request $request)
    {
        $request->merge([
            'username' => Str::lower(trim((string) $request->input('username'))),
            'email' => Str::lower(trim((string) $request->input('email'))),
            'nama' => trim(preg_replace('/\s+/', ' ', (string) $request->input('nama'))),
        ]);

        Verifikasi::periksa($request);

        // 1) Data sudah terdaftar -> langsung arahkan ke login / lupa password
        $ganda = CekDuplikat::cari($request->only('username', 'email', 'nik', 'nama', 'no_kk'));
        if ($ganda) {
            AuditLog::catat('daftar_ganda', 'akun', ['kolom' => $ganda]);
            return redirect()->route('login')
                ->with('duplikat', CekDuplikat::pesan($ganda))
                ->with('alert', ['tipe' => 'warning', 'judul' => 'Data sudah terdaftar', 'pesan' => CekDuplikat::pesan($ganda).' sudah terdaftar. Silakan masuk atau gunakan Lupa password.']);
        }

        $data = $request->validate([
            'nik' => ['required', 'digits:16'],
            'no_kk' => ['required', 'digits:16'],
            'nama' => ['required', 'string', 'max:100'],
            'tgl_lahir' => ['required', 'date', 'before:today'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'alamat' => ['required', 'string', 'max:500'],
            'username' => ['required', 'regex:/^[a-z0-9._]{4,30}$/'],
            'no_tlp' => ['required', 'regex:/^(\+62|62|0)8[0-9]{8,12}$/'],
            'email' => ['required', 'email:rfc', 'max:100'],
            'no_bpjs' => ['nullable', 'digits:13', 'unique:pasien,no_bpjs'],
            'password' => ['required', 'confirmed', AturanSandi::min(8)],
            'foto_ktp' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'setuju' => ['accepted'],
        ], [
            'username.regex' => 'Username 4–30 karakter: huruf kecil, angka, titik, atau garis bawah.',
            'no_tlp.regex' => 'Nomor HP harus diawali 08, 628, atau +628.',
            'email.required' => 'Email wajib diisi untuk menerima kode OTP.',
            'setuju.accepted' => 'Anda wajib menyetujui Kebijakan Privasi.',
            'foto_ktp.max' => 'Ukuran file KTP maksimal 5 MB.',
        ], ['nik' => 'NIK', 'no_kk' => 'Nomor KK', 'no_tlp' => 'nomor HP', 'tgl_lahir' => 'tanggal lahir', 'no_bpjs' => 'nomor BPJS', 'foto_ktp' => 'foto/scan KTP']);

        // 2) Simpan sementara, kirim OTP ke email pengguna
        $payload = collect($data)->except(['password', 'password_confirmation', 'setuju', 'foto_ktp'])->all();
        $payload['wa_opt_in'] = $request->boolean('wa_opt_in');
        $payload['password'] = Hash::make($data['password']);
        if ($request->hasFile('foto_ktp')) {
            $payload['foto_ktp'] = $request->file('foto_ktp')->store('ktp/sementara', 'local');
        }
        $otp = Otp::kirim($data['email'], 'registrasi', $payload);
        session(['otp_registrasi' => $otp->id]);

        return redirect()->route('register.otp')->with('alert', ['tipe' => 'info', 'judul' => 'Kode OTP terkirim', 'pesan' => ($otp->saluran === 'whatsapp' ? 'Email gagal, kode dikirim lewat ' : 'Cek email ').$otp->emailSamaran().' lalu masukkan kode 6 digit.']);
    }

    public function formOtpRegister()
    {
        $otp = OtpKode::find(session('otp_registrasi'));
        if (! $otp) return redirect()->route('register');
        return view('auth.otp', [
            'judul' => 'Verifikasi email', 'email' => $otp->emailSamaran(),
            'aksi' => route('register.otp'), 'aksiUlang' => route('register.otp.ulang'), 'tunggu' => Otp::JEDA_KIRIM - $otp->created_at->diffInSeconds(now()),
        ]);
    }

    public function kirimUlangOtpRegister()
    {
        $otp = Otp::kirimUlang(OtpKode::findOrFail(session('otp_registrasi')));
        session(['otp_registrasi' => $otp->id]);
        return back()->with('alert', ['tipe' => 'success', 'judul' => 'Kode baru dikirim', 'pesan' => 'Periksa email '.$otp->emailSamaran().'.']);
    }

    public function verifikasiOtpRegister(Request $request)
    {
        $request->validate(['kode' => ['required', 'digits:6']], [], ['kode' => 'kode OTP']);
        $otp = Otp::verifikasi(OtpKode::find(session('otp_registrasi')), $request->input('kode'));
        session()->forget('otp_registrasi');

        $pasien = self::buatPasienDariPayload($otp->payload);
        if (! $pasien) {
            return redirect()->route('login')->with('alert', ['tipe' => 'warning', 'judul' => 'Data sudah terdaftar', 'pesan' => 'Data ini terdaftar saat verifikasi berlangsung. Silakan masuk atau gunakan Lupa password.']);
        }

        Auth::login($pasien->akun);
        $request->session()->regenerate();
        Cookie::queue($this->namaCookiePerangkat($pasien->akun), $this->tokenPerangkat($pasien->akun), 60 * 24 * self::HARI_PERANGKAT);
        LogLogin::create(['id_akun' => $pasien->id_akun, 'email_dicoba' => $pasien->akun->username, 'waktu' => now(), 'hasil' => 'berhasil', 'ip' => $request->ip()]);

        return redirect()->route('dashboard')->with('alert', ['tipe' => 'success', 'judul' => 'Registrasi berhasil', 'pesan' => "Email terverifikasi. Nomor rekam medis Anda {$pasien->no_rm}."]);
    }

    /** Dipakai web & API setelah OTP valid. Null bila data ternyata sudah terdaftar (race). */
    public static function buatPasienDariPayload(array $p): ?Pasien
    {
        if (CekDuplikat::cari($p)) return null;

        $pasien = DB::transaction(function () use ($p) {
            $akun = Akun::create([
                'id_peran' => Peran::where('nama_peran', Peran::PASIEN)->value('id_peran'),
                'username' => $p['username'], 'email' => $p['email'], 'password' => $p['password'], 'status' => 'aktif',
            ]);
            $profil = collect($p)->only(['nik', 'no_kk', 'nama', 'tgl_lahir', 'jenis_kelamin', 'no_tlp', 'alamat', 'no_bpjs'])->all();
            $profil['wa_opt_in'] = (bool) ($p['wa_opt_in'] ?? false);
            if (! empty($p['foto_ktp']) && Storage::disk('local')->exists($p['foto_ktp'])) {
                $tujuan = 'ktp/'.basename($p['foto_ktp']);
                Storage::disk('local')->move($p['foto_ktp'], $tujuan);
                $profil['foto_ktp'] = $tujuan;
            }
            $lama = Pasien::where('nik', $p['nik'])->whereNull('id_akun')->first(); // pernah didaftarkan loket
            if ($lama) {
                $lama->update($profil + ['id_akun' => $akun->id_akun]);
                return $lama;
            }
            return Pasien::create($profil + ['id_akun' => $akun->id_akun, 'no_rm' => Pasien::nomorRmBaru()]);
        });

        Notifier::pasien($pasien, 1, ['no_rm' => $pasien->no_rm], route('dashboard'), 'success');
        Notifier::peran([Peran::LOKET], 'Pasien baru terdaftar online', "{$pasien->nama} ({$pasien->no_rm}) mendaftar lewat aplikasi.", route('loket.pasien.index', ['q' => $pasien->no_rm]));
        return $pasien;
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login')->with('alert', ['tipe' => 'info', 'judul' => 'Sampai jumpa', 'pesan' => 'Anda telah keluar.']);
    }

    // ======================= LUPA PASSWORD (OTP email) =======================

    public function formLupa() { return view('auth.lupa-password'); }

    public function kirimLupa(Request $request)
    {
        $data = $request->validate(['login' => ['required', 'string', 'max:100']], [], ['login' => 'username atau email']);
        Verifikasi::periksa($request);
        $login = Str::lower(trim($data['login']));
        $akun = Akun::where(filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username', $login)->first();

        session()->forget('otp_reset');
        $email = 'email terdaftar';
        if ($akun?->email && $akun->aktif()) {
            $otp = Otp::kirim($akun->email, 'reset', [], $akun->id_akun);
            session(['otp_reset' => $otp->id]);
            $email = $otp->emailSamaran();
        }
        // Pesan sama untuk akun ada/tidak ada agar keberadaan akun tidak bocor
        return redirect()->route('password.otp')->with('email_reset', $email)
            ->with('alert', ['tipe' => 'info', 'judul' => 'Permintaan diterima', 'pesan' => 'Jika akun terdaftar dan memiliki email, kode OTP telah dikirim. Akun tanpa email: datang ke loket dengan KTP.']);
    }

    public function formOtpReset()
    {
        $otp = OtpKode::find(session('otp_reset'));
        return view('auth.otp', [
            'judul' => 'Atur ulang password', 'email' => $otp?->emailSamaran() ?? session('email_reset', 'email terdaftar'), 'resetPassword' => true,
            'aksi' => route('password.otp'), 'aksiUlang' => route('password.otp.ulang'), 'tunggu' => $otp ? Otp::JEDA_KIRIM - $otp->created_at->diffInSeconds(now()) : 60,
        ]);
    }

    public function kirimUlangOtpReset()
    {
        $lama = OtpKode::find(session('otp_reset'));
        if ($lama) {
            $otp = Otp::kirimUlang($lama);
            session(['otp_reset' => $otp->id]);
        }
        return back()->with('alert', ['tipe' => 'success', 'judul' => 'Kode baru dikirim', 'pesan' => 'Periksa kotak masuk email Anda.']);
    }

    public function simpanResetOtp(Request $request)
    {
        $request->validate(['kode' => ['required', 'digits:6'], 'password' => ['required', 'confirmed', AturanSandi::min(8)]], [], ['kode' => 'kode OTP']);
        $otp = Otp::verifikasi(OtpKode::find(session('otp_reset')), $request->input('kode'));
        session()->forget('otp_reset');
        $akun = Akun::findOrFail($otp->id_akun);
        $akun->forceFill(['password' => $request->input('password'), 'remember_token' => Str::random(60), 'gagal_login' => 0, 'terkunci_sampai' => null])->save();
        $akun->tokens()->delete();
        return redirect()->route('login')->with('alert', ['tipe' => 'success', 'judul' => 'Password diperbarui', 'pesan' => 'Silakan masuk dengan password baru.']);
    }
}
