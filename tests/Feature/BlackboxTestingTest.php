<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\Antrian;
use App\Models\Dokter;
use App\Models\JadwalPraktik;
use App\Models\Konsultasi;
use App\Models\Obat;
use App\Models\Pasien;
use App\Models\Pengaduan;
use App\Models\Pengumuman;
use App\Models\Peran;
use App\Models\Resep;
use App\Models\Spesialisasi;
use App\Support\Hmac;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BlackboxTestingTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Mail::fake();
    }

    private function akun(string $username): Akun
    {
        return Akun::where('username', $username)->firstOrFail();
    }

    /**
     * 1. Test Realtime API Endpoints (Local & Hosting Ready)
     */
    public function test_blackbox_realtime_api_endpoints(): void
    {
        // Realtime sync version endpoint
        $res = $this->getJson('/api/v1/realtime');
        $res->assertOk()
            ->assertJsonStructure(['v' => ['antrian', 'konsultasi', 'resep', 'obat', 'pasien', 'pengaduan', 'jadwal', 'pengumuman', 'akun'], 't']);

        // Public API endpoints
        $this->getJson('/api/v1/public/antrian')->assertOk();
        $this->getJson('/api/v1/public/dokter')->assertOk();
        $this->getJson('/api/v1/public/pengumuman')->assertOk();
        $this->getJson('/api/v1/public/alur')->assertOk();

        // Kiosk HMAC security verification
        $this->getJson('/api/v1/kiosk/antrian')->assertStatus(401);

        $ts = time();
        $sig = Hmac::tanda(Hmac::kanonik('GET', 'api/v1/kiosk/antrian', (string) $ts, ''), 'rahasia-kiosk-uji');

        $this->call('GET', '/api/v1/kiosk/antrian', [], [], [], [
            'HTTP_X_KIOSK_ID' => 'kiosk-uji',
            'HTTP_X_TIMESTAMP' => (string) $ts,
            'HTTP_X_SIGNATURE' => $sig,
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ])->assertOk()->assertJsonStructure(['poli']);
    }

    /**
     * 2. Test All Roles Interconnected Workflow (Pasien -> Loket -> Dokter -> Farmasi)
     */
    public function test_blackbox_all_roles_interconnected_workflow(): void
    {
        $pasienAkun = $this->akun('pasien');
        $loketAkun = $this->akun('loket');
        $dokterAkun = $this->akun('dr.umum');
        $apotekerAkun = $this->akun('apoteker');

        // A. Pasien mengambil antrean online
        $sp = Spesialisasi::where('nama', 'Umum')->firstOrFail();
        $this->actingAs($pasienAkun)->post(route('pasien.antrian.store'), [
            'id_spesialisasi' => $sp->id_spesialisasi,
            'tanggal' => today()->toDateString(),
            'keluhan_awal' => 'Demam dan pusing 2 hari',
        ])->assertSessionHas('alert');

        $antrian = Antrian::where('id_pasien', $pasienAkun->pasien->id_pasien)->latest('id_antrian')->firstOrFail();
        $this->assertEquals('menunggu', $antrian->status);

        // B. Loket melakukan check-in antrean
        $this->actingAs($loketAkun)->post(route('loket.antrian.checkin', $antrian))
            ->assertSessionHas('alert');
        $antrian->refresh();
        $this->assertEquals('hadir', $antrian->status);

        // C. Dokter memanggil dan memeriksa pasien
        $this->actingAs($dokterAkun)->post(route('dokter.panggil', $antrian))
            ->assertSessionHas('alert');
        $antrian->refresh();
        $this->assertEquals('dipanggil', $antrian->status);

        $obat = Obat::where('stok', '>', 10)->firstOrFail();
        $stokAwal = $obat->stok;

        $this->actingAs($dokterAkun)->post(route('dokter.periksa.simpan', $antrian), [
            'keluhan' => 'Demam dan sakit kepala berat',
            'diagnosis' => 'Cephalgia akut susp. ISPA',
            'obat' => [
                ['id_obat' => $obat->id_obat, 'jumlah' => 10, 'aturan_pakai' => '3x1 tablet sesudah makan'],
            ],
        ])->assertSessionHas('alert');

        $antrian->refresh();
        $this->assertEquals('selesai', $antrian->status);

        // Resep otomatis terbentuk dan terhubung ke farmasi
        $resep = Resep::where('id_pasien', $pasienAkun->pasien->id_pasien)->latest('id_resep')->firstOrFail();
        $this->assertEquals('baru', $resep->status);

        // D. Apoteker memproses dan menyerahkan obat
        $this->actingAs($apotekerAkun)->post(route('farmasi.resep.proses', $resep))
            ->assertSessionHas('alert');
        $resep->refresh();
        $this->assertEquals('diproses', $resep->status);

        $this->actingAs($apotekerAkun)->post(route('farmasi.resep.serahkan', $resep))
            ->assertSessionHas('alert');
        $resep->refresh();
        $this->assertEquals('diserahkan', $resep->status);

        // Verifikasi stok obat berkurang otomatis
        $obat->refresh();
        $this->assertEquals($stokAwal - 10, $obat->stok);

        // Verifikasi notifikasi sampai ke akun pasien
        $this->assertTrue($pasienAkun->notifications()->count() > 0);
    }

    /**
     * 3. Test CRUD Methods Across Entities
     */
    public function test_blackbox_crud_methods(): void
    {
        $adminAkun = $this->akun('superadmin');
        $apotekerAkun = $this->akun('apoteker');

        // CRUD Obat (Apoteker)
        $this->actingAs($apotekerAkun)->post(route('obat.store'), [
            'kode' => 'OBT-999',
            'nama_obat' => 'Paracetamol Blackbox Test 500mg',
            'kategori' => 'Demam, Nyeri & Peradangan',
            'bentuk' => 'Tablet',
            'satuan' => 'tablet',
            'poli' => 'umum',
            'keamanan_hamil' => 'aman',
            'jumlah_bawaan' => 10,
            'stok' => 100,
            'stok_minimum' => 20,
            'aktif' => 1,
        ])->assertSessionHas('alert');

        $obatBaru = Obat::where('kode', 'OBT-999')->firstOrFail();
        $this->assertEquals('Paracetamol Blackbox Test 500mg', $obatBaru->nama_obat);

        // Update Obat
        $this->actingAs($apotekerAkun)->put(route('obat.update', $obatBaru), [
            'kode' => 'OBT-999',
            'nama_obat' => 'Paracetamol Blackbox Test 650mg',
            'kategori' => 'Demam, Nyeri & Peradangan',
            'bentuk' => 'Tablet',
            'satuan' => 'tablet',
            'poli' => 'umum',
            'keamanan_hamil' => 'aman',
            'jumlah_bawaan' => 10,
            'stok' => 120,
            'stok_minimum' => 20,
            'aktif' => 1,
        ])->assertSessionHas('alert');
        $obatBaru->refresh();
        $this->assertEquals('Paracetamol Blackbox Test 650mg', $obatBaru->nama_obat);

        // Delete Obat
        $this->actingAs($apotekerAkun)->delete(route('obat.destroy', $obatBaru))
            ->assertSessionHas('alert');
        $this->assertDatabaseMissing('obat', ['kode' => 'OBT-999']);

        // CRUD Pengumuman (Admin)
        $this->actingAs($adminAkun)->post(route('admin.pengumuman.store'), [
            'judul' => 'Pengumuman Blackbox Test',
            'isi' => 'Isi pengumuman uji coba blackbox testing.',
            'kategori' => 'pengumuman',
            'tanggal' => today()->toDateString(),
            'terbit' => 1,
        ])->assertSessionHas('alert');

        $p = Pengumuman::where('judul', 'Pengumuman Blackbox Test')->firstOrFail();

        $this->actingAs($adminAkun)->put(route('admin.pengumuman.update', $p), [
            'judul' => 'Pengumuman Blackbox Test Terupdate',
            'isi' => 'Isi baru pengumuman.',
            'kategori' => 'agenda',
            'tanggal' => today()->toDateString(),
            'terbit' => 1,
        ])->assertSessionHas('alert');

        $this->actingAs($adminAkun)->delete(route('admin.pengumuman.destroy', $p))
            ->assertSessionHas('alert');
        $this->assertDatabaseMissing('pengumuman', ['id' => $p->id]);
    }

    /**
     * 4. Test Security Boundaries & Role Isolation
     */
    public function test_blackbox_security_and_role_isolation(): void
    {
        $pasienAkun = $this->akun('pasien');
        $dokterAkun = $this->akun('dr.umum');
        $loketAkun = $this->akun('loket');

        // Pasien tidak boleh mengakses portal admin/dokter/farmasi/loket
        $this->actingAs($pasienAkun)->get('/admin/akun')->assertRedirect(route('dashboard'));
        $this->actingAs($pasienAkun)->get('/praktik/antrean')->assertRedirect(route('dashboard'));
        $this->actingAs($pasienAkun)->get('/farmasi/resep')->assertRedirect(route('dashboard'));
        $this->actingAs($pasienAkun)->get('/loket/antrean')->assertRedirect(route('dashboard'));

        // Dokter tidak boleh mengakses antrean pasien poli lain
        $spKandungan = Spesialisasi::where('nama', 'Kandungan')->firstOrFail();
        $antrianKandungan = Antrian::create([
            'id_pasien' => $pasienAkun->pasien->id_pasien,
            'id_spesialisasi' => $spKandungan->id_spesialisasi,
            'tanggal' => today()->toDateString(),
            'nomor' => 'K-999',
            'status' => 'hadir',
            'sumber' => 'loket',
        ]);

        $this->actingAs($dokterAkun)->get(route('dokter.periksa', $antrianKandungan))
            ->assertStatus(403);

        // Proteksi brute force login & penguncian akun
        $this->app['auth']->forgetGuards();
        $login = fn (string $pw) => $this->withSession(['captcha' => 5])
            ->post('/login', ['login' => 'apoteker', 'password' => $pw, 'captcha' => 5]);

        for ($i = 1; $i <= 4; $i++) {
            $login('passwordsalah-'.$i)->assertSessionHasErrors('login');
            \Illuminate\Support\Facades\RateLimiter::clear('apoteker|127.0.0.1');
        }
        $this->assertFalse($this->akun('apoteker')->terkunci());

        $login('passwordsalah-5')->assertSessionHasErrors('login');
        $this->assertTrue($this->akun('apoteker')->terkunci());
    }
}
