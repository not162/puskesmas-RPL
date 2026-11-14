<?php

namespace Tests\Feature;

use App\Mail\KodeOtpMail;
use App\Models\Akun;
use App\Models\Antrian;
use App\Models\Konsultasi;
use App\Models\Obat;
use App\Models\Resep;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * UAT otomatis: untuk tiap peran, jelajahi semua tautan GET yang TAMPIL di halaman (2 tingkat).
 * Aturan lulus:
 *  - tidak ada error server (5xx) maupun 404 di tautan internal;
 *  - tautan yang ditampilkan ke sebuah peran tidak boleh berakhir "Akses ditolak"
 *    (artinya menu/tombol tampil untuk peran yang tidak berhak).
 */
class UatJelajahTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;
    private array $temuan = [];

    private function akun(string $u): Akun
    {
        return Akun::where('username', $u)->firstOrFail();
    }

    /** Siapkan data nyata di semua status agar halaman detail ikut terjelajah. */
    private function siapkanData(): void
    {
        Mail::fake();
        $p = $this->akun('pasien');
        $p->pasien->update(['wa_opt_in' => true]);
        $this->actingAs($p)->post('/pasien/konsultasi', ['id_spesialisasi' => 1, 'keluhan' => 'Demam tiga hari dan batuk']);
        $k = Konsultasi::first();
        $this->actingAs($this->akun('dr.umum'))->post("/praktik/konsultasi/{$k->kode}/tanggapi", ['tanggapan' => 'Silakan datang.']);
        $this->actingAs($p)->post('/pasien/antrean', ['id_spesialisasi' => 1, 'tanggal' => today()->toDateString(), 'konsultasi' => $k->kode]);
        $this->actingAs($p)->post('/pasien/antrean', ['id_spesialisasi' => 2, 'tanggal' => today()->addDay()->toDateString()]);
        $a = Antrian::first();
        $this->actingAs($this->akun('loket'))->post("/loket/antrean/{$a->kode}/checkin");
        $this->actingAs($this->akun('dr.umum'))->post("/praktik/antrean/{$a->kode}/panggil");
        $obat = Obat::first();
        $this->actingAs($this->akun('dr.umum'))->post("/praktik/antrean/{$a->kode}/periksa", [
            'keluhan' => 'Demam', 'diagnosis' => 'ISPA', 'obat' => [['id_obat' => $obat->id_obat, 'jumlah' => 10, 'aturan_pakai' => '3x1']],
        ]);
        $r = Resep::first();
        $this->actingAs($this->akun('apoteker'))->post("/farmasi/resep/{$r->kode}/proses");
        $this->actingAs($p)->post('/pasien/pengaduan', ['kategori' => 'layanan', 'judul' => 'Ruang tunggu panas', 'isi' => 'Kipas angin di ruang tunggu mati.']);
        $this->actingAs($this->akun('loket'))->post('/loket/pasien', ['nik' => '3171010101990900', 'nama' => 'Pasien Loket', 'id_spesialisasi' => 2]);
        $this->assertSame('diproses', $r->fresh()->status);
    }

    private function jelajah(string $username): array
    {
        $akun = $this->akun($username);
        $antrean = ['/dashboard' => 'awal'];
        $dikunjungi = [];
        $kedalaman = ['/dashboard' => 0];

        while ($antrean) {
            $url = array_key_first($antrean);
            $asal = array_shift($antrean);
            if (isset($dikunjungi[$url]) || count($dikunjungi) > 160) continue;
            $dikunjungi[$url] = true;

            $res = $this->actingAs($akun)->get($url);
            $kode = $res->getStatusCode();
            $lok = $res->headers->get('Location');

            if ($kode >= 500 || $kode === 404) {
                $this->temuan[] = "[$username] $url → HTTP $kode (dari $asal)";
                continue;
            }
            if ($kode === 403) {
                $this->temuan[] = "[$username] $url → 403 (tautan tampil di $asal)";
                continue;
            }
            if ($kode >= 300 && $kode < 400) {
                $alert = session('alert');
                if (($alert['judul'] ?? '') === 'Akses ditolak') {
                    $this->temuan[] = "[$username] $url → Akses ditolak (tautan tampil di $asal)";
                }
                continue;
            }
            $ct = (string) $res->headers->get('Content-Type');
            if (! str_contains($ct, 'text/html') || ($kedalaman[$url] ?? 0) >= 2) continue;

            preg_match_all('/<a\s[^>]*href="([^"#]+)"/i', $res->getContent(), $m);
            foreach (array_unique($m[1]) as $href) {
                $href = html_entity_decode($href);
                $href = preg_replace('#^https?://(localhost|'.preg_quote(parse_url(config('app.url'), PHP_URL_HOST)).')(:\d+)?#', '', $href);
                if (! str_starts_with($href, '/') || str_starts_with($href, '//')) continue;   // eksternal
                if (preg_match('#^/(logout|notifikasi/[^/]+/baca|api/|storage/)#', $href)) continue; // aksi / API
                if (! isset($dikunjungi[$href]) && ! isset($antrean[$href])) {
                    $antrean[$href] = $url;
                    $kedalaman[$href] = ($kedalaman[$url] ?? 0) + 1;
                }
            }
        }
        return array_keys($dikunjungi);
    }

    public function test_jelajah_semua_tautan_tiap_peran(): void
    {
        $this->siapkanData();
        $ringkas = [];
        foreach (['superadmin', 'admin', 'dr.umum', 'dr.kandungan', 'apoteker', 'loket', 'pasien'] as $u) {
            $ringkas[$u] = count($this->jelajah($u));
        }
        fwrite(STDERR, "\nHalaman terjelajah per peran: ".json_encode($ringkas)."\n");
        $this->assertSame([], $this->temuan, "Temuan UAT:\n".implode("\n", $this->temuan));
        foreach ($ringkas as $u => $n) {
            $this->assertGreaterThan(8, $n, "Terlalu sedikit halaman terjelajah untuk $u");
        }
    }

    public function test_unduh_data_saya_uu_pdp(): void
    {
        $this->siapkanData();
        $res = $this->actingAs($this->akun('pasien'))->get('/profil/unduh-data')->assertOk();
        $json = json_decode($res->streamedContent(), true);
        $this->assertSame('3171010101900001', $json['profil']['nik']);
        $this->assertNotEmpty($json['antrean']);
        $this->assertSame('ISPA', collect($json['konsultasi_dan_pemeriksaan'])->whereNotNull('diagnosis')->first()['diagnosis']);
        $this->assertStringNotContainsString('Pasien Loket', $res->streamedContent()); // tidak memuat data pasien lain
        $this->assertTrue(\App\Models\AuditLog::where('aksi', 'unduh_data')->exists());
    }

    public function test_header_keamanan(): void
    {
        $res = $this->get('/');
        $res->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertStringContainsString('camera=(self)', $res->headers->get('Permissions-Policy'));
        $this->actingAs($this->akun('pasien'))->get('/dashboard')->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_halaman_publik_tanpa_login(): void
    {
        foreach (['/', '/layar-antrean', '/jadwal', '/alur-layanan', '/informasi', '/kebijakan-privasi', '/login', '/pasien/masuk', '/register', '/lupa-password', '/api/docs'] as $u) {
            $res = $this->get($u);
            $this->assertSame(200, $res->getStatusCode(), $u);
            $this->assertStringContainsString('NussCorp Technologies Indonesia', $res->getContent(), "Footer hak cipta hilang di $u");
        }
        // Halaman privat mengarah ke login
        foreach (['/dashboard', '/pasien/antrean', '/loket/antrean', '/admin/akun', '/laporan'] as $u) {
            $this->get($u)->assertRedirect('/login');
        }
    }
}
