@extends('layouts.app')
@section('title', 'Laporan Bulanan')
@section('content')
<div class="page-h"><div><h2>Laporan {{ $lap['label'] }}</h2><p>Rekap kunjungan, layanan dokter, dan obat keluar.</p></div>
  <form class="filters" method="GET"><input type="month" name="bulan" value="{{ $lap['periode'] }}" class="input"><button class="btn">Tampilkan</button>
    <a class="btn btn-primary" href="{{ route('laporan.xlsx', ['bulan' => $lap['periode']]) }}"><x-ico n="download"/> Excel (.xlsx)</a>
    <a class="btn btn-primary" href="{{ route('laporan.pdf', ['bulan' => $lap['periode']]) }}"><x-ico n="download"/> PDF</a>
    <a class="btn" target="_blank" href="{{ route('laporan.pdf', ['bulan' => $lap['periode'], 'unduh' => 0]) }}"><x-ico n="eye"/> Pratinjau PDF</a>
    <a class="btn" href="{{ route('laporan.export', ['bulan' => $lap['periode']]) }}">CSV</a>
    <a class="btn" target="_blank" href="{{ route('laporan.cetak', ['bulan' => $lap['periode']]) }}"><x-ico n="printer"/> Cetak</a></form></div>
<div id="live-laporan" data-live="antrian,resep,konsultasi">@include('laporan._isi')</div>
@endsection

