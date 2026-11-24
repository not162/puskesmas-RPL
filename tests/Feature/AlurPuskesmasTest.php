<?php

namespace Tests\Feature;

use App\Http\Controllers\RealtimeController;
use App\Models\Akun;
use App\Models\Antrian;
use App\Models\AuditLog;
use App\Models\Konsultasi;
use App\Models\Obat;
use App\Models\Resep;
use App\Support\Hmac;
use App\Mail\KodeOtpMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AlurPuskesmasTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function akun(string $username): Akun
    {
        return Akun::where('username', $username)->firstOrFail();
    }

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    /** Kode OTP terakhir yang dikirim lewat email (SMTP dipalsukan di test). */
    private function kodeOtp(?string $email = null): string
    {
        $m = Mail::sent(KodeOtpMail::class, fn ($mail) => $email === null || $mail->hasTo($email))->last();
        $this->assertNotNull($m, 'Email OTP tidak terkirim');
        return $m->kode;
    }

    private function sandi(string $peran): string
    {
        return env('SEED_PASSWORD_'.strtoupper($peran));
    }

    public function test_halaman_publik_dan_gateway_login_tanpa_akun_demo(): void
    {
        foreach (['/', '/layar-antrean', '/jadwal', '/alur-layanan', '/informasi', '/register', '/lupa-password', '/pasien/masuk', '/kebijakan-privasi', '/api/docs'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/login')->assertOk()->assertSee('Portal Masuk')->assertSee('Verifikasi keamanan')->assertSee('Lupa password?')
            ->assertDontSee('Akun demo')->assertDontSee('superadmin@')->assertDontSee($this->sandi('super_admin'));
        // Jadwal publik tidak memuat No. STR
        $this->get('/jadwal')->assertDontSee('STR-3171');
    }

    public function test_tujuh_peran_login_dengan_username_dan_password_berbeda(): void
    {
        $peran = ['superadmin' => 'super_admin', 'admin' => 'admin', 'dr.umum' => 'dokter_umum', 'dr.kandungan' => 'dokter_kandungan', 'apoteker' => 'apoteker', 'loket' => 'loket', 'pasien' => 'pasien'];
        $this->assertCount(7, array_unique(array_map(fn ($p) => $this->sandi($p), $peran)));

        foreach ($peran as $username => $p) {
            // OTP hanya untuk lupa password: semua peran (termasuk pasien) langsung masuk
            $res = $this->withSession(['captcha' => 7])->post('/login', ['login' => $username, 'password' => $this->sandi($p), 'captcha' => 7]);
            $res->assertRedirect('/dashboard');
            $this->assertSame($p, auth()->user()->role());
            $this->get('/dashboard')->assertOk();
            $this->post('/logout');
        }
        // Password peran lain tidak berlaku
        $this->withSession(['captcha' => 7])->post('/login', ['login' => 'loket', 'password' => $this->sandi('apoteker'), 'captcha' => 7])->assertSessionHasErrors('login');
        // Captcha salah ditolak walau password benar
        $this->withSession(['captcha' => 7])->post('/login', ['login' => 'loket', 'password' => $this->sandi('loket'), 'captcha' => 8])->assertSessionHasErrors('captcha');
        $this->assertGuest();
        // Login dengan email juga bisa
        $this->withSession(['captcha' => 3])->post('/login', ['login' => 'apoteker@puskesmas.test', 'password' => $this->sandi('apoteker'), 'captcha' => 3])->assertRedirect('/dashboard');
    }

    public function test_lupa_password_dengan_otp_email(): void
    {
        $this->withSession(['captcha' => 5])->post('/lupa-password', ['captcha' => 5] + ['login' => 'tidak.ada'])->assertRedirect('/lupa-password/verifikasi'); // respons sama, tidak bocor
        Mail::assertNothingSent();

        $this->withSession(['captcha' => 5])->post('/lupa-password', ['captcha' => 5] + ['login' => 'pasien'])->assertRedirect('/lupa-password/verifikasi');
        Mail::assertSent(KodeOtpMail::class, fn ($m) => $m->hasTo('pasien@puskesmas.test') && $m->tujuan === 'reset');
        $this->post('/lupa-password/verifikasi', ['kode' => '123123', 'password' => 'BaruSekali9', 'password_confirmation' => 'BaruSekali9'])->assertSessionHasErrors('kode');
        $this->post('/lupa-password/verifikasi', ['kode' => $this->kodeOtp(), 'password' => 'BaruSekali9', 'password_confirmation' => 'BaruSekali9'])->assertRedirect('/login');
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('BaruSekali9', $this->akun('pasien')->fresh()->password));
    }

    public function test_otp_mode_antrean_tidak_menahan_halaman(): void
    {
        config(['otp.pengiriman' => 'antrean']);
        $this->withSession(['captcha' => 5])->post('/lupa-password', ['captcha' => 5] + ['login' => 'pasien'])->assertRedirect('/lupa-password/verifikasi');
        Mail::assertQueued(KodeOtpMail::class, fn ($m) => $m->hasTo('pasien@puskesmas.test') && $m->tries === 3);
        Mail::assertNotSent(KodeOtpMail::class);
    }

    public function test_cek_kesiapan_dan_qr_tanpa_cdn(): void
    {
        $this->artisan('puskesmas:cek')->expectsOutputToContain('Database')->expectsOutputToContain('Password awal')->assertSuccessful();
        $this->actingAs($this->akun('pasien'))->post('/pasien/antrean', ['id_spesialisasi' => 1, 'tanggal' => today()->toDateString()]);
        $a = Antrian::first();
        $html = $this->get("/pasien/antrean/{$a->kode}/tiket")->assertOk()->getContent();
        $this->assertStringContainsString('<svg', $html);
        $this->assertStringNotContainsString('cdn.jsdelivr', $html);
        $this->assertStringNotContainsString('cdn.jsdelivr', $this->get('/api/docs')->getContent());
        $this->assertStringNotContainsString('fonts.googleapis', $this->get('/')->getContent());
    }

    public function test_ekspor_laporan_xlsx_dan_pdf(): void
    {
        $admin = $this->akun('superadmin');
        $xlsx = $this->actingAs($admin)->get('/laporan/xlsx')->assertOk()->assertDownload('laporan-puskesmas-'.now()->format('Y-m').'.xlsx');
        $path = $xlsx->baseResponse->getFile()->getPathname();
        $book = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        $this->assertSame(['Ringkasan', 'Per Poli', 'Per Dokter', 'Obat Keluar', 'Harian'], $book->getSheetNames());
        $this->assertStringContainsString('LAPORAN BULANAN', $book->getSheetByName('Per Poli')->getCell('A3')->getValue());

        $pdf = $this->actingAs($admin)->get('/laporan/pdf')->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
        $this->actingAs($admin)->get('/laporan/pdf?unduh=0')->assertHeader('Content-Disposition', 'inline; filename="laporan-puskesmas-'.now()->format('Y-m').'.pdf"');

        // Admin boleh, apoteker tidak
        $this->actingAs($this->akun('admin'))->get('/laporan/xlsx')->assertOk();
        $this->actingAs($this->akun('apoteker'))->get('/laporan/pdf')->assertRedirect('/dashboard');

        // API dengan token
        $this->app['auth']->forgetGuards();
        $token = $this->postJson('/api/v1/auth/login', ['login' => 'superadmin', 'password' => $this->sandi('super_admin')])->json('token');
        $this->get('/api/v1/laporan/bulanan/pdf', ['Authorization' => "Bearer $token"])->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_kunci_akun_setelah_gagal_berulang(): void
    {
        $login = fn (string $pw) => $this->withSession(['captcha' => 4])->post('/login', ['login' => 'apoteker', 'password' => $pw, 'captcha' => 4]);
        for ($i = 0; $i < 4; $i++) {
            $login('salah-'.$i)->assertSessionHasErrors('login');
            \Illuminate\Support\Facades\RateLimiter::clear('apoteker|127.0.0.1');
        }
        $this->assertFalse($this->akun('apoteker')->terkunci());
        $login('salah-5')->assertSessionHasErrors('login');
        $apt = $this->akun('apoteker');
        $this->assertTrue($apt->terkunci());
        // Password benar pun ditolak selama terkunci
        \Illuminate\Support\Facades\RateLimiter::clear('apoteker|127.0.0.1');
        $login($this->sandi('apoteker'))->assertSessionHasErrors('login');
        $this->assertGuest();
        // Super Admin diberi tahu & tercatat di audit
        $this->assertTrue($this->akun('superadmin')->notifications()->where('data->judul', 'Akun dikunci sementara')->exists());
        $this->assertTrue(AuditLog::where('aksi', 'kunci_akun')->exists());
        // API juga menolak (423)
        $this->postJson('/api/v1/auth/login', ['login' => 'apoteker', 'password' => $this->sandi('apoteker')])->assertStatus(423);
        // Super Admin membuka kunci
        $this->actingAs($this->akun('superadmin'))->post("/admin/akun/{$apt->id_akun}/buka-kunci")->assertRedirect();
        $this->assertFalse($apt->fresh()->terkunci());
        $this->post('/logout');
        $login($this->sandi('apoteker'))->assertRedirect('/dashboard');
    }

    public function test_lupa_password_membuka_kunci_akun(): void
    {
        $p = $this->akun('pasien');
        $p->forceFill(['terkunci_sampai' => now()->addMinutes(15)])->save();
        $this->withSession(['captcha' => 5])->post('/lupa-password', ['captcha' => 5, 'login' => 'pasien']);
        $this->post('/lupa-password/verifikasi', ['kode' => $this->kodeOtp(), 'password' => 'BaruLagi12', 'password_confirmation' => 'BaruLagi12'])->assertRedirect('/login');
        $this->assertFalse($p->fresh()->terkunci());
    }

    public function test_turnstile_dan_cadangan_soal_hitung(): void
    {
        config(['services.turnstile.site_key' => '1x00000000000000000000AA', 'services.turnstile.secret_key' => 'rahasia-uji']);
        $this->get('/login')->assertSee('cf-turnstile', false)->assertDontSee('captcha-q', false);

        // Satu fake dengan saklar: online (token-ok lolos) atau offline
        $offline = false;
        \Illuminate\Support\Facades\Http::fake(function ($req) use (&$offline) {
            if ($offline) throw new \Illuminate\Http\Client\ConnectionException('offline');
            return \Illuminate\Support\Facades\Http::response(['success' => $req['response'] === 'token-ok']);
        });
        $this->post('/login', ['login' => 'loket', 'password' => $this->sandi('loket'), 'cf-turnstile-response' => 'token-ok'])->assertRedirect('/dashboard');
        \Illuminate\Support\Facades\Http::assertSent(fn ($r) => $r['secret'] === 'rahasia-uji' && $r['response'] === 'token-ok');
        $this->post('/logout');

        // Token ditolak / kosong
        $this->post('/login', ['login' => 'loket', 'password' => $this->sandi('loket'), 'cf-turnstile-response' => 'palsu'])->assertSessionHasErrors('captcha');
        $this->post('/login', ['login' => 'loket', 'password' => $this->sandi('loket')])->assertSessionHasErrors('captcha');
        $this->assertGuest();

        // Cloudflare tidak terjangkau (offline) -> beralih ke soal hitung
        $offline = true;
        $this->post('/login', ['login' => 'loket', 'password' => $this->sandi('loket'), 'cf-turnstile-response' => 'x'])->assertSessionHasErrors('captcha');
        $this->get('/login')->assertSee('captcha-q', false)->assertDontSee('cf-turnstile', false);
        $this->withSession(['captcha' => 6, 'verifikasi_mode' => 'hitung'])->post('/login', ['login' => 'loket', 'password' => $this->sandi('loket'), 'captcha' => 6])->assertRedirect('/dashboard');
    }

    public function test_otp_perangkat_baru_untuk_petugas_bila_diaktifkan(): void
    {
        // Bawaan: OTP perangkat baru MATI, pasien pun langsung masuk walau peran dicentang
        \App\Models\Pengaturan::updateOrCreate(['kunci' => 'otp_petugas'], ['nilai' => 'loket']);
        $this->withSession(['captcha' => 2])->post('/login', ['login' => 'pasien', 'password' => $this->sandi('pasien'), 'captcha' => 2])->assertRedirect('/dashboard');
        $this->post('/logout');
        \App\Models\Pengaturan::updateOrCreate(['kunci' => 'otp_petugas'], ['nilai' => '']);
        Mail::assertNothingSent();

        // Fitur opsional diaktifkan (OTP_LOGIN_PERANGKAT=true): pasien wajib OTP dari perangkat baru
        config(['otp.login_perangkat' => true]);
        $this->withSession(['captcha' => 2])->post('/login', ['login' => 'pasien', 'password' => $this->sandi('pasien'), 'captcha' => 2])->assertRedirect('/login/verifikasi');
        $this->assertGuest();
        $this->post('/login/verifikasi', ['kode' => '000000'])->assertSessionHasErrors('kode');
        $this->post('/login/verifikasi', ['kode' => $this->kodeOtp('pasien@puskesmas.test'), 'percaya' => '1'])->assertRedirect('/dashboard');
        $this->post('/logout');

        $loket = $this->akun('loket');
        $loket->update(['email' => 'loket.puskesmas@gmail.com']);
        $masuk = fn () => $this->withSession(['captcha' => 2])->post('/login', ['login' => 'loket', 'password' => $this->sandi('loket'), 'captcha' => 2]);

        $masuk()->assertRedirect('/dashboard'); // belum diaktifkan: langsung masuk
        $this->post('/logout');

        \App\Models\Pengaturan::updateOrCreate(['kunci' => 'otp_petugas'], ['nilai' => 'loket,apoteker']);
        $masuk()->assertRedirect('/login/verifikasi');
        $this->assertGuest();
        $this->post('/login/verifikasi', ['kode' => $this->kodeOtp('loket.puskesmas@gmail.com')])->assertRedirect('/dashboard');

        // Petugas dengan email contoh (.test) tidak dipaksa OTP agar tidak terkunci di luar
        $this->post('/logout');
        $this->withSession(['captcha' => 2])->post('/login', ['login' => 'apoteker', 'password' => $this->sandi('apoteker'), 'captcha' => 2])->assertRedirect('/dashboard');
    }

    public function test_websocket_kanal_privat_dan_sinyal_tanpa_data_pribadi(): void
    {
        // Otorisasi kanal: staf hanya untuk petugas; akun.{id} hanya pemiliknya
        config(['broadcasting.default' => 'reverb', 'broadcasting.connections.reverb.key' => 'k', 'broadcasting.connections.reverb.secret' => 's', 'broadcasting.connections.reverb.app_id' => '1']);
        app(\Illuminate\Broadcasting\BroadcastManager::class)->purge('reverb');
        require base_path('routes/channels.php'); // daftarkan kanal ke driver reverb
        $auth = fn ($u, $ch) => $this->actingAs($this->akun($u))->post('/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => $ch]);
        $auth('loket', 'private-staf')->assertOk();
        $auth('pasien', 'private-staf')->assertForbidden();
        $idPasien = $this->akun('pasien')->id_akun;
        $auth('pasien', 'private-akun.'.$idPasien)->assertOk();
        $auth('loket', 'private-akun.'.$idPasien)->assertForbidden();

        // Sinyal yang dihasilkan perubahan data: hanya nama kanal
        config(['broadcasting.default' => 'log']);
        \App\Support\Siaran::kirimSekarang();
        \App\Support\Siaran::$terkirim = [];
        $this->actingAs($this->akun('pasien'))->post('/pasien/antrean', ['id_spesialisasi' => 1, 'tanggal' => today()->toDateString()]);
        $kirim = collect(\App\Support\Siaran::$terkirim)->groupBy(0)->map(fn ($g) => $g->pluck(1)->flatten()->unique()->values()->all());
        $this->assertContains('antrian', $kirim['publik']);
        $this->assertContains('antrian', $kirim['staf']);
        $this->assertContains('antrian', $kirim['akun.'.$idPasien]);
        $this->assertContains('notifikasi', $kirim['akun.'.$idPasien]);
        $this->assertNotContains('pasien', $kirim['publik']);
        $payload = json_encode((new \App\Events\DataBerubah('publik', ['antrian']))->broadcastWith());
        $this->assertSame('{"kanal":["antrian"]}', $payload);
    }

    public function test_siaran_gagal_tidak_mengganggu_request(): void
    {
        // Reverb tidak berjalan (port tertutup): aksi tetap berhasil
        config(['broadcasting.default' => 'reverb', 'broadcasting.connections.reverb.key' => 'k', 'broadcasting.connections.reverb.secret' => 's',
            'broadcasting.connections.reverb.app_id' => '1', 'broadcasting.connections.reverb.options.host' => '127.0.0.1', 'broadcasting.connections.reverb.options.port' => 1]);
        app(\Illuminate\Broadcasting\BroadcastManager::class)->purge('reverb');
        \App\Support\Siaran::$terkirim = [];
        $mulai = microtime(true);
        $this->actingAs($this->akun('pasien'))->post('/pasien/antrean', ['id_spesialisasi' => 1, 'tanggal' => today()->toDateString()])->assertRedirect();
        $this->assertSame([], \App\Support\Siaran::$terkirim); // gagal kirim, diabaikan
        $this->assertLessThan(5, microtime(true) - $mulai);
        $this->assertSame(1, Antrian::count());
    }
    public function test_whatsapp_hanya_untuk_pasien_opt_in_dan_dibatasi(): void
    {
        $p = $this->akun('pasien')->pasien;
        $ambil = fn () => $this->actingAs($this->akun('pasien'))->post('/pasien/antrean', ['id_spesialisasi' => 1, 'tanggal' => today()->toDateString()]);

        // Belum opt-in: tidak ada pesan WA
        $ambil();
        $this->assertSame(0, \App\Models\WaLog::count());

        // Opt-in: langkah 5 (nomor antrean) dikirim, nomor dinormalisasi ke 62...
        $p->update(['wa_opt_in' => true]);
        \App\Models\Antrian::query()->update(['status' => 'batal']);
        $ambil();
        $log = \App\Models\WaLog::first();
        $this->assertSame('6281234567890', $log->nomor);
        $this->assertSame('langkah_5', $log->jenis);
        $this->assertSame('log', $log->status);

        // Langkah 7 (dipanggil) juga terkirim, langkah 6 (check-in) tidak (tidak dicentang)
        $a = \App\Models\Antrian::where('status', 'menunggu')->first();
        $this->actingAs($this->akun('loket'))->post("/loket/antrean/{$a->kode}/checkin");
        $this->actingAs($this->akun('loket'))->post("/loket/antrean/{$a->kode}/panggil");
        $this->assertEqualsCanonicalizing(['langkah_5', 'langkah_7'], \App\Models\WaLog::pluck('jenis')->all());

        // Batas harian
        \App\Models\Pengaturan::updateOrCreate(['kunci' => 'wa_maks_harian'], ['nilai' => '2']);
        \Illuminate\Support\Facades\Cache::forget('pengaturan:db');
        $this->assertSame('dibatasi', \App\Support\WhatsApp::kirim('081234567890', 'tes', 'uji'));
    }

    public function test_whatsapp_driver_fonnte(): void
    {
        config(['whatsapp.driver' => 'fonnte', 'whatsapp.fonnte_token' => 'token-uji']);
        $ok = true;
        \Illuminate\Support\Facades\Http::fake(function ($req) use (&$ok) {
            return \Illuminate\Support\Facades\Http::response(['status' => $ok], 200);
        });
        $this->assertSame('terkirim', \App\Support\WhatsApp::kirim('+62 812-3456-7891', 'Halo', 'uji'));
        \Illuminate\Support\Facades\Http::assertSent(fn ($r) => $r->url() === 'https://api.fonnte.com/send' && $r->hasHeader('Authorization', 'token-uji') && $r['target'] === '6281234567891');
        $ok = false;
        $this->assertSame('gagal', \App\Support\WhatsApp::kirim('081234567891', 'Halo', 'uji'));
        $this->assertSame('tidak_valid', \App\Support\WhatsApp::kirim('12345', 'Halo', 'uji'));
        // Halaman log & kirim uji (Super Admin)
        $ok = true;
        $this->actingAs($this->akun('superadmin'))->get('/admin/whatsapp')->assertOk()->assertSee('6281');
        $this->actingAs($this->akun('superadmin'))->post('/admin/whatsapp/uji', ['nomor' => '081234567892'])->assertSessionHas('alert.tipe', 'success');
        $this->actingAs($this->akun('admin'))->get('/admin/whatsapp')->assertRedirect('/dashboard');
    }

    public function test_otp_cadangan_lewat_whatsapp_saat_email_gagal(): void
    {
        config(['otp.registrasi' => true]); // fitur opsional: OTP saat daftar
        \Illuminate\Support\Facades\Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP mati'));
        \Illuminate\Support\Facades\Log::spy();
        $data = ['captcha' => 5, 'nik' => '3171010101990801', 'no_kk' => '3171010101990802', 'nama' => 'Wati', 'tgl_lahir' => '1991-01-01', 'jenis_kelamin' => 'P',
            'alamat' => 'Jl. C', 'username' => 'wati.wa', 'no_tlp' => '081299990000', 'email' => 'wati@gmail.com',
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123', 'setuju' => '1'];

        // Tanpa opt-in: gagal dengan pesan jelas
        $this->withSession(['captcha' => 5])->post('/register', $data)->assertSessionHasErrors('email');
        $this->assertSame(0, \App\Models\OtpKode::count());

        // Dengan opt-in: kode dikirim lewat WhatsApp
        $this->withSession(['captcha' => 5])->post('/register', $data + ['wa_opt_in' => '1'])->assertRedirect('/register/verifikasi');
        $this->assertSame('whatsapp', \App\Models\OtpKode::first()->saluran);
        $this->assertSame('Kode OTP ••••••', \App\Models\WaLog::first()->ringkas); // kode tidak tersimpan di log
        $kode = null;
        \Illuminate\Support\Facades\Log::shouldHaveReceived('info')->withArgs(function ($m) use (&$kode) {
            if (preg_match('/Kode OTP Anda: \*(\d{6})\*/', $m, $x)) $kode = $x[1];
            return true;
        });
        $this->assertNotNull($kode);
        $this->get('/register/verifikasi')->assertSee('WhatsApp 6281');
        $this->post('/register/verifikasi', ['kode' => $kode])->assertRedirect('/dashboard');
        $this->assertTrue(Akun::where('username', 'wati.wa')->first()->pasien->wa_opt_in);
    }

    public function test_data_ganda_diarahkan_ke_login_atau_lupa_password(): void
    {
        $dasar = ['nik' => '3171010101990077', 'no_kk' => '3171010101990000', 'nama' => 'Pasien Baru', 'tgl_lahir' => '1990-02-02', 'jenis_kelamin' => 'L',
            'alamat' => 'Jl. B', 'username' => 'pasien.baru', 'no_tlp' => '081211113333', 'email' => 'pasien.baru@gmail.com',
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123', 'setuju' => '1'];
        foreach ([['username' => 'pasien'], ['email' => 'pasien@puskesmas.test'], ['nik' => '3171010101900001'], ['nama' => '  budi   SANTOSO '], ['no_kk' => '3171010101900099']] as $i => $ubah) {
            if ($i === 4) \App\Models\Pasien::where('nik', '3171010101900001')->update(['no_kk' => '3171010101900099']);
            $this->withSession(['captcha' => 5])->post('/register', ['captcha' => 5] + array_merge($dasar, $ubah))->assertRedirect('/login')->assertSessionHas('duplikat');
        }
        Mail::assertNothingSent();
        $this->getJson('/register/cek?kolom=username&nilai=pasien')->assertJson(['terdaftar' => true]);
        $this->getJson('/register/cek?kolom=username&nilai=belum.ada')->assertJson(['terdaftar' => false]);

        // Data baru lolos -> akun langsung dibuat TANPA kode OTP (OTP hanya untuk lupa password)
        $this->withSession(['captcha' => 5])->post('/register', ['captcha' => 5] + $dasar)->assertRedirect('/dashboard');
        $this->assertAuthenticated();
        Mail::assertNothingSent();
        $this->assertSame('3171010101990000', Akun::where('username', 'pasien.baru')->first()->pasien->no_kk);
    }

    public function test_alur_lengkap_dengan_notifikasi_qr_hmac_dan_realtime(): void
    {
        Storage::fake('local');
        $versiAwal = RealtimeController::versi();

        // 1. Registrasi (hanya pasien) — form sesuai layout
        $this->withSession(['captcha' => 5])->post('/register', ['captcha' => 5] + [
            'nik' => '3171010101990002', 'nama' => 'Siti Aminah', 'tgl_lahir' => '1995-05-05', 'jenis_kelamin' => 'P',
            'alamat' => 'Jl. Mawar 1', 'username' => 'siti.aminah', 'no_tlp' => '081298765432', 'email' => 'siti.aminah@gmail.com', 'no_kk' => '3171010101990111',
            'no_bpjs' => '0009876543210', 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
            'foto_ktp' => UploadedFile::fake()->image('ktp.jpg'), 'setuju' => '1',
        ])->assertRedirect('/dashboard');
        $siti = Akun::where('username', 'siti.aminah')->first();
        $this->assertSame('pasien', $siti->role());
        $this->assertSame('siti.aminah@gmail.com', $siti->email);
        Storage::disk('local')->assertExists($siti->pasien->foto_ktp);
        // Username duplikat ditolak
        $this->post('/logout');
        $this->withSession(['captcha' => 5])->post('/register', ['captcha' => 5] + ['username' => 'siti.aminah', 'nik' => '3171010101990003'])->assertRedirect('/login')->assertSessionHas('duplikat', 'Username');

        // Loket mendapat notifikasi pasien baru (real-time lintas peran)
        $this->assertTrue($this->akun('loket')->notifications()->where('data->judul', 'like', 'Pasien baru%')->exists());

        // 3–4. Konsultasi online -> dokter kandungan menanggapi
        $this->actingAs($siti)->post('/pasien/konsultasi', ['id_spesialisasi' => 2, 'keluhan' => 'Kontrol kehamilan bulan ke-5, kaki bengkak'])->assertRedirect();
        $k = Konsultasi::first();
        $this->actingAs($this->akun('dr.umum'))->get("/praktik/konsultasi/{$k->kode}")->assertForbidden();
        $this->actingAs($this->akun('dr.kandungan'))->post("/praktik/konsultasi/{$k->kode}/tanggapi", ['tanggapan' => 'Silakan datang besok.'])->assertRedirect();

        // 5. Daftar berobat (antrean online) + tiket ber-QR HMAC
        $this->actingAs($siti)->post('/pasien/antrean', ['id_spesialisasi' => 2, 'tanggal' => today()->toDateString(), 'konsultasi' => $k->kode])->assertRedirect();
        $a = Antrian::first();
        $qr = Hmac::qrTiket($a);
        $this->actingAs($siti)->get("/pasien/antrean/{$a->kode}/tiket")->assertOk()->assertSee($qr, false);
        Cache::forget('realtime:versi');
        $this->assertNotSame($versiAwal['antrian'], RealtimeController::versi()['antrian']);

        // Layar publik tidak membocorkan data pribadi
        $live = $this->get('/live/antrian')->getContent();
        $this->assertStringNotContainsString('Siti', $live);
        $this->assertStringNotContainsString('3171010101990002', $live);

        // 6. Check-in QR: tiket dipalsukan ditolak, tiket asli diterima
        $palsu = substr($qr, 0, -1).($qr[-1] === 'a' ? 'b' : 'a');
        $this->actingAs($this->akun('loket'))->post('/loket/checkin-qr', ['qr' => $palsu])->assertSessionHas('alert.tipe', 'danger');
        $this->assertSame('menunggu', $a->fresh()->status);
        $this->assertTrue(AuditLog::where('aksi', 'qr_tolak')->exists());
        $this->actingAs($this->akun('loket'))->post('/loket/checkin-qr', ['qr' => $qr])->assertSessionHas('alert.tipe', 'success');
        $this->assertSame('hadir', $a->fresh()->status);

        // 7–8. Dokter memanggil, memeriksa, menerbitkan resep
        $this->actingAs($this->akun('dr.kandungan'))->post("/praktik/antrean/{$a->kode}/panggil")->assertRedirect();
        $this->actingAs($this->akun('dr.kandungan'))->get('/praktik/pemeriksaan')->assertOk()->assertSee($a->nomor);
        $folat = Obat::where('nama_obat', 'like', 'Asam Folat%')->first();
        $stok = $folat->stok;
        $this->actingAs($this->akun('dr.kandungan'))->post("/praktik/antrean/{$a->kode}/periksa", [
            'keluhan' => 'Kaki bengkak', 'diagnosis' => 'Kehamilan normal 20 minggu',
            'obat' => [['id_obat' => $folat->id_obat, 'jumlah' => 30, 'aturan_pakai' => '1 x sehari']],
        ])->assertRedirect('/praktik/antrean');
        $r = Resep::first();
        $this->actingAs($this->akun('dr.kandungan'))->get("/praktik/riwayat-pasien/{$siti->pasien->id_pasien}")->assertOk()->assertSee('Kehamilan normal');

        // 9–10. Apoteker proses & serahkan
        $this->actingAs($siti)->post("/farmasi/resep/{$r->kode}/proses")->assertRedirect('/dashboard');
        $this->actingAs($this->akun('apoteker'))->post("/farmasi/resep/{$r->kode}/proses");
        $this->actingAs($this->akun('apoteker'))->post("/farmasi/resep/{$r->kode}/serahkan");
        $this->assertSame('diserahkan', $r->fresh()->status);
        $this->assertSame($stok - 30, $folat->fresh()->stok);
        $this->actingAs($this->akun('apoteker'))->get('/farmasi/riwayat-penyerahan')->assertOk()->assertSee('Siti Aminah');

        $langkah = $siti->notifications()->get()->pluck('data.langkah')->filter()->unique()->sort()->values()->all();
        $this->assertSame([1, 3, 4, 5, 6, 7, 8, 9, 10], $langkah);

        // Pengaduan pasien -> loket menanggapi -> pasien diberi tahu
        $this->actingAs($siti)->post('/pasien/pengaduan', ['kategori' => 'antrean', 'judul' => 'Antrean lama', 'isi' => 'Menunggu terlalu lama di loket.'])->assertRedirect();
        $p = \App\Models\Pengaduan::first();
        $this->actingAs($this->akun('loket'))->put("/pengaduan/{$p->kode}", ['status' => 'selesai', 'tanggapan' => 'Petugas ditambah.'])->assertRedirect();
        $this->assertTrue($siti->notifications()->where('data->judul', 'like', 'Pengaduan%')->exists());

        // Laporan & audit
        $this->actingAs($this->akun('superadmin'))->get('/laporan')->assertOk()->assertSee('Asam Folat');
        $this->actingAs($this->akun('superadmin'))->get('/admin/audit')->assertOk()->assertSee('qr_tolak');
        $this->actingAs($this->akun('admin'))->get('/laporan')->assertOk();
        $this->actingAs($this->akun('apoteker'))->get('/laporan')->assertRedirect('/dashboard');
    }

    public function test_navigasi_dan_pembatasan_tiap_peran(): void
    {
        $boleh = [
            'pasien' => ['/pasien/daftar-berobat', '/pasien/antrean', '/pasien/riwayat', '/pasien/resep', '/notifikasi', '/pasien/pengaduan', '/profil'],
            'dr.umum' => ['/praktik/antrean', '/praktik/pemeriksaan', '/praktik/resep', '/praktik/riwayat-pasien', '/praktik/jadwal-saya', '/praktik/konsultasi'],
            'apoteker' => ['/farmasi/resep?status=baru', '/farmasi/resep?status=diproses', '/obat', '/farmasi/riwayat-penyerahan'],
            'loket' => ['/loket/pasien', '/loket/pasien/create', '/loket/pendaftaran?q=Budi', '/loket/antrean', '/loket/checkin-qr', '/pengaduan'],
            'superadmin' => ['/admin/akun', '/admin/akun/create', '/admin/peran', '/admin/jadwal', '/admin/pengumuman', '/admin/template-notifikasi', '/laporan', '/admin/audit', '/admin/pengaturan', '/admin/pasien', '/admin/log-login'],
            'admin' => ['/admin/jadwal', '/admin/pengumuman', '/pengaduan', '/admin/template-notifikasi', '/laporan'],
        ];
        foreach ($boleh as $u => $urls) {
            foreach ($urls as $url) {
                $this->actingAs($this->akun($u))->get($url)->assertOk();
            }
        }
        $dilarang = [
            'admin' => ['/admin/akun', '/admin/peran', '/admin/audit', '/admin/pengaturan'],
            'loket' => ['/admin/akun', '/obat', '/laporan'],
            'dr.umum' => ['/loket/antrean', '/farmasi/resep', '/pengaduan'],
            'apoteker' => ['/praktik/antrean', '/loket/checkin-qr'],
            'pasien' => ['/admin/peran', '/pengaduan', '/loket/pasien'],
        ];
        foreach ($dilarang as $u => $urls) {
            foreach ($urls as $url) {
                $this->actingAs($this->akun($u))->get($url)->assertRedirect('/dashboard');
            }
        }
        // Admin bisa kirim alert broadcast real-time ke peran
        $this->actingAs($this->akun('admin'))->post('/admin/broadcast', ['peran' => ['dokter_umum', 'dokter_kandungan'], 'judul' => 'Rapat', 'pesan' => 'Rapat pukul 13.00', 'tipe' => 'warning'])->assertRedirect();
        $this->assertTrue($this->akun('dr.kandungan')->notifications()->where('data->judul', 'Rapat')->exists());
        // ... tetapi tidak boleh mengubah template
        $this->actingAs($this->akun('admin'))->put('/admin/template-notifikasi/1', ['judul' => 'x', 'pesan' => 'y'])->assertRedirect('/dashboard');
        // Versi real-time bisa dibaca semua peran
        $this->actingAs($this->akun('loket'))->getJson('/realtime/versi')->assertOk()->assertJsonStructure(['v' => ['antrian', 'resep', 'konsultasi', 'pengaduan']]);
    }

    public function test_api_kiosk_hmac_dan_crud_token(): void
    {
        // --- Kiosk: tanda tangan HMAC ---
        $this->actingAs($this->akun('pasien'))->post('/pasien/antrean', ['id_spesialisasi' => 1, 'tanggal' => today()->toDateString()]);
        $qr = Hmac::qrTiket(Antrian::first());
        $this->app['auth']->forgetGuards();

        $kirim = function (string $method, string $path, array $body = [], ?string $secret = null, ?int $ts = null) {
            $json = $body ? json_encode($body) : '';
            $ts ??= time();
            $sig = Hmac::tanda(Hmac::kanonik($method, $path, (string) $ts, $json), $secret ?? 'rahasia-kiosk-uji');
            return $this->call($method, '/'.$path, [], [], [], [
                'HTTP_X_KIOSK_ID' => 'kiosk-uji', 'HTTP_X_TIMESTAMP' => (string) $ts, 'HTTP_X_SIGNATURE' => $sig,
                'CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json',
            ], $json);
        };
        $this->getJson('/api/v1/kiosk/antrian')->assertUnauthorized();
        $kirim('GET', 'api/v1/kiosk/antrian', [], 'kunci-salah')->assertUnauthorized();
        $kirim('GET', 'api/v1/kiosk/antrian', [], null, time() - 3600)->assertUnauthorized();
        $kirim('GET', 'api/v1/kiosk/antrian')->assertOk()->assertJsonStructure(['poli']);
        $kirim('POST', 'api/v1/kiosk/checkin', ['qr' => substr($qr, 0, -2).'00'])->assertStatus(422);
        $kirim('POST', 'api/v1/kiosk/checkin', ['qr' => $qr])->assertOk()->assertJsonPath('nomor', 'U-001');

        // Replay (header & body identik) ditolak
        $ts = time() - 5;
        $kirim('GET', 'api/v1/kiosk/antrian', [], null, $ts)->assertOk();
        $kirim('GET', 'api/v1/kiosk/antrian', [], null, $ts)->assertUnauthorized();

        // --- API token (username) + CRUD ---
        $token = $this->postJson('/api/v1/auth/login', ['login' => 'apoteker', 'password' => $this->sandi('apoteker')])->assertOk()->json('token');
        $h = ['Authorization' => "Bearer $token"];
        $id = $this->postJson('/api/v1/obat', ['nama_obat' => 'Metformin 500 mg', 'satuan' => 'tablet', 'stok' => 100], $h)->assertCreated()->json('data.id_obat');
        $this->putJson("/api/v1/obat/$id", ['nama_obat' => 'Metformin 500 mg', 'satuan' => 'tablet', 'stok' => 80], $h)->assertOk()->assertJsonPath('data.stok', 80);
        $this->deleteJson("/api/v1/obat/$id", [], $h)->assertNoContent();
        $this->getJson('/api/v1/laporan/bulanan', $h)->assertForbidden();

        $this->app['auth']->forgetGuards();
        $this->postJson('/api/v1/auth/register', [
            'nik' => '3171010101990009', 'no_kk' => '3171010101990010', 'nama' => 'Ani', 'tgl_lahir' => '2000-01-01', 'jenis_kelamin' => 'P', 'no_tlp' => '081211112222',
            'alamat' => 'Jl. A', 'username' => 'ani_01', 'email' => 'ani01@gmail.com', 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
        ])->assertCreated()->assertJsonPath('akun.username', 'ani_01')->assertJsonStructure(['token']);
        $this->postJson('/api/v1/auth/register', ['username' => 'ani_01'])->assertStatus(409);
        $this->app['auth']->forgetGuards();
        $this->postJson('/api/v1/auth/login', ['login' => 'ani_01', 'password' => 'rahasia123'])->assertOk()->assertJsonStructure(['token']);
        // Fitur opsional: OTP login lewat API bila diaktifkan
        config(['otp.login_perangkat' => true]);
        $this->app['auth']->forgetGuards();
        $ref = $this->postJson('/api/v1/auth/login', ['login' => 'ani_01', 'password' => 'rahasia123'])->assertStatus(202)->json('otp_ref');
        $this->postJson('/api/v1/auth/login/verifikasi', ['otp_ref' => $ref, 'kode' => $this->kodeOtp('ani01@gmail.com')])->assertOk()->assertJsonStructure(['token']);
    }

    public function test_profil_avatar_dan_disable_hp_alamat(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $pasienAkun = $this->akun('pasien');
        $pasienModel = $pasienAkun->pasien;
        $noTlpAwal = $pasienModel->no_tlp;
        $alamatAwal = $pasienModel->alamat;

        // 1. Halaman profil menampilkan Nomor HP dan Alamat terkunci/disabled
        $res = $this->actingAs($pasienAkun)->get('/profil')->assertOk();
        $res->assertSee('Terkunci (Sesuai Registrasi)');
        $res->assertSee('disabled', false);

        // 2. Upaya mengubah nomor HP dan alamat melalui form profil pasien diabaikan (tetap data awal)
        $this->actingAs($pasienAkun)->put('/profil', [
            'email' => 'pasien.baru@example.com',
            'no_tlp' => '089999999999',
            'alamat' => 'Alamat Berubah Ilegal',
            'wa_opt_in' => '1',
        ])->assertSessionHas('alert');

        $pasienModel->refresh();
        $this->assertSame($noTlpAwal, $pasienModel->no_tlp);
        $this->assertSame($alamatAwal, $pasienModel->alamat);

        // 3. Pasien dapat mengunggah avatar foto dengan format/ukuran standar (.jpg/.png)
        $foto = \Illuminate\Http\UploadedFile::fake()->image('profil.jpg', 300, 300)->size(500); // 500 KB
        $this->actingAs($pasienAkun)->put('/profil', [
            'email' => 'pasien.baru@example.com',
            'foto' => $foto,
        ])->assertSessionHas('alert');

        $pasienAkun->refresh();
        $this->assertNotNull($pasienAkun->foto);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($pasienAkun->foto);

        // 4. Endpoint avatar mengembalikan file foto yang diunggah
        $this->get('/avatar/'.$pasienAkun->id_akun)->assertOk();

        // 5. Pasien dapat menghapus foto dan kembali ke inisial SVG
        $this->actingAs($pasienAkun)->put('/profil', [
            'email' => 'pasien.baru@example.com',
            'hapus_foto' => '1',
        ])->assertSessionHas('alert');

        $pasienAkun->refresh();
        $this->assertNull($pasienAkun->foto);
        $resSvg = $this->get('/avatar/'.$pasienAkun->id_akun)->assertOk();
        $this->assertStringContainsString('image/svg+xml', $resSvg->headers->get('Content-Type'));
    }
}
