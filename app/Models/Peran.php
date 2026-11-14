<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Peran extends Model
{
    public const SUPER_ADMIN = 'super_admin';
    public const ADMIN = 'admin';
    public const DOKTER_UMUM = 'dokter_umum';
    public const DOKTER_KANDUNGAN = 'dokter_kandungan';
    public const APOTEKER = 'apoteker';
    public const LOKET = 'loket';
    public const PASIEN = 'pasien';

    protected $table = 'peran';
    protected $primaryKey = 'id_peran';
    public $timestamps = false;
    protected $fillable = ['nama_peran', 'label'];

    public function izin(): BelongsToMany
    {
        return $this->belongsToMany(Izin::class, 'peran_izin', 'id_peran', 'id_izin');
    }

    public function akun(): HasMany
    {
        return $this->hasMany(Akun::class, 'id_peran');
    }
}

