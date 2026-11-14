<?php

namespace App\Http\Controllers\Pasien;

use App\Http\Controllers\Controller;
use App\Models\Antrian;
use App\Models\Konsultasi;
use App\Models\Spesialisasi;
use App\Support\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AntrianController extends Controller
{
    private function milik(Request $r, Antrian $a): Antrian
    {
        abort_unless($a->id_pasien === $r->user()->pasien?->id_pasien, 404);
        return $a;
    }

    public function index(Request $request)
    {
        return view('pasien.antrian.index', [
            'antrian' => $request->user()->pasien->antrian()->with('spesialisasi')->latest('tanggal')->latest('id_antrian')->paginate(10),
        ]);
    }

    public function create(Request $request)
    {
        $kons = $request->query('konsultasi')
            ? Konsultasi::where('kode', $request->query('konsultasi'))->where('id_pasien', $request->user()->pasien->id_pasien)->first()
            : null;
        return view('pasien.antrian.create', ['spesialisasi' => Spesialisasi::all(), 'konsultasi' => $kons]);
    }

    public function store(Request $request)
    {
        $pasien = $request->user()->pasien;
        $data = $request->validate([
            'id_spesialisasi' => ['required', 'exists:spesialisasi,id_spesialisasi'],
            'tanggal' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:'.today()->addDays((int) \App\Models\Pengaturan::nilai('maks_hari_antrean'))->toDateString()],
            'keluhan_awal' => ['nullable', 'string', 'max:500'],
            'konsultasi' => ['nullable', 'string'],
        ], ['tanggal.before_or_equal' => 'Antrean online melewati batas hari yang diizinkan.'], ['id_spesialisasi' => 'poli']);

        $sudahAda = Antrian::where('id_pasien', $pasien->id_pasien)->where('id_spesialisasi', $data['id_spesialisasi'])
            ->whereDate('tanggal', $data['tanggal'])->whereNot('status', 'batal')->exists();
        if ($sudahAda) {
            throw ValidationException::withMessages(['tanggal' => 'Anda sudah memiliki antrian aktif di poli ini pada tanggal tersebut.']);
        }

        $sp = Spesialisasi::findOrFail($data['id_spesialisasi']);
        $kons = ! empty($data['konsultasi']) ? Konsultasi::where('kode', $data['konsultasi'])->where('id_pasien', $pasien->id_pasien)->first() : null;

        $antrian = DB::transaction(fn () => Antrian::create([
            'id_pasien' => $pasien->id_pasien,
            'id_spesialisasi' => $sp->id_spesialisasi,
            'id_konsultasi' => $kons?->id_konsultasi,
            'tanggal' => $data['tanggal'],
            'nomor' => Antrian::nomorBerikut($sp, $data['tanggal']),
            'keluhan_awal' => $data['keluhan_awal'] ?? $kons?->keluhan,
            'sumber' => 'online',
            'status' => 'menunggu',
        ]));

        Notifier::pasien($pasien, 5, [
            'nomor' => $antrian->nomor, 'poli' => $sp->poli, 'tanggal' => $antrian->tanggal->locale('id')->translatedFormat('d F Y'),
        ], route('pasien.antrian.show', $antrian), 'success');

        Notifier::peran('loket', 'Antrean online baru', "{$antrian->nomor} · {$sp->poli} · {$antrian->tanggal->format('d/m/Y')}", route('loket.antrian', ['tanggal' => $antrian->tanggal->toDateString()]));
        return redirect()->route('pasien.antrian.show', $antrian)
            ->with('alert', ['tipe' => 'success', 'judul' => 'Antrian berhasil diambil', 'pesan' => "Nomor Anda {$antrian->nomor}. Unduh tiket dan tunjukkan di loket saat datang."]);
    }

    public function show(Request $request, Antrian $antrian)
    {
        return view('pasien.antrian.show', ['a' => $this->milik($request, $antrian)->load('spesialisasi', 'pasien')]);
    }

    /** Status live antrian milik pasien (dipolling halaman detail). */
    public function status(Request $request, Antrian $antrian)
    {
        $a = $this->milik($request, $antrian);
        return response()->json(['nomor' => $a->nomor, 'status' => $a->status, 'label' => $a->labelStatus(), 'di_depan' => $a->posisi()]);
    }

    /** Unduh tiket antrian (HTML mandiri, bisa dicetak / simpan PDF). */
    public function tiket(Request $request, Antrian $antrian)
    {
        $a = $this->milik($request, $antrian)->load('spesialisasi', 'pasien');
        $html = view('pasien.antrian.tiket', ['a' => $a])->render();
        return response($html)->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="tiket-antrian-'.$a->nomor.'-'.$a->tanggal->format('Ymd').'.html"');
    }

    public function cetak(Request $request, Antrian $antrian)
    {
        return view('pasien.antrian.tiket', ['a' => $this->milik($request, $antrian)->load('spesialisasi', 'pasien'), 'cetak' => true]);
    }

    public function batal(Request $request, Antrian $antrian)
    {
        $a = $this->milik($request, $antrian);
        if ($a->status !== 'menunggu') {
            return $this->kembali('warning', 'Tidak bisa dibatalkan', 'Antrian yang sudah check-in tidak dapat dibatalkan dari aplikasi.');
        }
        $a->update(['status' => 'batal']);
        Notifier::pasien($a->pasien, 5, [], route('pasien.antrian.index'), 'warning', "Antrian {$a->nomor} telah dibatalkan.");
        return $this->kembali('success', 'Antrian dibatalkan', "Antrian {$a->nomor} dibatalkan.", route('pasien.antrian.index'));
    }
}

