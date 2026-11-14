<?php

namespace App\Support;

use App\Mail\KodeOtpMail;
use App\Models\AuditLog;
use App\Models\OtpKode;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * Kode OTP 6 digit dikirim lewat SMTP ke email pengguna (Gmail, dll).
 * Kode disimpan sebagai HMAC-SHA256, berlaku 10 menit, maks 5 kali salah, kirim ulang tiap 60 detik.
 */
class Otp
{
    public const BERLAKU_MENIT = 10;
    public const MAKS_COBA = 5;
    public const JEDA_KIRIM = 60;

    private static function hash(string $email, string $tujuan, string $kode): string
    {
        return Hmac::tanda('otp|'.strtolower($email).'|'.$tujuan.'|'.$kode);
    }

    public static function kirim(string $email, string $tujuan, array $payload = [], ?int $idAkun = null): OtpKode
    {
        $email = strtolower(trim($email));
        $terakhir = OtpKode::where('email', $email)->where('tujuan', $tujuan)->latest('id')->first();
        if ($terakhir && $terakhir->created_at->diffInSeconds(now()) < self::JEDA_KIRIM) {
            $sisa = self::JEDA_KIRIM - (int) $terakhir->created_at->diffInSeconds(now());
            throw ValidationException::withMessages(['kode' => "Tunggu {$sisa} detik sebelum meminta kode baru."]);
        }
        OtpKode::where('email', $email)->where('tujuan', $tujuan)->whereNull('dipakai_at')->update(['dipakai_at' => now()]);

        $kode = (string) random_int(100000, 999999);
        $otp = OtpKode::create([
            'email' => $email, 'tujuan' => $tujuan, 'id_akun' => $idAkun, 'payload' => $payload ?: null,
            'kode_hash' => self::hash($email, $tujuan, $kode), 'kedaluwarsa' => now()->addMinutes(self::BERLAKU_MENIT),
            'ip' => app()->runningInConsole() ? null : request()->ip(),
        ]);

        try {
            Mail::to($email)->send(new KodeOtpMail($kode, $tujuan, self::BERLAKU_MENIT));
        } catch (\Throwable $e) {
            $otp->delete();
            report($e);
            throw ValidationException::withMessages(['kode' => 'Email OTP gagal dikirim. Periksa alamat email atau konfigurasi SMTP server.', 'email' => 'Email OTP gagal dikirim. Periksa alamat email Anda.']);
        }
        AuditLog::catat('otp_kirim', 'otp_kode', ['tujuan' => $tujuan, 'email' => $otp->emailSamaran()]);
        return $otp;
    }

    /** Kirim ulang kode baru dengan payload & tujuan yang sama. */
    public static function kirimUlang(OtpKode $lama): OtpKode
    {
        return self::kirim($lama->email, $lama->tujuan, $lama->payload ?? [], $lama->id_akun);
    }

    /** Lempar ValidationException bila salah; tandai terpakai bila benar. */
    public static function verifikasi(?OtpKode $otp, string $kode): OtpKode
    {
        if (! $otp || ! $otp->masihBerlaku()) {
            throw ValidationException::withMessages(['kode' => 'Kode OTP kedaluwarsa atau tidak valid. Minta kode baru.']);
        }
        $otp->increment('percobaan');
        if (! hash_equals($otp->kode_hash, self::hash($otp->email, $otp->tujuan, trim($kode)))) {
            $sisa = self::MAKS_COBA - $otp->percobaan;
            throw ValidationException::withMessages(['kode' => $sisa > 0 ? "Kode OTP salah. Sisa {$sisa} percobaan." : 'Terlalu banyak percobaan. Minta kode baru.']);
        }
        $otp->update(['dipakai_at' => now()]);
        return $otp;
    }
}

