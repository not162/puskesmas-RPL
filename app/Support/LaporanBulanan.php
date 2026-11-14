<?php

namespace App\Support;

use App\Models\Antrian;
use App\Models\Konsultasi;
use App\Models\Pasien;
use App\Models\PenyerahanObat;
use App\Models\Resep;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class LaporanBulanan
{
    public CarbonImmutable $awal;
    public CarbonImmutable $akhir;

    public function __construct(?string $bulan = null)
    {
        $this->awal = CarbonImmutable::createFromFormat('Y-m-d', ($bulan && preg_match('/^\d{4}-\d{2}$/', $bulan) ? $bulan : now()->format('Y-m')).'-01')->startOfMonth();
        $this->akhir = $this->awal->endOfMonth();
    }

    public function label(): string
    {
        return $this->awal->locale('id')->translatedFormat('F Y');
    }

    public function ringkasan(): array
    {
        $r = [$this->awal, $this->akhir];
        return [
            'pasien_baru' => Pasien::whereBetween('created_at', $r)->count(),
            'kunjungan' => Antrian::whereBetween('tanggal', [$this->awal->toDateString(), $this->akhir->toDateString()])->whereNot('status', 'batal')->count(),
            'antrian_online' => Antrian::whereBetween('tanggal', [$this->awal->toDateString(), $this->akhir->toDateString()])->where('sumber', 'online')->count(),
            'antrian_batal' => Antrian::whereBetween('tanggal', [$this->awal->toDateString(), $this->akhir->toDateString()])->where('status', 'batal')->count(),
            'konsultasi_online' => Konsultasi::whereBetween('tanggal', $r)->where('jenis', 'online')->count(),
            'pemeriksaan' => Konsultasi::whereBetween('tanggal', $r)->where('jenis', 'langsung')->count(),
            'resep' => Resep::whereBetween('tgl_resep', $r)->count(),
            'obat_diserahkan' => PenyerahanObat::whereBetween('waktu', $r)->count(),
        ];
    }

    public function perPoli(): array
    {
        return DB::table('antrian')->join('spesialisasi', 'spesialisasi.id_spesialisasi', '=', 'antrian.id_spesialisasi')
            ->whereBetween('antrian.tanggal', [$this->awal->toDateString(), $this->akhir->toDateString()])
            ->whereNot('antrian.status', 'batal')
            ->groupBy('spesialisasi.poli')->orderBy('spesialisasi.poli')
            ->selectRaw('spesialisasi.poli as poli, count(*) as total, sum(antrian.sumber = "online") as online, sum(antrian.sumber = "loket") as loket')
            ->get()->map(fn ($x) => (array) $x)->all();
    }

    public function perDokter(): array
    {
        return DB::table('konsultasi')->join('dokter', 'dokter.id_dokter', '=', 'konsultasi.id_dokter')
            ->join('spesialisasi', 'spesialisasi.id_spesialisasi', '=', 'dokter.id_spesialisasi')
            ->whereBetween('konsultasi.tanggal', [$this->awal, $this->akhir])
            ->groupBy('dokter.nama', 'spesialisasi.nama')->orderBy('dokter.nama')
            ->selectRaw('dokter.nama as dokter, spesialisasi.nama as spesialisasi, sum(konsultasi.jenis = "langsung") as pemeriksaan, sum(konsultasi.jenis = "online") as online')
            ->get()->map(fn ($x) => (array) $x)->all();
    }

    public function obatKeluar(): array
    {
        return DB::table('detail_resep')->join('resep', 'resep.id_resep', '=', 'detail_resep.id_resep')
            ->join('penyerahan_obat', 'penyerahan_obat.id_resep', '=', 'resep.id_resep')
            ->join('obat', 'obat.id_obat', '=', 'detail_resep.id_obat')
            ->whereBetween('penyerahan_obat.waktu', [$this->awal, $this->akhir])
            ->groupBy('obat.nama_obat', 'obat.satuan', 'obat.stok')->orderByDesc('jumlah')
            ->selectRaw('obat.nama_obat as obat, obat.satuan as satuan, sum(detail_resep.jumlah) as jumlah, obat.stok as sisa_stok')
            ->get()->map(fn ($x) => (array) $x)->all();
    }

    public function harian(): array
    {
        $rows = DB::table('antrian')->whereBetween('tanggal', [$this->awal->toDateString(), $this->akhir->toDateString()])
            ->whereNot('status', 'batal')->groupBy('tanggal')->selectRaw('tanggal, count(*) as total')->pluck('total', 'tanggal');
        $out = [];
        for ($d = $this->awal; $d <= $this->akhir; $d = $d->addDay()) {
            $out[$d->format('d')] = (int) ($rows[$d->toDateString()] ?? 0);
        }
        return $out;
    }

    public function semua(): array
    {
        return [
            'periode' => $this->awal->format('Y-m'),
            'label' => $this->label(),
            'ringkasan' => $this->ringkasan(),
            'per_poli' => $this->perPoli(),
            'per_dokter' => $this->perDokter(),
            'obat_keluar' => $this->obatKeluar(),
            'harian' => $this->harian(),
        ];
    }
}

