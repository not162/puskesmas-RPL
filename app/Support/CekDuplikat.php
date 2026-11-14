<?php

namespace App\Support;

use App\Models\Akun;
use App\Models\Pasien;
use App\Models\Pengaturan;

/**
 * Mencegah pendaftaran ganda. Mengembalikan daftar kolom yang sudah terdaftar
 * (username, email, NIK, nama, No. KK). Pasien yang didaftarkan loket tanpa akun
 * TIDAK dihitung ganda agar bisa menautkan akun lewat NIK yang sama.
 */
class CekDuplikat
{
    public const LABEL = ['username' => 'Username', 'email' => 'Email', 'nik' => 'NIK', 'nama' => 'Nama lengkap', 'no_kk' => 'Nomor Kartu Keluarga'];

    public static function cari(array $d): array
    {
        $ada = [];
        if (! empty($d['username']) && Akun::where('username', strtolower(trim($d['username'])))->exists()) $ada[] = 'username';
        if (! empty($d['email']) && Akun::where('email', strtolower(trim($d['email'])))->exists()) $ada[] = 'email';

        $pasienBerakun = fn () => Pasien::whereNotNull('id_akun');
        if (! empty($d['nik']) && $pasienBerakun()->where('nik', $d['nik'])->exists()) $ada[] = 'nik';
        if (! empty($d['nama']) && Pengaturan::nilai('tolak_nama_ganda') !== '0'
            && $pasienBerakun()->whereRaw('LOWER(TRIM(nama)) = ?', [mb_strtolower(trim(preg_replace('/\s+/', ' ', $d['nama'])))])->exists()) $ada[] = 'nama';
        if (! empty($d['no_kk']) && Pengaturan::nilai('tolak_kk_ganda') !== '0' && $pasienBerakun()->where('no_kk', $d['no_kk'])->exists()) $ada[] = 'no_kk';

        return $ada;
    }

    public static function pesan(array $kolom): string
    {
        return implode(', ', array_map(fn ($k) => self::LABEL[$k] ?? $k, $kolom));
    }
}

