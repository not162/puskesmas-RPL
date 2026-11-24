<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
            'foto' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'password_lama' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [
            'foto.image' => 'Berkas avatar harus berupa file gambar.',
            'foto.mimes' => 'Format foto avatar harus berupa file .png, .jpg, .jpeg, atau .webp.',
            'foto.max' => 'Ukuran foto avatar maksimal 2 MB sesuai standar.',
        ], [
            'password' => 'kata sandi baru',
            'password_lama' => 'kata sandi lama',
            'foto' => 'foto avatar',
        ]);

        $akun->email = $data['email'];
        if (! empty($data['password'])) {
            $akun->password = $data['password'];
        }

        // Hapus foto jika diminta
        if ($request->boolean('hapus_foto')) {
            if ($akun->foto && Storage::disk('public')->exists($akun->foto)) {
                Storage::disk('public')->delete($akun->foto);
            }
            $akun->foto = null;
        }

        // Simpan foto avatar baru jika diunggah
        if ($request->hasFile('foto')) {
            if ($akun->foto && Storage::disk('public')->exists($akun->foto)) {
                Storage::disk('public')->delete($akun->foto);
            }
            $akun->foto = $request->file('foto')->store('avatars', 'public');
        }

        $akun->save();

        // Untuk pasien: Nomor HP dan Alamat terkunci/disabled sesuai data pendaftaran awal,
        // hanya preferensi WhatsApp (wa_opt_in) yang dapat diubah mandiri.
        if ($akun->pasien) {
            $akun->pasien->update([
                'wa_opt_in' => $request->boolean('wa_opt_in'),
            ]);
        }

        return $this->kembali('success', 'Profil diperbarui', 'Perubahan profil dan foto avatar berhasil disimpan.');
    }

    /** Menampilkan avatar user atau SVG fallback dengan inisial nama. */
    public function avatar(Akun $akun)
    {
        if ($akun->foto && Storage::disk('public')->exists($akun->foto)) {
            /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
            $disk = Storage::disk('public');
            return $disk->response($akun->foto, null, [
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }

        $inisial = $akun->inisial();
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="128" height="128" viewBox="0 0 128 128">'
            . '<defs><linearGradient id="g" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#0284c7"/><stop offset="100%" stop-color="#0369a1"/></linearGradient></defs>'
            . '<rect width="128" height="128" rx="64" fill="url(#g)"/>'
            . '<text x="50%" y="54%" font-family="system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif" font-size="44" font-weight="700" fill="#ffffff" dominant-baseline="middle" text-anchor="middle">'
            . htmlspecialchars($inisial, ENT_QUOTES)
            . '</text></svg>';

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /** Hak akses data (UU PDP): unduh salinan data pribadi milik sendiri dalam format JSON. */
    public function unduhData(Request $request)
    {
        $akun = $request->user()->load('peran', 'pasien');
        $p = $akun->pasien;
        $data = [
            'dibuat' => now()->toIso8601String(),
            'sumber' => \App\Models\Pengaturan::nilai('nama_puskesmas'),
            'akun' => [
                'username' => $akun->username,
                'email' => $akun->email,
                'foto' => $akun->foto ? $akun->avatarUrl() : null,
                'peran' => $akun->peran->label,
                'terdaftar' => $akun->created_at?->toDateString(),
            ],
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
