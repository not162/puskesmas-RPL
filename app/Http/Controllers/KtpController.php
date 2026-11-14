<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Pasien;
use Illuminate\Support\Facades\Storage;

/** Foto KTP hanya bisa dibuka petugas berizin (pasien.daftar / akun.kelola), setiap akses diaudit. */
class KtpController extends Controller
{
    public function __invoke(Pasien $pasien)
    {
        abort_unless($pasien->foto_ktp && Storage::disk('local')->exists($pasien->foto_ktp), 404);
        AuditLog::catat('lihat_ktp', $pasien);
        return Storage::disk('local')->response($pasien->foto_ktp, 'ktp-'.$pasien->no_rm.'.'.pathinfo($pasien->foto_ktp, PATHINFO_EXTENSION), [
            'Cache-Control' => 'no-store, private',
        ]);
    }
}

