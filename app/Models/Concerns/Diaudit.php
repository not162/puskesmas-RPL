<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;

/** Mencatat create/update/delete model ke tabel audit_log secara otomatis. */
trait Diaudit
{
    protected static array $kolomRahasia = ['password', 'remember_token', 'foto_ktp'];

    public static function bootDiaudit(): void
    {
        static::created(fn ($m) => AuditLog::catat('buat', $m, $m->bersihkanAudit($m->getAttributes())));
        static::updated(function ($m) {
            $ubah = $m->bersihkanAudit($m->getChanges());
            unset($ubah['updated_at']);
            if ($ubah) AuditLog::catat('ubah', $m, $ubah);
        });
        static::deleted(fn ($m) => AuditLog::catat('hapus', $m, null));
    }

    public function bersihkanAudit(array $data): array
    {
        foreach (static::$kolomRahasia as $k) {
            if (array_key_exists($k, $data)) $data[$k] = '••••';
        }
        return $data;
    }
}

