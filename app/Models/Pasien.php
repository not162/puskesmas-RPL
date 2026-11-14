<?php

namespace App\Models;

use App\Models\Concerns\Diaudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pasien extends Model
{
    use Diaudit;

    protected $table = 'pasien';
    protected $primaryKey = 'id_pasien';
    protected $fillable = ['id_akun', 'no_rm', 'nik', 'no_kk', 'no_bpjs', 'foto_ktp', 'nama', 'tgl_lahir', 'jenis_kelamin', 'alamat', 'no_tlp'];
    protected $casts = ['tgl_lahir' => 'date'];

    public function akun(): BelongsTo { return $this->belongsTo(Akun::class, 'id_akun'); }
    public function konsultasi(): HasMany { return $this->hasMany(Konsultasi::class, 'id_pasien'); }
    public function antrian(): HasMany { return $this->hasMany(Antrian::class, 'id_pasien'); }
    public function resep(): HasMany { return $this->hasMany(Resep::class, 'id_pasien'); }

    public static function nomorRmBaru(): string
    {
        $last = static::max('id_pasien') ?? 0;
        return 'RM-'.str_pad((string) ($last + 1), 6, '0', STR_PAD_LEFT);
    }

    /** Nama disamarkan untuk tampilan publik: "Budi Santoso" -> "B*** S***". */
    public function namaSamaran(): string
    {
        return collect(explode(' ', trim($this->nama)))
            ->map(fn ($k) => mb_substr($k, 0, 1).'***')->implode(' ');
    }

    public function nikSamaran(): string
    {
        return str_repeat('•', 12).substr($this->nik, -4);
    }
}

