<?php

namespace App\Models;

use App\Models\Concerns\Diaudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Admin extends Model
{
    use Diaudit;

    protected $table = 'admin';
    protected $primaryKey = 'id_admin';
    public $timestamps = false;
    protected $fillable = ['id_akun', 'nama'];

    public function akun(): BelongsTo { return $this->belongsTo(Akun::class, 'id_akun'); }
    public function dokter(): HasMany { return $this->hasMany(Dokter::class, 'id_admin'); }
}

