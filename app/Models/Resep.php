<?php

namespace App\Models;

use App\Models\Concerns\Diaudit;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Resep extends Model
{
    use Diaudit, HasUlids;

    protected $table = 'resep';
    protected $primaryKey = 'id_resep';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;
    protected $fillable = ['id_pasien', 'id_dokter', 'id_apoteker', 'id_konsultasi', 'status', 'tgl_resep'];
    protected $casts = ['tgl_resep' => 'datetime'];

    public function uniqueIds(): array { return ['kode']; }
    public function getRouteKeyName(): string { return 'kode'; }

    public function pasien(): BelongsTo { return $this->belongsTo(Pasien::class, 'id_pasien'); }
    public function dokter(): BelongsTo { return $this->belongsTo(Dokter::class, 'id_dokter'); }
    public function apoteker(): BelongsTo { return $this->belongsTo(Apoteker::class, 'id_apoteker'); }
    public function konsultasi(): BelongsTo { return $this->belongsTo(Konsultasi::class, 'id_konsultasi'); }
    public function penyerahan(): HasOne { return $this->hasOne(PenyerahanObat::class, 'id_resep'); }

    /** Relasi N:M "berisi" lewat tabel detail_resep. */
    public function obat(): BelongsToMany
    {
        return $this->belongsToMany(Obat::class, 'detail_resep', 'id_resep', 'id_obat')->withPivot('jumlah', 'aturan_pakai');
    }
}

