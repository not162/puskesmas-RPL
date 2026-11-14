@extends('layouts.app')
@section('title', 'Dasbor Dokter')
@section('content')
<div id="live-dash" data-live="antrian,konsultasi,resep">
<div class="page-h"><div><h2>{{ $dokter->nama }}</h2><p>{{ $dokter->spesialisasi->nama === 'Kandungan' ? 'Dokter Kandungan' : 'Dokter Umum' }} · {{ $dokter->spesialisasi->poli }} · {{ $dokter->jadwal }}</p></div>
  <a href="{{ route('dokter.antrian') }}" class="btn btn-primary"><x-ico n="steth"/> Mulai praktik</a></div>
<div class="grid g4">
  <div class="card stat"><span class="ic c-blue"><x-ico n="ticket"/></span><div><b>{{ $antrianHariIni }}</b><span>Antrean poli hari ini</span></div></div>
  <div class="card stat"><span class="ic c-green"><x-ico n="user"/></span><div><b>{{ $siapPeriksa }}</b><span>Siap diperiksa</span></div></div>
  <div class="card stat"><span class="ic c-amber"><x-ico n="chat"/></span><div><b>{{ $konsultasiMenunggu }}</b><span>Konsultasi online menunggu</span></div></div>
  <div class="card stat"><span class="ic c-teal"><x-ico n="pill"/></span><div><b>{{ $resepBulanIni }}</b><span>Resep bulan ini</span></div></div>
</div>
<div class="alert alert-info mt"><x-ico n="shield"/><div>Sebagai dokter, Anda hanya dapat melihat pasien di <b>{{ $dokter->spesialisasi->poli }}</b>, menanggapi konsultasi, dan menerbitkan resep. Pengelolaan obat, akun, dan laporan dilakukan peran lain.</div></div>
</div>
@endsection

