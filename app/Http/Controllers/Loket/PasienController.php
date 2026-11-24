<?php

namespace App\Http\Controllers\Loket;

use App\Http\Controllers\Controller;
use App\Models\Antrian;
use App\Models\Pasien;
use App\Models\Spesialisasi;
use App\Support\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Loket: daftar pasien datang langsung (tanpa akun) + ambil antrian di tempat. */
class PasienController extends Controller
{
    public function index(Request $request)
    {
        return view('loket.pasien.index', [
            'pasien' => Pasien::when($request->query('q'), fn ($q, $v) => $q->where('nama', 'like', "%$v%")->orWhere('nik', $v)->orWhere('no_rm', $v)->orWhere('no_bpjs', $v))
                ->latest('id_pasien')->paginate(20)->withQueryString(),
            'spesialisasi' => Spesialisasi::all(),
        ]);
    }

    public function create()
    {
        return view('loket.pasien.form', ['pasien' => new Pasien, 'spesialisasi' => Spesialisasi::all()]);
    }

    public function store(Request $request)
    {
        $data = $this->valid($request);
        [$pasien, $antrian] = DB::transaction(function () use ($data) {
            $p = Pasien::create(collect($data)->except('id_spesialisasi', 'keluhan_awal')->all() + ['no_rm' => Pasien::nomorRmBaru(), 'wa_opt_in' => request()->boolean('wa_opt_in')]);
            $a = null;
            if (! empty($data['id_spesialisasi'])) {
                $a = $this->buatAntrian($p, Spesialisasi::find($data['id_spesialisasi']), $data['keluhan_awal'] ?? null);
            }
            return [$p, $a];
        });

        $pesan = "Pasien {$pasien->nama} terdaftar dengan No. RM {$pasien->no_rm}.".($antrian ? " Nomor antrian: {$antrian->nomor}." : '');
        return $this->kembali('success', 'Pasien terdaftar', $pesan, route('loket.pasien.index'));
    }

    public function edit(Pasien $pasien)
    {
        return view('loket.pasien.form', ['pasien' => $pasien, 'spesialisasi' => Spesialisasi::all()]);
    }

    public function update(Request $request, Pasien $pasien)
    {
        $pasien->update(collect($this->valid($request, $pasien))->except('id_spesialisasi', 'keluhan_awal')->all() + ['wa_opt_in' => $request->boolean('wa_opt_in')]);
        return $this->kembali('success', 'Data diperbarui', "Data {$pasien->nama} disimpan.", route('loket.pasien.index'));
    }

    /** Ambil antrian di loket untuk pasien lama. */
    public function antrian(Request $request, Pasien $pasien)
    {
        $data = $request->validate(['id_spesialisasi' => ['required', 'exists:spesialisasi,id_spesialisasi'], 'keluhan_awal' => ['nullable', 'max:500']]);
        $sp = Spesialisasi::find($data['id_spesialisasi']);
        if (Antrian::where('id_pasien', $pasien->id_pasien)->where('id_spesialisasi', $sp->id_spesialisasi)->whereDate('tanggal', today())->whereNot('status', 'batal')->exists()) {
            return $this->kembali('warning', 'Sudah ada antrian', "{$pasien->nama} sudah memiliki antrian di {$sp->poli} hari ini.");
        }
        $a = DB::transaction(fn () => $this->buatAntrian($pasien, $sp, $data['keluhan_awal'] ?? null));
        return $this->kembali('success', 'Antrian dibuat', "Nomor antrian {$a->nomor} untuk {$pasien->nama}.");
    }

    private function buatAntrian(Pasien $p, Spesialisasi $sp, ?string $keluhan): Antrian
    {
        $a = Antrian::create([
            'id_pasien' => $p->id_pasien, 'id_spesialisasi' => $sp->id_spesialisasi, 'tanggal' => today()->toDateString(),
            'nomor' => Antrian::nomorBerikut($sp, today()->toDateString()), 'status' => 'hadir', 'sumber' => 'loket',
            'keluhan_awal' => $keluhan, 'waktu_hadir' => now(),
        ]);
        Notifier::pasien($p, 6, [], null, 'success', "Antrian {$a->nomor} dibuat di loket dan sudah check-in.");
        Notifier::peran(Notifier::peranDokterUntuk($sp->nama), 'Pasien siap diperiksa', "Antrian {$a->nomor} ({$p->nama}) hadir di {$sp->poli}.", route('dokter.antrian'));
        return $a;
    }

    private function valid(Request $request, ?Pasien $p = null): array
    {
        return $request->validate([
            'nik' => ['required', 'digits:16', Rule::unique('pasien', 'nik')->ignore($p?->id_pasien, 'id_pasien')],
            'nama' => ['required', 'max:100'],
            'tgl_lahir' => ['nullable', 'date', 'before:today'],
            'jenis_kelamin' => ['nullable', 'in:L,P'],
            'no_tlp' => ['nullable', 'max:20'],
            'no_bpjs' => ['nullable', 'digits:13', Rule::unique('pasien', 'no_bpjs')->ignore($p?->id_pasien, 'id_pasien')],
            'alamat' => ['nullable', 'max:500'],
            'id_spesialisasi' => ['nullable', 'exists:spesialisasi,id_spesialisasi'],
            'keluhan_awal' => ['nullable', 'max:500'],
        ], [], ['nik' => 'NIK']);
    }
}

