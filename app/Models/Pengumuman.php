<?php

namespace App\Models;

use App\Models\Concerns\Diaudit;
use Illuminate\Database\Eloquent\Model;

class Pengumuman extends Model
{
    use Diaudit;

    protected $table = 'pengumuman';
    protected $fillable = ['judul', 'isi', 'kategori', 'tanggal', 'terbit'];
    protected $casts = ['tanggal' => 'date', 'terbit' => 'boolean'];
}

