<?php

namespace App\Models;

use App\Models\Concerns\Diaudit;
use Illuminate\Database\Eloquent\Model;

class Obat extends Model
{
    use Diaudit;

    protected $table = 'obat';
    protected $primaryKey = 'id_obat';
    protected $fillable = ['nama_obat', 'satuan', 'stok'];
}

