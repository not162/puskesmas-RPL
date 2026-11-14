<?php

namespace App\Support;

use App\Jobs\KirimWhatsApp;
use App\Models\Pasien;
use App\Models\Pengaturan;
use App\Models\WaLog;
use Illuminate\Support\Facades\Http;

/**
 * Notifikasi WhatsApp (Sprint 5).
 *  - Hanya ke pasien yang menyetujui (opt-in).
 *  - Batas kirim per nomor per hari (Pengaturan: wa_maks_harian).
 *  - Driver: log (lokal, tidak terkirim) atau fonnte (api.fonnte.com).
 */
class WhatsApp
{
    public static function aktif(): bool
    {
        return config('whatsapp.driver') === 'log' || (config('whatsapp.driver') === 'fonnte' && config('whatsapp.fonnte_token'));
    }

    /** 08xx / +628xx / 628xx -> 628xx. Null bila tidak valid. */
    public static function normalisasi(?string $no): ?string
    {
        $d = preg_replace('/\D/', '', (string) $no);
        if (str_starts_with($d, '0')) $d = '62'.substr($d, 1);
        return preg_match('/^628\d{7,12}$/', $d) ? $d : null;
    }

    /** Langkah layanan yang dikirim ke WhatsApp, mis. [5, 7, 9]. */
    public static function langkahAktif(): array
    {
        return array_map('intval', array_filter(explode(',', (string) Pengaturan::nilai('wa_langkah'))));
    }

    public static function langkahPasien(?Pasien $p, int $langkah, string $judul, string $pesan): void
    {
        if (! $p || ! $p->wa_opt_in || ! in_array($langkah, self::langkahAktif(), true)) return;
        $nama = Pengaturan::nilai('nama_puskesmas');
        self::kirim($p->no_tlp, "*{$nama}*\n{$judul}\n\n{$pesan}\n\n_Pesan otomatis — jangan dibalas._", 'langkah_'.$langkah, $p->id_akun);
    }

    /** Kirim (atau antrekan) pesan. Kembalikan status: terkirim / log / antre / gagal / dibatasi / tidak_valid. */
    public static function kirim(?string $nomor, string $pesan, string $jenis, ?int $idAkun = null, ?string $ringkas = null): string
    {
        $no = self::normalisasi($nomor);
        if (! $no) return 'tidak_valid';
        if (! self::aktif()) return 'nonaktif';

        $maks = max(1, (int) Pengaturan::nilai('wa_maks_harian'));
        $hariIni = WaLog::where('nomor', $no)->whereIn('status', ['terkirim', 'log', 'antre'])->where('created_at', '>=', today())->count();
        if ($hariIni >= $maks) {
            WaLog::create(['id_akun' => $idAkun, 'nomor' => $no, 'jenis' => $jenis, 'status' => 'dibatasi', 'ringkas' => $ringkas ?? mb_substr($pesan, 0, 150)]);
            return 'dibatasi';
        }

        if (config('whatsapp.pengiriman') === 'antrean' && config('whatsapp.driver') !== 'log') {
            $log = WaLog::create(['id_akun' => $idAkun, 'nomor' => $no, 'jenis' => $jenis, 'status' => 'antre', 'ringkas' => $ringkas ?? mb_substr($pesan, 0, 150)]);
            KirimWhatsApp::dispatch($log->id, $pesan);
            return 'antre';
        }
        return self::kirimSekarang($no, $pesan, $jenis, $idAkun, $ringkas);
    }

    public static function kirimSekarang(string $no, string $pesan, string $jenis, ?int $idAkun, ?string $ringkas, ?WaLog $log = null): string
    {
        $data = ['id_akun' => $idAkun, 'nomor' => $no, 'jenis' => $jenis, 'ringkas' => $ringkas ?? mb_substr($pesan, 0, 150)];

        if (config('whatsapp.driver') === 'log') {
            logger()->info("[WhatsApp] ke $no: $pesan");
            $log ? $log->update(['status' => 'log']) : WaLog::create($data + ['status' => 'log', 'respons' => 'driver log (tidak dikirim)']);
            return 'log';
        }

        try {
            $res = Http::asForm()->timeout(config('whatsapp.timeout'))
                ->withHeaders(['Authorization' => config('whatsapp.fonnte_token')])
                ->post('https://api.fonnte.com/send', ['target' => $no, 'message' => $pesan, 'countryCode' => '62']);
            $ok = $res->successful() && $res->json('status') === true;
            $status = $ok ? 'terkirim' : 'gagal';
            $respons = mb_substr($res->body(), 0, 250);
        } catch (\Throwable $e) {
            $status = 'gagal';
            $respons = mb_substr($e->getMessage(), 0, 250);
        }
        $log ? $log->update(['status' => $status, 'respons' => $respons]) : WaLog::create($data + ['status' => $status, 'respons' => $respons]);
        return $status;
    }
}

