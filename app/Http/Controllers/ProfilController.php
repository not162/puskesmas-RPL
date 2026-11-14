<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfilController extends Controller
{
    public function edit(Request $request)
    {
        return view('profil', ['akun' => $request->user()->load('peran', 'pasien')]);
    }

    public function update(Request $request)
    {
        $akun = $request->user();
        $data = $request->validate([
            'email' => [$akun->pasien ? 'nullable' : 'required', 'email', Rule::unique('akun', 'email')->ignore($akun->id_akun, 'id_akun')],
            'no_tlp' => ['nullable', 'regex:/^(\+62|0)8[0-9]{8,12}$/'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'password_lama' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [], ['password' => 'kata sandi baru', 'password_lama' => 'kata sandi lama']);

        $akun->email = $data['email'];
        if (! empty($data['password'])) {
            $akun->password = $data['password'];
        }
        $akun->save();
        $akun->pasien?->update(array_filter(['no_tlp' => $data['no_tlp'] ?? null, 'alamat' => $data['alamat'] ?? null]) + ['wa_opt_in' => $request->boolean('wa_opt_in')]);

        return $this->kembali('success', 'Profil diperbarui', 'Perubahan profil berhasil disimpan.');
    }

    /** Hak akses data (UU PDP): unduh salinan data pribadi milik sendiri dalam format JSON. */
    public function unduhData(Request $request)
    {
        $akun = $request->user()->load('peran', 'pasien');
        $p = $akun->pasien;
        $data = [
            'dibuat' => now()->toIso8601String(),
            'sumber' => \App\Models\Pengaturan::nilai('nama_puskesmas'),
            'akun' => ['username' => $akun->username, 'email' => $akun->email, 'peran' => $akun->peran->label, 'terdaftar' => $akun->created_at?->toDateString()],
        ];
        if ($p) {
            $data['profil'] = $p->only(['no_rm', 'nik', 'no_kk', 'no_bpjs', 'nama', 'jenis_kelamin', 'alamat', 'no_tlp']) + ['tgl_lahir' => $p->tgl_lahir?->toDateString(), 'setuju_whatsapp' => $p->wa_opt_in, 'foto_ktp_tersimpan' => (bool) $p->foto_ktp];
            $data['antrean'] = $p->antrian()->with('spesialisasi')->get()->map(fn ($a) => ['nomor' => $a->nomor, 'poli' => $a->spesialisasi->poli, 'tanggal' => $a->tanggal->toDateString(), 'status' => $a->status]);
            $data['konsultasi_dan_pemeriksaan'] = $p->konsultasi()->with('dokter', 'spesialisasi')->get()->map(fn ($k) => ['tanggal' => $k->tanggal->toDateTimeString(), 'poli' => $k->spesialisasi->poli, 'jenis' => $k->jenis, 'keluhan' => $k->keluhan, 'diagnosis' => $k->diagnosis, 'tanggapan' => $k->tanggapan, 'dokter' => $k->dokter?->nama]);
            $data['resep'] = $p->resep()->with('obat', 'dokter')->get()->map(fn ($r) => ['tanggal' => $r->tgl_resep->toDateTimeString(), 'dokter' => $r->dokter->nama, 'status' => $r->status, 'obat' => $r->obat->map(fn ($o) => ['nama' => $o->nama_obat, 'jumlah' => $o->pivot->jumlah, 'aturan_pakai' => $o->pivot->aturan_pakai])]);
        }
        $data['riwayat_masuk'] = $akun->logLogin()->latest('waktu')->limit(50)->get(['waktu', 'hasil', 'ip']);
        \App\Models\AuditLog::catat('unduh_data', $akun);
        return response()->streamDownload(fn () => print(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
            'data-saya-'.$akun->username.'-'.now()->format('Ymd').'.json', ['Content-Type' => 'application/json; charset=UTF-8']);
    }
}
