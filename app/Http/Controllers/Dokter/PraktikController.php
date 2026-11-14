<?php

namespace App\Http\Controllers\Dokter;

use App\Http\Controllers\Controller;
use App\Models\Antrian;
use App\Models\Konsultasi;
use App\Models\Obat;
use App\Models\Resep;
use App\Support\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Dokter umum & dokter kandungan: hanya melihat antrian/konsultasi
 * di poli sesuai spesialisasinya sendiri.
 */
class PraktikController extends Controller
{
    private function dokter(Request $r) { return $r->user()->dokter->load('spesialisasi'); }

    private function poliSaya(Request $r, Antrian|Konsultasi $x): void
    {
        abort_unless($x->id_spesialisasi === $this->dokter($r)->id_spesialisasi, 403, 'Bukan pasien poli Anda.');
    }

    public function antrian(Request $request)
    {
        $d = $this->dokter($request);
        return view('dokter.antrian', [
            'dokter' => $d,
            'antrian' => Antrian::with('pasien')->whereDate('tanggal', today())->where('id_spesialisasi', $d->id_spesialisasi)
                ->whereIn('status', ['hadir', 'dipanggil', 'dilayani', 'menunggu'])->orderByRaw("FIELD(status,'dilayani','dipanggil','hadir','menunggu')")->orderBy('id_antrian')->get(),
            'selesai' => Antrian::with('pasien')->whereDate('tanggal', today())->where('id_spesialisasi', $d->id_spesialisasi)->where('status', 'selesai')->latest('updated_at')->limit(10)->get(),
        ]);
    }

    public function panggil(Request $request, Antrian $antrian)
    {
        $this->poliSaya($request, $antrian);
        if (! in_array($antrian->status, ['hadir', 'dipanggil'])) {
            return $this->kembali('warning', 'Belum check-in', 'Pasien belum check-in di loket.');
        }
        $antrian->update(['status' => 'dipanggil', 'waktu_panggil' => now(), 'id_dokter' => $this->dokter($request)->id_dokter]);
        Notifier::pasien($antrian->pasien, 7, ['nomor' => $antrian->nomor, 'poli' => $antrian->spesialisasi->poli], route('pasien.antrian.show', $antrian), 'warning');
        return $this->kembali('info', 'Pasien dipanggil', "Nomor {$antrian->nomor} tampil di layar antrian.");
    }

    public function periksa(Request $request, Antrian $antrian)
    {
        $this->poliSaya($request, $antrian);
        if (in_array($antrian->status, ['selesai', 'batal', 'menunggu'])) {
            return $this->kembali('warning', 'Tidak dapat diperiksa', "Status antrian: {$antrian->labelStatus()}.");
        }
        $antrian->update(['status' => 'dilayani', 'id_dokter' => $this->dokter($request)->id_dokter]);
        return view('dokter.periksa', [
            'a' => $antrian->load('pasien', 'konsultasi', 'spesialisasi'),
            'riwayat' => Konsultasi::with('dokter')->where('id_pasien', $antrian->id_pasien)->whereNotNull('diagnosis')->latest('tanggal')->limit(5)->get(),
            'obat' => Obat::tersedia()->orderByRaw('kode is null')->orderBy('kategori')->orderBy('kode')->get(),
        ]);
    }

    public function simpanPeriksa(Request $request, Antrian $antrian)
    {
        $this->poliSaya($request, $antrian);
        $data = $request->validate([
            'keluhan' => ['required', 'string', 'max:2000'],
            'diagnosis' => ['required', 'string', 'max:2000'],
            'obat' => ['nullable', 'array'],
            'obat.*.id_obat' => ['nullable', 'exists:obat,id_obat', 'distinct'],
            'obat.*.jumlah' => ['nullable', 'required_with:obat.*.id_obat', 'integer', 'min:1', 'max:500'],
            'obat.*.aturan_pakai' => ['nullable', 'required_with:obat.*.id_obat', 'string', 'max:100'],
        ], ['obat.*.id_obat.distinct' => 'Obat yang sama dipilih dua kali.']);

        $d = $this->dokter($request);
        $items = collect($data['obat'] ?? [])->filter(fn ($o) => ! empty($o['id_obat']));

        $resep = DB::transaction(function () use ($antrian, $data, $d, $items) {
            $k = $antrian->konsultasi;
            $isi = ['id_dokter' => $d->id_dokter, 'diagnosis' => $data['diagnosis'], 'status' => 'selesai'];
            if ($k && $k->jenis === 'online') {
                $k->update($isi); // konsultasi online dilanjutkan pemeriksaan langsung
            }
            $periksa = Konsultasi::create($isi + [
                'id_pasien' => $antrian->id_pasien, 'id_spesialisasi' => $antrian->id_spesialisasi,
                'tanggal' => now(), 'keluhan' => $data['keluhan'], 'jenis' => 'langsung',
            ]);
            $antrian->update(['status' => 'selesai', 'id_konsultasi' => $periksa->id_konsultasi]);

            if ($items->isEmpty()) return null;
            $r = Resep::create(['id_pasien' => $antrian->id_pasien, 'id_dokter' => $d->id_dokter, 'id_konsultasi' => $periksa->id_konsultasi, 'status' => 'baru', 'tgl_resep' => now()]);
            foreach ($items as $o) {
                $r->obat()->attach($o['id_obat'], ['jumlah' => $o['jumlah'], 'aturan_pakai' => $o['aturan_pakai']]);
            }
            return $r;
        });

        \App\Support\RekomendasiObat::lupakanGraf(); // graf belajar dari resep baru
        if ($resep) {
            Notifier::pasien($antrian->pasien, 8, [], route('pasien.resep'), 'success');
            Notifier::peran('apoteker', 'Resep baru', "Resep untuk antrian {$antrian->nomor} dari {$d->nama}.", route('farmasi.resep.show', $resep));
        } else {
            Notifier::pasien($antrian->pasien, 8, [], route('dashboard'), 'info', 'Pemeriksaan selesai tanpa resep obat.');
        }

        return $this->kembali('success', 'Pemeriksaan disimpan', $resep ? 'Resep diteruskan ke farmasi.' : 'Pemeriksaan selesai tanpa resep.', route('dokter.antrian'));
    }

    public function konsultasi(Request $request)
    {
        $d = $this->dokter($request);
        return view('dokter.konsultasi.index', [
            'dokter' => $d,
            'konsultasi' => Konsultasi::with('pasien', 'dokter')->where('id_spesialisasi', $d->id_spesialisasi)->where('jenis', 'online')
                ->when($request->query('status', 'menunggu'), fn ($q, $s) => $s === 'semua' ? $q : $q->where('status', $s))
                ->latest('tanggal')->paginate(15)->withQueryString(),
        ]);
    }

    public function showKonsultasi(Request $request, Konsultasi $konsultasi)
    {
        $this->poliSaya($request, $konsultasi);
        return view('dokter.konsultasi.show', [
            'k' => $konsultasi->load('pasien', 'dokter', 'spesialisasi'),
            'riwayat' => Konsultasi::where('id_pasien', $konsultasi->id_pasien)->whereNot('id_konsultasi', $konsultasi->id_konsultasi)->latest('tanggal')->limit(5)->get(),
        ]);
    }

    public function tanggapi(Request $request, Konsultasi $konsultasi)
    {
        $this->poliSaya($request, $konsultasi);
        $data = $request->validate([
            'tanggapan' => ['required', 'string', 'min:5', 'max:3000'],
            'diagnosis' => ['nullable', 'string', 'max:1000'],
        ], [], ['diagnosis' => 'diagnosis sementara']);
        $d = $this->dokter($request);
        $konsultasi->update($data + ['id_dokter' => $d->id_dokter, 'status' => 'ditanggapi']);
        Notifier::pasien($konsultasi->pasien, 4, ['dokter' => $d->nama], route('pasien.konsultasi.show', $konsultasi), 'success');
        return $this->kembali('success', 'Tanggapan terkirim', 'Pasien telah diberi notifikasi.', route('dokter.konsultasi'));
    }

    /** Saran obat hibrida (graf + konten + kolaboratif) untuk resep yang sedang ditulis. */
    public function rekomendasiObat(Request $request)
    {
        $d = $this->dokter($request);
        $dipilih = array_filter(array_map('intval', (array) $request->query('dipilih', [])));
        $teks = mb_substr((string) $request->query('teks', ''), 0, 2000);
        return response()->json([
            'poli' => $d->spesialisasi->poli,
            'bobot' => \App\Support\RekomendasiObat::BOBOT,
            'saran' => \App\Support\RekomendasiObat::untuk($dipilih, $teks, $d->spesialisasi->nama, $d->id_dokter),
        ]);
    }

    public function riwayatResep(Request $request)
    {
        return view('dokter.resep', [
            'resep' => Resep::with('pasien', 'obat')->where('id_dokter', $this->dokter($request)->id_dokter)->latest('tgl_resep')->paginate(15),
        ]);
    }
}

