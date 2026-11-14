<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogLogin extends Model
{
    protected $table = 'log_login';
    protected $primaryKey = 'id_log';
    public $timestamps = false;
    protected $fillable = ['id_akun', 'email_dicoba', 'waktu', 'hasil', 'ip'];
    protected $casts = ['waktu' => 'datetime'];

    public function akun(): BelongsTo { return $this->belongsTo(Akun::class, 'id_akun'); }
}

