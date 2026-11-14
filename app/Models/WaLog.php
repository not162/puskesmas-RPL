<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaLog extends Model
{
    protected $table = 'wa_log';
    public const UPDATED_AT = null;
    protected $fillable = ['id_akun', 'nomor', 'jenis', 'status', 'ringkas', 'respons'];

    public function akun(): BelongsTo { return $this->belongsTo(Akun::class, 'id_akun'); }

    public function nomorSamaran(): string
    {
        return substr($this->nomor, 0, 4).str_repeat('•', max(0, strlen($this->nomor) - 7)).substr($this->nomor, -3);
    }
}

