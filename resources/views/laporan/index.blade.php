@extends('layouts.app')
@section('title', 'Laporan Bulanan')
@section('content')
<div class="page-h"><div><h2>Laporan {{ $lap['label'] }}</h2><p>Rekap kunjungan, layanan dokter, dan obat keluar.</p></div>
  <form class="filters" method="GET"><input type="month" name="bulan" value="{{ $lap['periode'] }}" class="input"><button class="btn">Tampilkan</button>
    <a class="btn btn-primary" href="{{ route('laporan.export', ['bulan' => $lap['periode']]) }}"><x-ico n="download"/> Export Excel (CSV)</a>
    <a class="btn" target="_blank" href="{{ route('laporan.cetak', ['bulan' => $lap['periode']]) }}"><x-ico n="printer"/> Cetak / PDF</a></form></div>
<div id="live-laporan" data-live="antrian,resep,konsultasi">@include('laporan._isi')</div>
@endsection

