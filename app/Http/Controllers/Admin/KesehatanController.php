<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Pemantau;
use Illuminate\Support\Facades\Cache;

class KesehatanController extends Controller
{
    public function __invoke()
    {
        return view('admin.kesehatan', [
            'hasil' => Pemantau::periksa(),
            'pantauTerakhir' => Cache::get('pantau-terakhir')['waktu'] ?? null,
            'audit' => Cache::get('audit-dependensi'),
            'versi' => ['PHP' => PHP_VERSION, 'Laravel' => app()->version(), 'Lingkungan' => app()->environment(), 'Debug' => config('app.debug') ? 'aktif' : 'mati'],
        ]);
    }
}

