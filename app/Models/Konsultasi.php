<?php

namespace App\Models;

use App\Models\Concerns\Diaudit;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Konsultasi extends Model
{
    use Diaudit, HasUlids;

    protected $table = 'konsultasi';
    protected $primaryKey = 'id_konsultasi';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $fillable = ['id_pasien', 'id_dokter', 'id_spesialisasi', 'tanggal', 'keluhan', 'diagnosis', 'tanggapan', 'jenis', 'status'];
    protected $casts = ['tanggal' => 'datetime'];

    public function uniqueIds(): array { return ['kode']; }
    public function getRouteKeyName(): string { return 'kode'; }

    public function pasien(): BelongsTo { return $this->belongsTo(Pasien::class, 'id_pasien'); }
    public function dokter(): BelongsTo { return $this->belongsTo(Dokter::class, 'id_dokter'); }
    public function spesialisasi(): BelongsTo { return $this->belongsTo(Spesialisasi::class, 'id_spesialisasi'); }
    public function resep(): HasOne { return $this->hasOne(Resep::class, 'id_konsultasi'); }
    public function antrian(): HasOne { return $this->hasOne(Antrian::class, 'id_konsultasi'); }
}

