<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\Antrian;
use App\Models\Pengaduan;
use App\Support\Hmac;
use App\Support\Pemantau;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PemeliharaanTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function akun(string $u): Akun
    {
        return Akun::where('username', $u)->firstOrFail();
    }

    public function test_pemantau_mendeteksi_masalah_dan_memberi_tahu_super_admin_sekali(): void
    {
        $kode = collect(Pemantau::periksa())->pluck('status', 'kode');
        $this->assertSame('ok', $kode['db']);

        // Pengaduan > 3 hari belum ditanggapi -> perhatian
        $p = Pengaduan::create(['id_akun' => $this->akun('pasien')->id_akun, 'kategori' => 'layanan', 'judul' => 'Lama', 'isi' => 'Belum dibalas sejak lama', 'status' => 'baru']);
        $p->forceFill(['created_at' => now()->subDays(4)])->save();
        // Job tertahan > 5 menit -> masalah
        \DB::table('jobs')->insert(['queue' => 'default', 'payload' => '{}', 'attempts' => 0, 'available_at' => now()->subHour()->timestamp, 'created_at' => now()->subHour()->timestamp]);
        $kode = collect(Pemantau::periksa())->pluck('status', 'kode');
        $this->assertSame('warn', $kode['pengaduan']);
        $this->assertSame('gagal', $kode['antrean']);

        $this->artisan('puskesmas:pantau --kirim')->assertFailed();
        $admin = $this->akun('superadmin');
        $this->assertSame(1, $admin->notifications()->where('data->judul', 'like', 'Kesehatan sistem%')->count());
        // Dijalankan lagi (mis. jam berikutnya): tidak mengirim ulang masalah yang sama di hari yang sama
        $this->artisan('puskesmas:pantau --kirim');
        $this->assertSame(1, $admin->fresh()->notifications()->where('data->judul', 'like', 'Kesehatan sistem%')->count());
        $this->assertNull($this->akun('admin')->notifications()->first());
    }

    public function test_hitung_error_log(): void
    {
        $f = storage_path('logs/laravel-uji-pantau.log');
        file_put_contents($f, '['.now()->subHour()->format('Y-m-d H:i:s')."] testing.ERROR: contoh galat baru\n"
            .'['.now()->subDays(3)->format('Y-m-d H:i:s')."] testing.ERROR: galat lama\n"
            .'['.now()->format('Y-m-d H:i:s')."] testing.INFO: bukan galat\n");
        try {
            $this->assertGreaterThanOrEqual(1, Pemantau::hitungError(24));
            $sebelum = Pemantau::hitungError(24);
            file_put_contents($f, '['.now()->format('Y-m-d H:i:s')."] testing.CRITICAL: galat kritis\n", FILE_APPEND);
            $this->assertSame($sebelum + 1, Pemantau::hitungError(24));
        } finally {
            @unlink($f);
        }
    }

    public function test_rotasi_kunci_hmac_tiket_lama_tetap_valid(): void
    {
        $env = sys_get_temp_dir().'/pkm-env-uji-'.uniqid();
        file_put_contents($env, "APP_NAME=Uji\nHMAC_SECRET=kunci-lama-uji\nKIOSK_HMAC_SECRET=kiosk-lama\n");
        config(['hmac.secret' => 'kunci-lama-uji']);
        $this->actingAs($this->akun('pasien'))->post('/pasien/antrean', ['id_spesialisasi' => 1, 'tanggal' => today()->toDateString()]);
        $a = Antrian::first();
        $qrLama = Hmac::qrTiket($a);

        $this->artisan('puskesmas:rotasi-kunci', ['--hmac' => true, '--kiosk' => true, '--berkas' => $env, '--paksa' => true])->assertSuccessful();
        $isi = file_get_contents($env);
        $this->assertStringContainsString('HMAC_SECRET_LAMA=kunci-lama-uji', $isi);
        preg_match('/^HMAC_SECRET=([a-f0-9]{64})$/m', $isi, $m);
        $this->assertNotEmpty($m);
        $this->assertDoesNotMatchRegularExpression('/^KIOSK_HMAC_SECRET=kiosk-lama$/m', $isi);
        $this->assertFileExists(dirname($env).'/.env.backup');

        // Terapkan kunci baru + kunci lama
        config(['hmac.secret' => $m[1], 'hmac.secret_lama' => 'kunci-lama-uji']);
        $this->assertSame($a->id_antrian, Hmac::verifikasiQr($qrLama)?->id_antrian, 'Tiket lama harus tetap valid selama transisi');
        $this->assertSame($a->id_antrian, Hmac::verifikasiQr(Hmac::qrTiket($a))?->id_antrian);
        $this->assertNotSame($qrLama, Hmac::qrTiket($a));
        // Setelah masa transisi (kunci lama dihapus) tiket lama ditolak
        config(['hmac.secret_lama' => null]);
        $this->assertNull(Hmac::verifikasiQr($qrLama));
        $this->assertTrue(\App\Models\AuditLog::where('aksi', 'rotasi_kunci')->count() >= 2);
        @unlink($env);
        @unlink(dirname($env).'/.env.backup');
    }

    public function test_halaman_kesehatan_hanya_super_admin(): void
    {
        Cache::forever('audit-dependensi', ['waktu' => now()->toIso8601String(), 'celah' => 2, 'paket' => ['contoh/paket'], 'ditinggalkan' => []]);
        $this->actingAs($this->akun('superadmin'))->get('/admin/kesehatan')->assertOk()->assertSee('Kesehatan sistem')->assertSee('contoh/paket')->assertSee('Ada masalah');
        $this->actingAs($this->akun('admin'))->get('/admin/kesehatan')->assertRedirect('/dashboard');
        $this->actingAs($this->akun('loket'))->get('/admin/kesehatan')->assertRedirect('/dashboard');
    }
}
