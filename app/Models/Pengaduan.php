<?php

namespace App\Models;

use App\Models\Concerns\Diaudit;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pengaduan extends Model
{
    use Diaudit, HasUlids;

    public const KATEGORI = ['layanan' => 'Pelayanan petugas', 'antrean' => 'Antrean', 'obat' => 'Obat & farmasi', 'fasilitas' => 'Fasilitas', 'privasi' => 'Data pribadi (lihat, perbaiki, hapus)', 'lainnya' => 'Lainnya'];

    protected $table = 'pengaduan';
    protected $primaryKey = 'id_pengaduan';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $fillable = ['id_akun', 'kategori', 'judul', 'isi', 'status', 'tanggapan', 'id_penanggap'];

    public function uniqueIds(): array { return ['kode']; }
    public function getRouteKeyName(): string { return 'kode'; }

    public function akun(): BelongsTo { return $this->belongsTo(Akun::class, 'id_akun'); }
    public function penanggap(): BelongsTo { return $this->belongsTo(Akun::class, 'id_penanggap'); }
}

