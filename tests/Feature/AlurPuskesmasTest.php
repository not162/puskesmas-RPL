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
            $res = $this->withSession(['captcha' => 7])->post('/login', ['login' => $username, 'password' => $this->sandi($p), 'captcha' => 7]);
            if ($p === 'pasien') {
                $res->assertRedirect('/login/verifikasi');
                $this->assertGuest();
                $this->post('/login/verifikasi', ['kode' => '000000'])->assertSessionHasErrors('kode');
                $res = $this->post('/login/verifikasi', ['kode' => $this->kodeOtp('pasien@puskesmas.test'), 'percaya' => '1']);
            }
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
        $this->post('/lupa-password', ['login' => 'tidak.ada'])->assertRedirect('/lupa-password/verifikasi'); // respons sama, tidak bocor
        Mail::assertNothingSent();

        $this->post('/lupa-password', ['login' => 'pasien'])->assertRedirect('/lupa-password/verifikasi');
        Mail::assertSent(KodeOtpMail::class, fn ($m) => $m->hasTo('pasien@puskesmas.test') && $m->tujuan === 'reset');
        $this->post('/lupa-password/verifikasi', ['kode' => '123123', 'password' => 'BaruSekali9', 'password_confirmation' => 'BaruSekali9'])->assertSessionHasErrors('kode');
        $this->post('/lupa-password/verifikasi', ['kode' => $this->kodeOtp(), 'password' => 'BaruSekali9', 'password_confirmation' => 'BaruSekali9'])->assertRedirect('/login');
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('BaruSekali9', $this->akun('pasien')->fresh()->password));
    }

    public function test_otp_mode_antrean_tidak_menahan_halaman(): void
    {
        config(['otp.pengiriman' => 'antrean']);
        $this->post('/lupa-password', ['login' => 'pasien'])->assertRedirect('/lupa-password/verifikasi');
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

    public function test_data_ganda_diarahkan_ke_login_atau_lupa_password(): void
    {
        $dasar = ['nik' => '3171010101990077', 'no_kk' => '3171010101990000', 'nama' => 'Pasien Baru', 'tgl_lahir' => '1990-02-02', 'jenis_kelamin' => 'L',
            'alamat' => 'Jl. B', 'username' => 'pasien.baru', 'no_tlp' => '081211113333', 'email' => 'pasien.baru@gmail.com',
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123', 'setuju' => '1'];
        foreach ([['username' => 'pasien'], ['email' => 'pasien@puskesmas.test'], ['nik' => '3171010101900001'], ['nama' => '  budi   SANTOSO '], ['no_kk' => '3171010101900099']] as $i => $ubah) {
            if ($i === 4) \App\Models\Pasien::where('nik', '3171010101900001')->update(['no_kk' => '3171010101900099']);
            $this->post('/register', array_merge($dasar, $ubah))->assertRedirect('/login')->assertSessionHas('duplikat');
        }
        Mail::assertNothingSent();
        $this->getJson('/register/cek?kolom=username&nilai=pasien')->assertJson(['terdaftar' => true]);
        $this->getJson('/register/cek?kolom=username&nilai=belum.ada')->assertJson(['terdaftar' => false]);

        // Data baru lolos -> OTP dikirim ke email pengguna, akun baru dibuat setelah kode benar
        $this->post('/register', $dasar)->assertRedirect('/register/verifikasi');
        $this->assertNull(Akun::where('username', 'pasien.baru')->first());
        $this->post('/register/verifikasi', ['kode' => $this->kodeOtp('pasien.baru@gmail.com')])->assertRedirect('/dashboard');
        $this->assertSame('3171010101990000', Akun::where('username', 'pasien.baru')->first()->pasien->no_kk);
    }

    public function test_alur_lengkap_dengan_notifikasi_qr_hmac_dan_realtime(): void
    {
        Storage::fake('local');
        $versiAwal = RealtimeController::versi();

        // 1. Registrasi (hanya pasien) — form sesuai layout
        $this->post('/register', [
            'nik' => '3171010101990002', 'nama' => 'Siti Aminah', 'tgl_lahir' => '1995-05-05', 'jenis_kelamin' => 'P',
            'alamat' => 'Jl. Mawar 1', 'username' => 'siti.aminah', 'no_tlp' => '081298765432', 'email' => 'siti.aminah@gmail.com', 'no_kk' => '3171010101990111',
            'no_bpjs' => '0009876543210', 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
            'foto_ktp' => UploadedFile::fake()->image('ktp.jpg'), 'setuju' => '1',
        ])->assertRedirect('/register/verifikasi');
        $this->post('/register/verifikasi', ['kode' => $this->kodeOtp('siti.aminah@gmail.com')])->assertRedirect('/dashboard');
        $siti = Akun::where('username', 'siti.aminah')->first();
        $this->assertSame('pasien', $siti->role());
        $this->assertSame('siti.aminah@gmail.com', $siti->email);
        Storage::disk('local')->assertExists($siti->pasien->foto_ktp);
        // Username duplikat ditolak
        $this->post('/logout');
        $this->post('/register', ['username' => 'siti.aminah', 'nik' => '3171010101990003'])->assertRedirect('/login')->assertSessionHas('duplikat', 'Username');

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
        $ref = $this->postJson('/api/v1/auth/register', [
            'nik' => '3171010101990009', 'no_kk' => '3171010101990010', 'nama' => 'Ani', 'tgl_lahir' => '2000-01-01', 'jenis_kelamin' => 'P', 'no_tlp' => '081211112222',
            'alamat' => 'Jl. A', 'username' => 'ani_01', 'email' => 'ani01@gmail.com', 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
        ])->assertStatus(202)->json('otp_ref');
        $this->postJson('/api/v1/auth/register/verifikasi', ['otp_ref' => $ref, 'kode' => $this->kodeOtp('ani01@gmail.com')])->assertCreated()->assertJsonPath('akun.username', 'ani_01');
        $this->postJson('/api/v1/auth/register', ['username' => 'ani_01'])->assertStatus(409);
        $this->app['auth']->forgetGuards();
        $ref = $this->postJson('/api/v1/auth/login', ['login' => 'ani_01', 'password' => 'rahasia123'])->assertStatus(202)->json('otp_ref');
        $this->postJson('/api/v1/auth/login/verifikasi', ['otp_ref' => $ref, 'kode' => $this->kodeOtp('ani01@gmail.com')])->assertOk()->assertJsonStructure(['token']);
    }
}
