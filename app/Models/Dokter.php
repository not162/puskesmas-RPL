<?php

namespace App\Models;

use App\Models\Concerns\Diaudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dokter extends Model
{
    use Diaudit;

    protected $table = 'dokter';
    protected $primaryKey = 'id_dokter';
    public $timestamps = false;
    protected $fillable = ['id_akun', 'id_spesialisasi', 'id_admin', 'nama', 'no_str', 'jadwal'];

    public function akun(): BelongsTo { return $this->belongsTo(Akun::class, 'id_akun'); }
    public function spesialisasi(): BelongsTo { return $this->belongsTo(Spesialisasi::class, 'id_spesialisasi'); }
    public function admin(): BelongsTo { return $this->belongsTo(Admin::class, 'id_admin'); }
    public function konsultasi(): HasMany { return $this->hasMany(Konsultasi::class, 'id_dokter'); }
    public function resep(): HasMany { return $this->hasMany(Resep::class, 'id_dokter'); }
    public function jadwalPraktik(): HasMany { return $this->hasMany(JadwalPraktik::class, 'id_dokter')->orderBy('hari')->orderBy('jam_mulai'); }

    /** Ringkasan jadwal aktif, contoh: "Senin 08:00–14:00 · Rabu 08:00–12:00". */
    public function ringkasJadwal(): string
    {
        $j = $this->jadwalPraktik->where('aktif', true);
        return $j->isEmpty() ? ($this->jadwal ?? 'Jadwal menyusul') : $j->map->label()->implode(' · ');
    }
}

