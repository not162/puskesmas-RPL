<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\Antrian;
use App\Models\Obat;
use App\Support\RekomendasiObat;
use App\Support\Siaran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class FormulariumTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function akun(string $u): Akun
    {
        return Akun::where('username', $u)->firstOrFail();
    }

    private function id(string $kode): int
    {
        return Obat::where('kode', $kode)->value('id_obat');
    }

    public function test_formularium_20_obat_dan_tips_tersedia(): void
    {
        $this->assertSame(20, Obat::where('kode', 'like', 'OBT-%')->count());
        $p = Obat::where('kode', 'OBT-001')->first();
        $this->assertSame('Parasetamol 500 mg', $p->nama_obat);
        $this->assertSame('3x1 sesudah makan jika demam', $p->aturan_bawaan);
        $this->assertSame('antibiotik', Obat::where('kode', 'OBT-002')->value('golongan'));
        $this->assertSame('hindari', Obat::where('kode', 'OBT-003')->value('keamanan_hamil'));
        $this->assertSame('kandungan', Obat::where('kode', 'OBT-011')->value('poli'));
        $this->get('/informasi')->assertOk()->assertSee('Bijak menggunakan obat')->assertSee('Ibu hamil: kenali tanda bahaya');
    }

    public function test_crud_obat_rbac_dan_siaran_realtime(): void
    {
        $apt = $this->akun('apoteker');
        $data = ['kode' => 'OBT-021', 'nama_obat' => 'Dexamethasone 0,5 mg', 'kategori' => 'Demam, Nyeri & Peradangan', 'bentuk' => 'Tablet',
            'zat_aktif' => 'Dexamethasone', 'poli' => 'umum', 'keamanan_hamil' => 'konsultasi', 'kegunaan' => 'Antiradang steroid',
            'indikasi' => 'radang, alergi berat', 'satuan' => 'tablet', 'jumlah_bawaan' => 10, 'aturan_bawaan' => '3x1 sesudah makan',
            'stok' => 100, 'stok_minimum' => 20, 'aktif' => '1'];

        // Create
        Siaran::$terkirim = [];
        config(['broadcasting.default' => 'log']);
        $this->actingAs($apt)->post('/obat', $data)->assertRedirect('/obat');
        $o = Obat::where('kode', 'OBT-021')->first();
        $this->assertNotNull($o);
        $this->assertContains('obat', collect(Siaran::$terkirim)->firstWhere(0, 'staf')[1] ?? [], 'Perubahan obat harus disiarkan ke kanal staf');
        // Validasi kode unik & format
        $this->actingAs($apt)->post('/obat', ['kode' => 'OBT-001'] + $data)->assertSessionHasErrors('kode');
        $this->actingAs($apt)->post('/obat', ['kode' => 'X1'] + $data)->assertSessionHasErrors('kode');
        // Update
        $this->actingAs($apt)->put("/obat/{$o->id_obat}", ['stok' => 5] + $data)->assertRedirect('/obat');
        $this->assertTrue($o->fresh()->stokMenipis());
        // Nonaktif -> tidak muncul di pemilih dokter
        $this->actingAs($apt)->put("/obat/{$o->id_obat}", ['aktif' => null] + $data);
        $this->assertFalse($o->fresh()->aktif);
        // Delete (belum pernah diresepkan)
        $this->actingAs($apt)->delete("/obat/{$o->id_obat}")->assertRedirect();
        $this->assertNull(Obat::find($o->id_obat));

        // RBAC
        $this->actingAs($this->akun('dr.umum'))->get('/obat')->assertRedirect('/dashboard');
        $this->actingAs($this->akun('dr.umum'))->post('/obat', $data)->assertRedirect('/dashboard');
        $this->actingAs($this->akun('dr.umum'))->get('/formularium')->assertOk()->assertSee('Parasetamol 500 mg')->assertSee('OBT-020');
        $this->actingAs($this->akun('apoteker'))->get('/formularium')->assertOk();
        $this->actingAs($this->akun('loket'))->get('/formularium')->assertRedirect('/dashboard');
        $this->actingAs($this->akun('pasien'))->get('/formularium')->assertRedirect('/dashboard');
        $this->actingAs($this->akun('superadmin'))->get('/obat?q=demam')->assertOk()->assertSee('Parasetamol');
    }

    public function test_rekomendasi_obat_hibrida_dan_belajar_dari_resep(): void
    {
        Mail::fake();
        $dk = $this->akun('dr.kandungan');
        // Poli Kandungan, Asam Folat dipilih -> Fe & Kalsium teratas, Ibuprofen diberi peringatan
        $r = $this->actingAs($dk)->getJson('/praktik/rekomendasi-obat?'.http_build_query(['teks' => 'Kontrol ANC trimester 2, nyeri kepala ringan', 'dipilih' => [$this->id('OBT-010')]]))->assertOk()->json('saran');
        $this->assertSame('OBT-011', $r[0]['kode']);
        $this->assertContains('OBT-012', array_column($r, 'kode'));
        $this->assertNotContains('OBT-010', array_column($r, 'kode'), 'Obat yang sudah dipilih tidak disarankan lagi');
        $ibu = collect($r)->firstWhere('kode', 'OBT-003');
        if ($ibu) $this->assertNotEmpty($ibu['peringatan']);
        $para = collect($r)->firstWhere('kode', 'OBT-001');
        $this->assertTrue(! $ibu || $para['skor'] > $ibu['skor'], 'Parasetamol harus lebih disarankan daripada Ibuprofen untuk ibu hamil');

        // Poli Umum: ISPA + Parasetamol -> Vitamin B Kompleks (contoh U-001)
        $du = $this->akun('dr.umum');
        $r = $this->actingAs($du)->getJson('/praktik/rekomendasi-obat?'.http_build_query(['teks' => 'demam dan batuk berdahak, ISPA ringan', 'dipilih' => [$this->id('OBT-001')]]))->json('saran');
        $this->assertSame('OBT-009', $r[0]['kode']);
        $this->assertStringContainsString('Parasetamol', $r[0]['alasan'][0]);
        $this->assertContains('OBT-014', array_slice(array_column($r, 'kode'), 0, 3));

        // Graf belajar: resep baru Cetirizine + Ambroxol membuat sisi baru di graf
        $sebelum = RekomendasiObat::graf()[$this->id('OBT-004')][$this->id('OBT-014')] ?? 0;
        $this->actingAs($this->akun('pasien'))->post('/pasien/antrean', ['id_spesialisasi' => 1, 'tanggal' => today()->toDateString()]);
        $a = Antrian::first();
        $this->actingAs($this->akun('loket'))->post("/loket/antrean/{$a->kode}/checkin");
        $this->actingAs($du)->post("/praktik/antrean/{$a->kode}/panggil");
        $this->actingAs($du)->post("/praktik/antrean/{$a->kode}/periksa", ['keluhan' => 'bersin gatal dan batuk berdahak', 'diagnosis' => 'rinitis alergi', 'obat' => [
            ['id_obat' => $this->id('OBT-004'), 'jumlah' => 10, 'aturan_pakai' => '1x1 malam'],
            ['id_obat' => $this->id('OBT-014'), 'jumlah' => 10, 'aturan_pakai' => '3x1'],
        ]])->assertRedirect('/praktik/antrean');
        $this->assertSame($sebelum + 1, RekomendasiObat::graf()[$this->id('OBT-004')][$this->id('OBT-014')]);
        // Kolaboratif: kasus "rinitis" serupa kini menyarankan obat yang dipakai
        $r = $this->actingAs($du)->getJson('/praktik/rekomendasi-obat?'.http_build_query(['teks' => 'rinitis alergi kambuh']))->json('saran');
        $ceti = collect($r)->firstWhere('kode', 'OBT-004');
        $this->assertNotNull($ceti);
        $this->assertGreaterThan(0, $ceti['rincian']['kolaboratif']);

        // RBAC: hanya dokter berizin resep.buat
        $this->actingAs($this->akun('apoteker'))->getJson('/praktik/rekomendasi-obat?teks=demam')->assertForbidden();
        $this->actingAs($this->akun('pasien'))->getJson('/praktik/rekomendasi-obat?teks=demam')->assertForbidden();
    }

    public function test_halaman_periksa_menampilkan_pemilih_obat_berkelompok(): void
    {
        Mail::fake();
        $this->actingAs($this->akun('pasien'))->post('/pasien/antrean', ['id_spesialisasi' => 2, 'tanggal' => today()->toDateString()]);
        $a = Antrian::first();
        $this->actingAs($this->akun('loket'))->post("/loket/antrean/{$a->kode}/checkin");
        $html = $this->actingAs($this->akun('dr.kandungan'))->get("/praktik/antrean/{$a->kode}/periksa")->assertOk()->getContent();
        $this->assertStringContainsString('<optgroup label="Suplemen Ibu Hamil (KIA/ANC)">', $html);
        $this->assertStringContainsString('OBT-011 · Tablet Tambah Darah (Fe) · Tablet', $html);
        $this->assertStringContainsString('data-aturan="1x1 malam sebelum tidur"', $html);
        $this->assertStringContainsString('Saran obat', $html);
        $this->assertStringContainsString('perhatikan keamanan obat bagi ibu hamil', $html);
    }
}
