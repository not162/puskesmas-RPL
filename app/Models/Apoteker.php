<?php

namespace App\Models;

use App\Models\Concerns\Diaudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Apoteker extends Model
{
    use Diaudit;

    protected $table = 'apoteker';
    protected $primaryKey = 'id_apoteker';
    public $timestamps = false;
    protected $fillable = ['id_akun', 'nama', 'no_sipa'];

    public function akun(): BelongsTo { return $this->belongsTo(Akun::class, 'id_akun'); }
    public function resep(): HasMany { return $this->hasMany(Resep::class, 'id_apoteker'); }
}

