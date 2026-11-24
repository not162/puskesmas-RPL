<?php

namespace App\Models;

use App\Models\Concerns\Diaudit;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Akun login tunggal untuk semua jenis pengguna (ERD: Akun).
 */
class Akun extends Authenticatable
{
    use Diaudit, HasApiTokens, Notifiable;

    protected $table = 'akun';
    protected $primaryKey = 'id_akun';
    protected $fillable = ['id_peran', 'username', 'email', 'password', 'status', 'foto'];
    protected $hidden = ['password', 'remember_token'];

    protected ?array $izinCache = null;

    protected function casts(): array
    {
        return ['password' => 'hashed', 'terkunci_sampai' => 'datetime'];
    }

    public function peran(): BelongsTo { return $this->belongsTo(Peran::class, 'id_peran'); }
    public function pasien(): HasOne { return $this->hasOne(Pasien::class, 'id_akun'); }
    public function dokter(): HasOne { return $this->hasOne(Dokter::class, 'id_akun'); }
    public function apoteker(): HasOne { return $this->hasOne(Apoteker::class, 'id_akun'); }
    public function admin(): HasOne { return $this->hasOne(Admin::class, 'id_akun'); }
    public function petugasLoket(): HasOne { return $this->hasOne(PetugasLoket::class, 'id_akun'); }
    public function logLogin(): HasMany { return $this->hasMany(LogLogin::class, 'id_akun'); }

    public function role(): string
    {
        return $this->peran?->nama_peran ?? '';
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role(), $roles, true);
    }

    public function isDokter(): bool
    {
        return $this->hasRole(Peran::DOKTER_UMUM, Peran::DOKTER_KANDUNGAN);
    }

    /** Daftar kode izin milik peran akun ini (RBAC dari tabel peran_izin). */
    public function daftarIzin(): array
    {
        return $this->izinCache ??= ($this->peran?->izin()->pluck('kode_izin')->all() ?? []);
    }

    public function punyaIzin(string $kode): bool
    {
        return $this->role() === Peran::SUPER_ADMIN || in_array($kode, $this->daftarIzin(), true);
    }

    public function namaTampil(): string
    {
        return $this->pasien?->nama ?? $this->dokter?->nama ?? $this->apoteker?->nama
            ?? $this->admin?->nama ?? $this->petugasLoket?->nama ?? $this->email;
    }

    public function terkunci(): bool
    {
        return $this->terkunci_sampai !== null && $this->terkunci_sampai->isFuture();
    }

    public function aktif(): bool
    {
        return $this->status === 'aktif';
    }

    public function avatarUrl(): string
    {
        if ($this->foto && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->foto)) {
            return asset('storage/'.$this->foto);
        }
        return route('avatar', $this, false);
    }

    public function inisial(): string
    {
        $nama = trim(preg_replace('/^(dr\.|drg\.|apt\.|ns\.)\s*/i', '', (string) $this->namaTampil()));
        $parts = preg_split('/\s+/', $nama);
        if (count($parts) >= 2) {
            return strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1));
        }
        return strtoupper(mb_substr($nama ?: $this->username, 0, 2) ?: 'PK');
    }
}

