<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Laporan Bulanan {{ $lap['label'] }}</title>
<style>
  @page { margin: 26mm 16mm 20mm 16mm; }
  body { font-family: "DejaVu Sans", sans-serif; font-size: 9.5pt; color: #111827; }
  header { position: fixed; top: -20mm; left: 0; right: 0; }
  footer { position: fixed; bottom: -14mm; left: 0; right: 0; font-size: 7pt; color: #667085; border-top: 1px solid #c9d3e5; padding-top: 4px; }
  .kop { border-bottom: 3px double #1e3a8a; padding-bottom: 6px; }
  .kop td { vertical-align: middle; }
  .logo { width: 42px; height: 42px; background: #2563eb; color: #fff; font-size: 24pt; text-align: center; line-height: 42px; border-radius: 8px; }
  .instansi { font-size: 7.5pt; color: #374151; letter-spacing: .5px; }
  .nama { font-size: 14pt; font-weight: bold; color: #1e3a8a; }
  .alamat { font-size: 7.5pt; color: #4b5563; }
  h1 { font-size: 12pt; text-align: center; margin: 6px 0 2px; text-transform: uppercase; }
  .periode { text-align: center; margin: 0 0 12px; color: #374151; }
  h2 { font-size: 10pt; margin: 14px 0 6px; color: #1e3a8a; border-left: 3px solid #2563eb; padding-left: 6px; }
  table.data { width: 100%; border-collapse: collapse; }
  table.data th { background: #2563eb; color: #fff; font-weight: bold; padding: 5px 6px; text-align: left; font-size: 8.5pt; }
  table.data td { border-bottom: 1px solid #dde5f2; padding: 4px 6px; }
  table.data tr.total td { font-weight: bold; background: #eef2fa; border-top: 1px solid #94a3b8; }
  .angka { text-align: right; }
  .ringkas td { width: 25%; padding: 6px; border: 1px solid #dde5f2; }
  .ringkas b { display: block; font-size: 13pt; color: #1e3a8a; }
  .ringkas span { font-size: 7.5pt; color: #4b5563; }
  .grafik td { vertical-align: bottom; text-align: center; font-size: 6pt; color: #4b5563; padding: 0 1px; }
  .bar { background: #2563eb; width: 100%; }
  .kosong { color: #6b7280; font-style: italic; text-align: center; padding: 8px; }
  .ttd { width: 100%; margin-top: 26px; page-break-inside: avoid; }
  .ttd td { width: 50%; vertical-align: top; text-align: center; }
  .garis { margin-top: 56px; font-weight: bold; text-decoration: underline; }
</style>
</head>
<body>
<header>
  <table class="kop" width="100%"><tr>
    <td width="52"><div class="logo">✚</div></td>
    <td>
      <div class="instansi">PEMERINTAH {{ strtoupper($p['kota'] ?? '') }} · DINAS KESEHATAN</div>
      <div class="nama">{{ strtoupper($p['nama_puskesmas']) }}</div>
      <div class="alamat">{{ $p['alamat'] }} · Telp. {{ $p['telepon'] }} · {{ $p['email'] }}</div>
    </td>
  </tr></table>
</header>
<footer>
  <table width="100%"><tr>
    <td>Dicetak {{ now()->format('d/m/Y H:i') }} · Dibuat di Indonesia · {{ $hakCipta }}</td>
  </tr></table>
</footer>

<h1>Laporan Bulanan Pelayanan</h1>
<p class="periode">Periode {{ $lap['label'] }}</p>

<h2>Ringkasan</h2>
@php $r = $lap['ringkasan']; $items = array_chunk($r, 4, true); @endphp
<table class="ringkas" width="100%" style="border-collapse:collapse">
  @foreach ($items as $baris)
    <tr>@foreach ($baris as $k => $v)<td><b>{{ number_format($v, 0, ',', '.') }}</b><span>{{ ucwords(str_replace('_', ' ', $k)) }}</span></td>@endforeach</tr>
  @endforeach
</table>

<h2>Kunjungan Harian</h2>
@php $max = max(1, max($lap['harian'])); @endphp
<table class="grafik" width="100%" style="height:110px"><tr>
  @foreach ($lap['harian'] as $tgl => $n)
    <td style="height:110px">@if($n)<div style="font-size:5.5pt">{{ $n }}</div>@endif<div class="bar" style="height:{{ max(1, round($n / $max * 90)) }}px"></div><div>{{ $tgl }}</div></td>
  @endforeach
</tr></table>

<h2>Kunjungan per Poli</h2>
<table class="data">
  <tr><th>Poli</th><th class="angka">Total</th><th class="angka">Online</th><th class="angka">Loket</th></tr>
  @forelse ($lap['per_poli'] as $x)
    <tr><td>{{ $x['poli'] }}</td><td class="angka">{{ $x['total'] }}</td><td class="angka">{{ $x['online'] }}</td><td class="angka">{{ $x['loket'] }}</td></tr>
  @empty <tr><td colspan="4" class="kosong">Tidak ada data</td></tr>
  @endforelse
  @if ($lap['per_poli'])
    <tr class="total"><td>Total</td><td class="angka">{{ collect($lap['per_poli'])->sum('total') }}</td><td class="angka">{{ collect($lap['per_poli'])->sum('online') }}</td><td class="angka">{{ collect($lap['per_poli'])->sum('loket') }}</td></tr>
  @endif
</table>

<h2>Layanan per Dokter</h2>
<table class="data">
  <tr><th>Dokter</th><th>Spesialisasi</th><th class="angka">Pemeriksaan</th><th class="angka">Konsultasi Online</th></tr>
  @forelse ($lap['per_dokter'] as $x)
    <tr><td>{{ $x['dokter'] }}</td><td>{{ $x['spesialisasi'] }}</td><td class="angka">{{ $x['pemeriksaan'] }}</td><td class="angka">{{ $x['online'] }}</td></tr>
  @empty <tr><td colspan="4" class="kosong">Tidak ada data</td></tr>
  @endforelse
</table>

<h2>Obat Keluar</h2>
<table class="data">
  <tr><th>Obat</th><th>Satuan</th><th class="angka">Jumlah Keluar</th><th class="angka">Sisa Stok</th></tr>
  @forelse ($lap['obat_keluar'] as $x)
    <tr><td>{{ $x['obat'] }}</td><td>{{ $x['satuan'] }}</td><td class="angka">{{ $x['jumlah'] }}</td><td class="angka">{{ $x['sisa_stok'] }}</td></tr>
  @empty <tr><td colspan="4" class="kosong">Tidak ada data</td></tr>
  @endforelse
  @if ($lap['obat_keluar'])
    <tr class="total"><td colspan="2">Total</td><td class="angka">{{ collect($lap['obat_keluar'])->sum('jumlah') }}</td><td></td></tr>
  @endif
</table>

<table class="ttd"><tr>
  <td></td>
  <td>
    {{ $p['kota'] ?? '' }}, {{ now()->locale('id')->translatedFormat('d F Y') }}<br>
    Kepala {{ $p['nama_puskesmas'] }}
    <div class="garis">{{ $p['kepala_puskesmas'] ?: '(................................)' }}</div>
    NIP. {{ $p['nip_kepala'] ?: '..............................' }}
  </td>
</tr></table>
</body>
</html>
