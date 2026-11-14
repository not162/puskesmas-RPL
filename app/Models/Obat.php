<?php

namespace App\Models;

use App\Models\Concerns\Diaudit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Obat extends Model
{
    use Diaudit;

    public const BENTUK = ['Tablet', 'Tablet Kunyah', 'Kapsul', 'Sirup', 'Sachet', 'Salep', 'Tetes', 'Injeksi'];
    public const POLI = ['umum' => 'Poli Umum', 'kandungan' => 'Poli KIA & Kandungan', 'semua' => 'Semua poli'];
    public const KEAMANAN_HAMIL = ['aman' => 'Aman untuk ibu hamil', 'hati_hati' => 'Hati-hati, sesuai pertimbangan dokter', 'hindari' => 'Hindari (terutama trimester akhir)', 'konsultasi' => 'Perlu pertimbangan dokter'];
    public const GOLONGAN = ['antibiotik' => 'Antibiotik', 'oains' => 'OAINS (antiinflamasi)', 'suplemen' => 'Suplemen/Vitamin'];

    protected $table = 'obat';
    protected $primaryKey = 'id_obat';
    protected $fillable = ['kode', 'nama_obat', 'kategori', 'bentuk', 'zat_aktif', 'golongan', 'poli', 'keamanan_hamil', 'indikasi', 'kegunaan',
        'jumlah_bawaan', 'aturan_bawaan', 'catatan', 'satuan', 'stok', 'stok_minimum', 'aktif'];
    protected $casts = ['aktif' => 'boolean'];

    public function scopeTersedia(Builder $q): Builder
    {
        return $q->where('aktif', true)->where('stok', '>', 0);
    }

    public function stokMenipis(): bool
    {
        return $this->stok <= $this->stok_minimum;
    }

    /** Label pilihan: "OBT-001 · Parasetamol 500 mg · Tablet" */
    public function label(): string
    {
        return trim(($this->kode ? $this->kode.' · ' : '').$this->nama_obat.($this->bentuk ? ' · '.$this->bentuk : ''));
    }

    /** Kata kunci indikasi (huruf kecil). */
    public function kataKunci(): array
    {
        return array_values(array_filter(array_map(fn ($x) => mb_strtolower(trim($x)), explode(',', (string) $this->indikasi))));
    }

    public static function kodeBerikut(): string
    {
        $n = (int) static::where('kode', 'like', 'OBT-%')->selectRaw('max(cast(substring(kode, 5) as unsigned)) n')->value('n');
        return 'OBT-'.str_pad((string) ($n + 1), 3, '0', STR_PAD_LEFT);
    }
}
