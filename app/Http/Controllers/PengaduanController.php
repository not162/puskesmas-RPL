<?php

namespace App\Http\Controllers;

use App\Models\Pengaduan;
use App\Notifications\InfoStaf;
use Illuminate\Http\Request;

/** Penanganan pengaduan oleh Loket / Admin / Super Admin (izin pengaduan.kelola). */
class PengaduanController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'aktif');
        return view('pengaduan.index', [
            'status' => $status,
            'pengaduan' => Pengaduan::with('akun.pasien', 'penanggap')
                ->when($status === 'aktif', fn ($q) => $q->whereIn('status', ['baru', 'diproses']), fn ($q) => $status === 'semua' ? $q : $q->where('status', $status))
                ->latest()->paginate(15)->withQueryString(),
        ]);
    }

    public function show(Pengaduan $pengaduan)
    {
        return view('pengaduan.show', ['p' => $pengaduan->load('akun.pasien', 'penanggap')]);
    }

    public function update(Request $request, Pengaduan $pengaduan)
    {
        $data = $request->validate(['status' => ['required', 'in:baru,diproses,selesai'], 'tanggapan' => ['nullable', 'required_if:status,selesai', 'max:3000']],
            ['tanggapan.required_if' => 'Tanggapan wajib diisi saat menyelesaikan pengaduan.']);
        $pengaduan->update($data + ['id_penanggap' => $request->user()->id_akun]);
        $pengaduan->akun?->notify(new InfoStaf('Pengaduan Anda '.$data['status'], $pengaduan->judul.($data['tanggapan'] ?? null ? ' — '.$data['tanggapan'] : ''), route('pasien.pengaduan'), $data['status'] === 'selesai' ? 'success' : 'info'));
        return $this->kembali('success', 'Pengaduan diperbarui', "Status: {$data['status']}. Pelapor diberi notifikasi.", route('pengaduan.index'));
    }

    public function destroy(Pengaduan $pengaduan)
    {
        $pengaduan->delete();
        return $this->kembali('success', 'Pengaduan dihapus', $pengaduan->judul, route('pengaduan.index'));
    }
}

