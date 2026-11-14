<?php

namespace App\Models;

use App\Models\Concerns\Diaudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Spesialisasi extends Model
{
    use Diaudit;

    protected $table = 'spesialisasi';
    protected $primaryKey = 'id_spesialisasi';
    public $timestamps = false;
    protected $fillable = ['nama', 'poli'];

    public function dokter(): HasMany { return $this->hasMany(Dokter::class, 'id_spesialisasi'); }

    /** Prefiks nomor antrian: U = Umum, K = Kandungan. */
    public function prefiks(): string
    {
        return strtoupper(mb_substr($this->nama, 0, 1));
    }
}

