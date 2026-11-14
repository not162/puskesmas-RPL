<?php

namespace App\Models;

use App\Models\Concerns\Diaudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PetugasLoket extends Model
{
    use Diaudit;

    protected $table = 'petugas_loket';
    protected $primaryKey = 'id_loket';
    public $timestamps = false;
    protected $fillable = ['id_akun', 'nama'];

    public function akun(): BelongsTo { return $this->belongsTo(Akun::class, 'id_akun'); }
}

