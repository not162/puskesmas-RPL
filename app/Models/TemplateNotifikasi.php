<?php

namespace App\Models;

use App\Models\Concerns\Diaudit;
use Illuminate\Database\Eloquent\Model;

class TemplateNotifikasi extends Model
{
    use Diaudit;

    protected $table = 'template_notifikasi';
    protected $primaryKey = 'langkah';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = ['langkah', 'judul', 'pesan', 'aktif'];
    protected $casts = ['aktif' => 'boolean'];
}

