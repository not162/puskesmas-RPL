<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\AntrianLive;
use App\Support\Hmac;
use App\Support\Notifier;
use Illuminate\Http\Request;

/** API untuk perangkat kiosk / layar TV — diamankan HMAC (middleware hmac). */
class KioskApiController extends Controller
{
    public function antrian()
    {
        return response()->json(AntrianLive::data());
    }

    public function checkin(Request $request)
    {
        $data = $request->validate(['qr' => ['required', 'string', 'max:120']]);
        $a = Hmac::verifikasiQr($data['qr']);
        if (! $a) {
            AuditLog::catat('qr_tolak', 'antrian', ['kiosk' => $request->attributes->get('kiosk_id')]);
            return response()->json(['message' => 'QR tidak valid.'], 422);
        }
        if (! $a->tanggal->isToday() || $a->status !== 'menunggu') {
            return response()->json(['message' => 'Antrean tidak dapat check-in.', 'status' => $a->status, 'tanggal' => $a->tanggal->toDateString()], 409);
        }
        $a->update(['status' => 'hadir', 'waktu_hadir' => now()]);
        AuditLog::catat('checkin_kiosk', $a, ['kiosk' => $request->attributes->get('kiosk_id')]);
        Notifier::pasien($a->pasien, 6, [], null, 'success');
        return ['message' => 'Check-in berhasil.', 'nomor' => $a->nomor, 'poli' => $a->spesialisasi->poli, 'di_depan' => $a->posisi()];
    }
}
