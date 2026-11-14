<?php

namespace App\Support;

use App\Models\Pengaturan;
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Ekspor laporan bulanan:
 *  - .xlsx multi-sheet (Ringkasan, Per Poli, Per Dokter, Obat Keluar, Harian + grafik) dengan rumus total
 *  - PDF dari server (Dompdf) dengan kop surat & tanda tangan Kepala Puskesmas
 */
class EksporLaporan
{
    public const HAK_CIPTA = '© 2026 Seluruh hak cipta dilindungi · Grup NussCorp Technologies Indonesia';
    private const BIRU = '2563EB';

    public static function namaFile(array $lap, string $ext): string
    {
        return 'laporan-puskesmas-'.$lap['periode'].'.'.$ext;
    }

    // ============================== XLSX ==============================

    public static function xlsx(array $lap): string
    {
        $p = Pengaturan::semua();
        $book = new Spreadsheet;
        $book->getProperties()->setCreator($p['nama_puskesmas'])->setCompany('NussCorp Technologies Indonesia')
            ->setTitle('Laporan Bulanan '.$lap['label'])->setSubject('Laporan Bulanan Puskesmas')
            ->setDescription('Dibuat di Indonesia · '.self::HAK_CIPTA);
        $book->getDefaultStyle()->getFont()->setName('Calibri')->setSize(11);

        // --- Ringkasan ---
        $ws = $book->getActiveSheet()->setTitle('Ringkasan');
        $baris = self::kop($ws, $p, $lap, 'B');
        $ws->fromArray(['Indikator', 'Jumlah'], null, "A$baris");
        self::gayaHeader($ws, "A$baris:B$baris");
        $awal = $baris + 1;
        foreach ($lap['ringkasan'] as $k => $v) {
            $baris++;
            $ws->setCellValue("A$baris", ucwords(str_replace('_', ' ', $k)))->setCellValue("B$baris", (int) $v);
        }
        self::gayaTabel($ws, "A".($awal - 1).":B$baris");
        $ws->getColumnDimension('A')->setWidth(34);
        $ws->getColumnDimension('B')->setWidth(14);
        self::kaki($ws, $baris + 2);

        // --- Tabel detail ---
        self::sheetTabel($book, 'Per Poli', ['Poli', 'Total Kunjungan', 'Online', 'Loket'], $lap['per_poli'], [1, 2, 3], $p, $lap);
        self::sheetTabel($book, 'Per Dokter', ['Dokter', 'Spesialisasi', 'Pemeriksaan', 'Konsultasi Online'], $lap['per_dokter'], [2, 3], $p, $lap);
        self::sheetTabel($book, 'Obat Keluar', ['Obat', 'Satuan', 'Jumlah Keluar', 'Sisa Stok'], $lap['obat_keluar'], [2], $p, $lap);

        // --- Harian + grafik batang ---
        $rows = [];
        foreach ($lap['harian'] as $tgl => $n) $rows[] = ['tanggal' => (int) $tgl, 'kunjungan' => $n];
        $wh = self::sheetTabel($book, 'Harian', ['Tanggal', 'Kunjungan'], $rows, [1], $p, $lap);
        $mulai = 6; // baris data pertama (kop 4 baris + header)
        $akhir = $mulai + count($rows) - 1;
        $label = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'Harian'!\$B$" . ($mulai - 1), null, 1)];
        $kategori = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'Harian'!\$A\$$mulai:\$A\$$akhir", null, count($rows))];
        $nilai = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'Harian'!\$B\$$mulai:\$B\$$akhir", null, count($rows))];
        $seri = new DataSeries(DataSeries::TYPE_BARCHART, DataSeries::GROUPING_CLUSTERED, [0], $label, $kategori, $nilai);
        $seri->setPlotDirection(DataSeries::DIRECTION_COL);
        $chart = new Chart('grafikHarian', new Title('Kunjungan Harian '.$lap['label']), new Legend(Legend::POSITION_BOTTOM), new PlotArea(null, [$seri]));
        $chart->setTopLeftPosition('D5')->setBottomRightPosition('P24');
        $wh->addChart($chart);

        $book->setActiveSheetIndex(0);
        $tmp = tempnam(sys_get_temp_dir(), 'lap').'.xlsx';
        $w = new Xlsx($book);
        $w->setIncludeCharts(true);
        $w->save($tmp);
        return $tmp;
    }

    private static function kop(Worksheet $ws, array $p, array $lap, string $kolomAkhir): int
    {
        $ws->setCellValue('A1', strtoupper($p['nama_puskesmas']))->mergeCells("A1:{$kolomAkhir}1");
        $ws->setCellValue('A2', $p['alamat'])->mergeCells("A2:{$kolomAkhir}2");
        $ws->setCellValue('A3', 'LAPORAN BULANAN · '.strtoupper($lap['label']))->mergeCells("A3:{$kolomAkhir}3");
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB(self::BIRU);
        $ws->getStyle('A3')->getFont()->setBold(true);
        $ws->getStyle('A2')->getFont()->setItalic(true)->setSize(9);
        $ws->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT)->setPaperSize(PageSetup::PAPERSIZE_A4)->setFitToWidth(1)->setFitToHeight(0);
        $ws->getHeaderFooter()->setOddFooter('&L'.$p['nama_puskesmas'].'&CDibuat di Indonesia · © 2026 NussCorp Technologies Indonesia&RHal &P/&N');
        return 5;
    }

    private static function kaki(Worksheet $ws, int $baris): void
    {
        $ws->setCellValue("A$baris", 'Dicetak '.now()->format('d/m/Y H:i').' · Dibuat di Indonesia · '.self::HAK_CIPTA);
        $ws->getStyle("A$baris")->getFont()->setItalic(true)->setSize(8)->getColor()->setRGB('667085');
    }

    private static function sheetTabel(Spreadsheet $book, string $judul, array $header, array $rows, array $kolomTotal, array $p, array $lap): Worksheet
    {
        $ws = $book->createSheet()->setTitle($judul);
        $akhirKol = chr(ord('A') + count($header) - 1);
        $h = self::kop($ws, $p, $lap, $akhirKol);
        $ws->fromArray($header, null, "A$h");
        self::gayaHeader($ws, "A$h:$akhirKol$h");
        $b = $h;
        foreach ($rows as $r) {
            $b++;
            // nullValue sentinel: fromArray membandingkan longgar, angka 0 akan terlewat bila memakai null
            $ws->fromArray(array_map(fn ($v) => is_numeric($v) ? $v + 0 : ($v ?? ''), array_values($r)), '__KOSONG__', "A$b", true);
        }
        if (! $rows) {
            $b++;
            $ws->setCellValue("A$b", 'Tidak ada data pada periode ini');
        } else {
            $b++;
            $ws->setCellValue("A$b", 'TOTAL');
            foreach ($kolomTotal as $i) {
                $kol = chr(ord('A') + $i);
                $ws->setCellValue("$kol$b", "=SUM({$kol}".($h + 1).":{$kol}".($b - 1).')');
            }
            $ws->getStyle("A$b:$akhirKol$b")->getFont()->setBold(true);
            $ws->getStyle("A$b:$akhirKol$b")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EEF2FA');
        }
        self::gayaTabel($ws, "A$h:$akhirKol$b");
        foreach (range('A', $akhirKol) as $k) $ws->getColumnDimension($k)->setAutoSize(true);
        $ws->freezePane('A'.($h + 1));
        $ws->setAutoFilter("A$h:$akhirKol".max($h, $b - 1));
        self::kaki($ws, $b + 2);
        return $ws;
    }

    private static function gayaHeader(Worksheet $ws, string $rentang): void
    {
        $s = $ws->getStyle($rentang);
        $s->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $s->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::BIRU);
        $s->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
    }

    private static function gayaTabel(Worksheet $ws, string $rentang): void
    {
        $ws->getStyle($rentang)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('C9D3E5');
    }

    // ============================== PDF ==============================

    public static function pdf(array $lap): string
    {
        $p = Pengaturan::semua();
        $html = view('laporan.pdf', ['lap' => $lap, 'p' => $p, 'hakCipta' => self::HAK_CIPTA])->render();

        $opt = new Options;
        $opt->set('defaultFont', 'DejaVu Sans');
        $opt->set('isRemoteEnabled', false); // tidak memuat sumber luar
        $opt->set('isPhpEnabled', false);
        $opt->setChroot(public_path());
        $pdf = new Dompdf($opt);
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();

        $pdf->getCanvas()->page_text(500, 815, 'Hal {PAGE_NUM} / {PAGE_COUNT}', 'DejaVu Sans', 7, [0.4, 0.45, 0.55]);
        $pdf->addInfo('Title', 'Laporan Bulanan '.$lap['label']);
        $pdf->addInfo('Author', $p['nama_puskesmas']);
        $pdf->addInfo('Creator', 'NussCorp Technologies Indonesia');
        return $pdf->output();
    }
}
