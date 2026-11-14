<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenyerahanObat extends Model
{
    protected $table = 'penyerahan_obat';
    protected $primaryKey = 'id_penyerahan';
    public $timestamps = false;
    protected $fillable = ['id_resep', 'id_apoteker', 'id_pasien', 'waktu'];
    protected $casts = ['waktu' => 'datetime'];

    public function resep(): BelongsTo { return $this->belongsTo(Resep::class, 'id_resep'); }
    public function apoteker(): BelongsTo { return $this->belongsTo(Apoteker::class, 'id_apoteker'); }
    public function pasien(): BelongsTo { return $this->belongsTo(Pasien::class, 'id_pasien'); }
}

