@extends('layouts.public')
@section('title', 'Jadwal Dokter')
@section('content')
<section class="section container">
  <span class="eyebrow">Dokter</span>
  <h1 class="section-title">Jadwal Praktik Dokter</h1>
  <p class="section-sub">Puskesmas melayani Poli Umum dan Poli KIA & Kandungan.</p>
  <div class="grid g3">
    @foreach ($dokter as $d)
      <div class="card"><span class="badge {{ $d['spesialisasi']==='Kandungan' ? 'b-warn' : 'b-info' }}">{{ $d['spesialisasi'] === 'Kandungan' ? 'Dokter Kandungan' : 'Dokter Umum' }}</span>
        <h3 class="mt">{{ $d['nama'] }}</h3><p class="muted mb0">{{ $d['poli'] }}</p><p class="mb0 mt"><x-ico n="clock"/> {{ $d['jadwal'] ?? '-' }}</p>
        <a class="btn btn-sm btn-primary mt" href="{{ auth()->check() ? route('pasien.antrian.create') : route('login') }}"><x-ico n="ticket"/> Ambil antrean</a></div>
    @endforeach
  </div>
</section>
@endsection

