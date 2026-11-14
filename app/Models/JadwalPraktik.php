<?php

namespace App\Models;

use App\Models\Concerns\Diaudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JadwalPraktik extends Model
{
    use Diaudit;

    public const HARI = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];

    protected $table = 'jadwal_praktik';
    protected $primaryKey = 'id_jadwal';
    public $timestamps = false;
    protected $fillable = ['id_dokter', 'hari', 'jam_mulai', 'jam_selesai', 'kuota', 'aktif'];
    protected $casts = ['aktif' => 'boolean'];

    public function dokter(): BelongsTo { return $this->belongsTo(Dokter::class, 'id_dokter'); }

    public function label(): string
    {
        return self::HARI[$this->hari].' '.substr($this->jam_mulai, 0, 5).'–'.substr($this->jam_selesai, 0, 5);
    }
}

