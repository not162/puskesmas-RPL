<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Akun;
use App\Models\Peran;
use App\Models\TemplateNotifikasi;
use App\Notifications\InfoStaf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class TemplateController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.template', [
            'template' => TemplateNotifikasi::orderBy('langkah')->get(),
            'peran' => Peran::all(),
            'bolehUbah' => $request->user()->punyaIzin('template.kelola'),
        ]);
    }

    public function update(Request $request, TemplateNotifikasi $template)
    {
        abort_unless($request->user()->punyaIzin('template.kelola'), 403);
        $template->update($request->validate(['judul' => ['required', 'max:100'], 'pesan' => ['required', 'max:300']]) + ['aktif' => $request->boolean('aktif')]);
        return $this->kembali('success', 'Template disimpan', "Langkah {$template->langkah}: {$template->judul}");
    }

    /** Tombol notifikasi alert: broadcast ke satu/semua peran. */
    public function broadcast(Request $request)
    {
        $data = $request->validate([
            'peran' => ['required', 'array', 'min:1'], 'peran.*' => ['exists:peran,nama_peran'],
            'judul' => ['required', 'max:100'], 'pesan' => ['required', 'max:500'], 'tipe' => ['required', 'in:info,success,warning,danger'],
        ]);
        $akun = Akun::whereHas('peran', fn ($q) => $q->whereIn('nama_peran', $data['peran']))->where('status', 'aktif')->get();
        Notification::send($akun, new InfoStaf($data['judul'], $data['pesan'], null, $data['tipe']));
        return $this->kembali('success', 'Alert terkirim', "Dikirim ke {$akun->count()} akun secara real-time.");
    }
}

