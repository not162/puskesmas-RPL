<?php

namespace App\Models;

use App\Models\Concerns\Diaudit;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Antrian extends Model
{
    use Diaudit, HasUlids;

    public const STATUS = ['menunggu', 'hadir', 'dipanggil', 'dilayani', 'selesai', 'batal'];

    protected $table = 'antrian';
    protected $primaryKey = 'id_antrian';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $fillable = ['id_pasien', 'id_spesialisasi', 'id_dokter', 'id_konsultasi', 'tanggal', 'nomor', 'status', 'sumber', 'keluhan_awal', 'waktu_hadir', 'waktu_panggil'];
    protected $casts = ['tanggal' => 'date', 'waktu_hadir' => 'datetime', 'waktu_panggil' => 'datetime'];

    public function uniqueIds(): array { return ['kode']; }
    public function getRouteKeyName(): string { return 'kode'; }

    public function pasien(): BelongsTo { return $this->belongsTo(Pasien::class, 'id_pasien'); }
    public function spesialisasi(): BelongsTo { return $this->belongsTo(Spesialisasi::class, 'id_spesialisasi'); }
    public function dokter(): BelongsTo { return $this->belongsTo(Dokter::class, 'id_dokter'); }
    public function konsultasi(): BelongsTo { return $this->belongsTo(Konsultasi::class, 'id_konsultasi'); }

    /** Nomor antrian berikutnya per poli per tanggal, contoh U-007. Panggil di dalam transaksi. */
    public static function nomorBerikut(Spesialisasi $sp, string $tanggal): string
    {
        $prefix = $sp->prefiks().'-';
        $n = DB::table('antrian')->where('tanggal', $tanggal)->where('nomor', 'like', $prefix.'%')->lockForUpdate()->count();
        return $prefix.str_pad((string) ($n + 1), 3, '0', STR_PAD_LEFT);
    }

    /** Jumlah antrian di depan pasien ini yang belum dilayani. */
    public function posisi(): int
    {
        return static::whereDate('tanggal', $this->tanggal)->where('id_spesialisasi', $this->id_spesialisasi)
            ->whereIn('status', ['menunggu', 'hadir', 'dipanggil'])->where('id_antrian', '<', $this->id_antrian)->count();
    }

    public function labelStatus(): string
    {
        return [
            'menunggu' => 'Menunggu check-in', 'hadir' => 'Sudah hadir', 'dipanggil' => 'Dipanggil',
            'dilayani' => 'Sedang dilayani', 'selesai' => 'Selesai', 'batal' => 'Dibatalkan',
        ][$this->status] ?? $this->status;
    }
}

