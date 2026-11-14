<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

/**
 * Verifikasi manusia (anti-bot) untuk login, daftar, dan lupa password.
 *  - Cloudflare Turnstile bila TURNSTILE_SITE_KEY & TURNSTILE_SECRET_KEY diisi.
 *  - Otomatis kembali ke soal hitung bila kunci kosong ATAU server Cloudflare tidak terjangkau (offline).
 */
class Verifikasi
{
    public static function turnstileAktif(): bool
    {
        return (bool) (config('services.turnstile.site_key') && config('services.turnstile.secret_key'))
            && session('verifikasi_mode') !== 'hitung';
    }

    /** Data untuk partial captcha. Soal hitung disimpan di session. */
    public static function siapkan(): array
    {
        if (self::turnstileAktif()) {
            return ['mode' => 'turnstile', 'site_key' => config('services.turnstile.site_key')];
        }
        $a = random_int(2, 9);
        $b = random_int(1, 9);
        [$soal, $jawab] = random_int(0, 1) ? ["$a + $b", $a + $b] : ["$a × $b", $a * $b];
        session(['captcha' => $jawab]);
        return ['mode' => 'hitung', 'soal' => $soal];
    }

    public static function periksa(Request $request): void
    {
        if (self::turnstileAktif()) {
            $token = (string) $request->input('cf-turnstile-response');
            if ($token === '') {
                throw ValidationException::withMessages(['captcha' => 'Selesaikan verifikasi keamanan terlebih dahulu.']);
            }
            try {
                $res = Http::asForm()->timeout(6)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => config('services.turnstile.secret_key'),
                    'response' => $token,
                    'remoteip' => $request->ip(),
                ]);
            } catch (\Throwable) {
                // Offline / Cloudflare tidak terjangkau: beralih ke soal hitung untuk sesi ini
                session(['verifikasi_mode' => 'hitung']);
                throw ValidationException::withMessages(['captcha' => 'Layanan verifikasi tidak terjangkau. Silakan jawab soal hitung.']);
            }
            if (! $res->successful() || ! $res->json('success')) {
                throw ValidationException::withMessages(['captcha' => 'Verifikasi keamanan gagal. Coba lagi.']);
            }
            return;
        }

        $jawab = session()->pull('captcha');
        if ($jawab === null || ! is_numeric($request->input('captcha')) || (int) $request->input('captcha') !== (int) $jawab) {
            throw ValidationException::withMessages(['captcha' => 'Jawaban verifikasi keamanan salah.']);
        }
    }
}
