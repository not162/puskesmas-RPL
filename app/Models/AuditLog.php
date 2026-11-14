<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $table = 'audit_log';
    protected $primaryKey = 'id_audit';
    public $timestamps = false;
    protected $fillable = ['id_akun', 'aksi', 'objek', 'objek_id', 'perubahan', 'ip', 'waktu'];
    protected $casts = ['waktu' => 'datetime', 'perubahan' => 'array'];

    public function akun(): BelongsTo { return $this->belongsTo(Akun::class, 'id_akun'); }

    public static function catat(string $aksi, Model|string $objek, ?array $perubahan = null): void
    {
        static::create([
            'id_akun' => auth()->id(),
            'aksi' => $aksi,
            'objek' => is_string($objek) ? $objek : $objek->getTable(),
            'objek_id' => is_string($objek) ? null : (string) $objek->getKey(),
            'perubahan' => $perubahan,
            'ip' => app()->runningInConsole() ? null : request()->ip(),
            'waktu' => now(),
        ]);
    }
}

