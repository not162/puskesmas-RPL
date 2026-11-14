<?php

namespace App\Support;

use App\Models\Antrian;

/**
 * HMAC-SHA256:
 *  1) Tanda tangan QR tiket antrean -> "PKM1.<kode>.<sig>" (anti-pemalsuan tiket).
 *  2) Tanda tangan permintaan API kiosk (lihat Middleware VerifikasiHmac).
 */
class Hmac
{
    public static function kunci(): string
    {
        return (string) (config('hmac.secret') ?: config('app.key'));
    }

    public static function tanda(string $payload, ?string $kunci = null): string
    {
        return hash_hmac('sha256', $payload, $kunci ?? self::kunci());
    }

    public static function payloadTiket(Antrian $a): string
    {
        return implode('|', [$a->kode, $a->nomor, $a->tanggal->toDateString(), $a->id_pasien]);
    }

    public static function qrTiket(Antrian $a): string
    {
        return 'PKM1.'.$a->kode.'.'.substr(self::tanda(self::payloadTiket($a)), 0, 32);
    }

    /** Validasi isi QR, kembalikan Antrian bila tanda tangan cocok. */
    public static function verifikasiQr(string $qr): ?Antrian
    {
        if (! preg_match('/^PKM1\.([0-9A-Za-z]{26})\.([a-f0-9]{32})$/', trim($qr), $m)) {
            return null;
        }
        $a = Antrian::where('kode', strtolower($m[1]))->orWhere('kode', $m[1])->first();
        if (! $a) return null;
        $payload = self::payloadTiket($a);
        if (hash_equals(substr(self::tanda($payload), 0, 32), $m[2])) return $a;
        // Masa transisi rotasi kunci: tiket yang ditandatangani kunci lama masih diterima
        $lama = config('hmac.secret_lama');
        return $lama && hash_equals(substr(self::tanda($payload, $lama), 0, 32), $m[2]) ? $a : null;
    }

    /** String kanonik permintaan kiosk: METHOD \n PATH \n TIMESTAMP \n SHA256(body) */
    public static function kanonik(string $method, string $path, string $timestamp, string $body): string
    {
        return strtoupper($method)."\n".'/'.ltrim($path, '/')."\n".$timestamp."\n".hash('sha256', $body);
    }
}

