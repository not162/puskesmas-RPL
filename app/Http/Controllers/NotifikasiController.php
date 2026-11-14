<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotifikasiController extends Controller
{
    public function index(Request $request)
    {
        return view('notifikasi.index', ['notifikasi' => $request->user()->notifications()->paginate(15)]);
    }

    /** Dipolling UI tiap 15 detik: jumlah belum dibaca + item baru sejak `sejak` (untuk toast). */
    public function feed(Request $request)
    {
        $akun = $request->user();
        $sejak = $request->query('sejak');
        $baru = $akun->unreadNotifications()
            ->when($sejak, fn ($q) => $q->where('created_at', '>', \Carbon\Carbon::createFromTimestamp((int) $sejak)))
            ->limit(5)->get()
            ->map(fn ($n) => ['id' => $n->id, 'waktu' => $n->created_at->diffForHumans()] + $n->data);

        return response()->json([
            'belum_dibaca' => $akun->unreadNotifications()->count(),
            'baru' => $baru,
            'server_time' => now()->timestamp,
            'terbaru' => $akun->notifications()->limit(6)->get()->map(fn ($n) => [
                'id' => $n->id, 'dibaca' => (bool) $n->read_at, 'waktu' => $n->created_at->diffForHumans(),
            ] + $n->data),
        ]);
    }

    public function baca(Request $request, string $id)
    {
        $n = $request->user()->notifications()->findOrFail($id);
        $n->markAsRead();
        return $n->data['url'] ?? null ? redirect($n->data['url']) : back();
    }

    public function bacaSemua(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();
        return $this->kembali('success', 'Notifikasi', 'Semua notifikasi ditandai sudah dibaca.');
    }
}

