<?php

namespace App\Http\Controllers;

use App\Support\EksporLaporan;
use App\Support\LaporanBulanan;
use Illuminate\Http\Request;

class LaporanController extends Controller
{
    public function index(Request $request)
    {
        return view('laporan.index', ['lap' => (new LaporanBulanan($request->query('bulan')))->semua()]);
    }

    public function cetak(Request $request)
    {
        return view('laporan.cetak', ['lap' => (new LaporanBulanan($request->query('bulan')))->semua()]);
    }

    /** Ekspor CSV (dibuka langsung oleh Excel; pemisah ; sesuai locale Indonesia). */
    public function export(Request $request)
    {
        $lap = (new LaporanBulanan($request->query('bulan')))->semua();
        $nama = 'laporan-puskesmas-'.$lap['periode'].'.csv';

        return response()->streamDownload(function () use ($lap) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF");
            $tulis = fn (array $row) => fputcsv($f, $row, ';');
            $tulis(['LAPORAN BULANAN PUSKESMAS', $lap['label']]);
            $tulis([]);
            $tulis(['RINGKASAN']);
            foreach ($lap['ringkasan'] as $k => $v) $tulis([ucwords(str_replace('_', ' ', $k)), $v]);
            $tulis([]);
            $tulis(['KUNJUNGAN PER POLI']); $tulis(['Poli', 'Total', 'Online', 'Loket']);
            foreach ($lap['per_poli'] as $r) $tulis(array_values($r));
            $tulis([]);
            $tulis(['LAYANAN PER DOKTER']); $tulis(['Dokter', 'Spesialisasi', 'Pemeriksaan', 'Konsultasi Online']);
            foreach ($lap['per_dokter'] as $r) $tulis(array_values($r));
            $tulis([]);
            $tulis(['OBAT KELUAR']); $tulis(['Obat', 'Satuan', 'Jumlah Keluar', 'Sisa Stok']);
            foreach ($lap['obat_keluar'] as $r) $tulis(array_values($r));
            $tulis([]);
            $tulis(['KUNJUNGAN HARIAN']); $tulis(['Tanggal', 'Kunjungan']);
            foreach ($lap['harian'] as $tgl => $n) $tulis([$tgl, $n]);
            fclose($f);
        }, $nama, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Excel .xlsx multi-sheet dengan rumus total & grafik. */
    public function xlsx(Request $request)
    {
        $lap = (new LaporanBulanan($request->query('bulan')))->semua();
        return response()->download(EksporLaporan::xlsx($lap), EksporLaporan::namaFile($lap, 'xlsx'), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    /** PDF dari server dengan kop surat & tanda tangan. ?unduh=0 untuk pratinjau di browser. */
    public function pdf(Request $request)
    {
        $lap = (new LaporanBulanan($request->query('bulan')))->semua();
        $cara = $request->query('unduh', '1') === '0' ? 'inline' : 'attachment';
        return response(EksporLaporan::pdf($lap), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $cara.'; filename="'.EksporLaporan::namaFile($lap, 'pdf').'"',
        ]);
    }
}
