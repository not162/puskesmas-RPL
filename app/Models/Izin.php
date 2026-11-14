<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Izin extends Model
{
    protected $table = 'izin';
    protected $primaryKey = 'id_izin';
    public $timestamps = false;
    protected $fillable = ['kode_izin', 'deskripsi'];

    public function peran(): BelongsToMany
    {
        return $this->belongsToMany(Peran::class, 'peran_izin', 'id_izin', 'id_peran');
    }
}

