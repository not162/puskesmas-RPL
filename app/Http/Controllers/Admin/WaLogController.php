<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WaLog;
use App\Support\WhatsApp;
use Illuminate\Http\Request;

class WaLogController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.wa-log', [
            'log' => WaLog::with('akun')->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))->latest('id')->paginate(30)->withQueryString(),
            'ringkas' => WaLog::where('created_at', '>=', today())->selectRaw('status, count(*) n')->groupBy('status')->pluck('n', 'status'),
        ]);
    }

    /** Kirim pesan uji ke nomor tertentu (Super Admin). */
    public function uji(Request $request)
    {
        $data = $request->validate(['nomor' => ['required', 'string', 'max:20']]);
        $status = WhatsApp::kirim($data['nomor'], '*Uji WhatsApp* — konfigurasi notifikasi berhasil.', 'uji', $request->user()->id_akun);
        $tipe = in_array($status, ['terkirim', 'log', 'antre']) ? 'success' : 'danger';
        return $this->kembali($tipe, 'Uji WhatsApp', "Status: {$status}".($status === 'log' ? ' (driver log: pesan hanya dicatat, tidak dikirim)' : ''));
    }
}

