<?php

namespace App\Models;

use App\Models\Concerns\Diaudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Pengaturan extends Model
{
    use Diaudit;

    public const BAWAAN = [
        'nama_puskesmas' => 'Puskesmas Sehat',
        'alamat' => 'Jl. Kesehatan No. 1, Kecamatan Sehat, Kota Sejahtera',
        'telepon' => '(021) 000-0000',
        'whatsapp' => '6281200000000',
        'email' => 'info@puskesmas.test',
        'jam_layanan' => 'Senin–Sabtu 07.30–14.00 WIB · IGD 24 jam',
        'maps_url' => 'https://maps.google.com/?q=puskesmas',
        'maks_hari_antrean' => '7',
        'kota' => 'Kota Sejahtera',
        'kepala_puskesmas' => '',
        'nip_kepala' => '',
        'otp_petugas' => '',
        'maks_gagal_login' => '5',
        'menit_kunci' => '15',
        'tolak_nama_ganda' => '1',
        'tolak_kk_ganda' => '1',
    ];

    protected $table = 'pengaturan';
    protected $primaryKey = 'kunci';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $fillable = ['kunci', 'nilai'];

    public static function semua(): array
    {
        return Cache::remember('pengaturan', 300, function () {
            try {
                return array_merge(self::BAWAAN, static::pluck('nilai', 'kunci')->all());
            } catch (\Throwable) {
                return self::BAWAAN;
            }
        });
    }

    public static function nilai(string $kunci): ?string
    {
        return self::semua()[$kunci] ?? null;
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('pengaturan'));
    }
}

