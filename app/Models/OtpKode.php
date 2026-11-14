<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class OtpKode extends Model
{
    use HasUlids;

    protected $table = 'otp_kode';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $fillable = ['email', 'tujuan', 'id_akun', 'kode_hash', 'payload', 'percobaan', 'kedaluwarsa', 'dipakai_at', 'ip'];
    protected $hidden = ['kode_hash', 'payload'];
    protected $casts = ['payload' => 'encrypted:array', 'kedaluwarsa' => 'datetime', 'dipakai_at' => 'datetime'];

    public function uniqueIds(): array { return ['ref']; }

    public function masihBerlaku(): bool
    {
        return ! $this->dipakai_at && $this->kedaluwarsa->isFuture() && $this->percobaan < \App\Support\Otp::MAKS_COBA;
    }

    /** contoh: b***i@gmail.com */
    public function emailSamaran(): string
    {
        [$u, $d] = explode('@', $this->email) + [1 => ''];
        return mb_substr($u, 0, 1).str_repeat('*', max(2, mb_strlen($u) - 2)).mb_substr($u, -1).'@'.$d;
    }
}

