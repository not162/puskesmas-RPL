@extends('layouts.app')
@section('title', 'Detail Konsultasi')
@section('content')
<div class="grid g2">
  <div class="card"><div class="card-h"><h3>Keluhan Anda</h3>@include('partials.status', ['s' => $k->status])</div>
    <dl class="kv"><dt>Poli</dt><dd>{{ $k->spesialisasi->poli }}</dd><dt>Tanggal</dt><dd>{{ $k->tanggal->format('d/m/Y H:i') }}</dd><dt>Jenis</dt><dd>{{ ucfirst($k->jenis) }}</dd></dl>
    <p class="mt" style="white-space:pre-line">{{ $k->keluhan }}</p></div>
  <div class="card"><h3>Tanggapan dokter</h3>
    @if ($k->status === 'menunggu')
      <div class="empty"><x-ico n="clock"/> Menunggu tanggapan dokter. Anda akan menerima notifikasi.</div>
    @else
      <p class="muted">{{ $k->dokter?->nama }}</p>
      @if ($k->tanggapan)<p style="white-space:pre-line">{{ $k->tanggapan }}</p>@endif
      @if ($k->diagnosis)<div class="notif-preview"><b>Diagnosis{{ $k->jenis === 'online' ? ' sementara' : '' }}:</b> {{ $k->diagnosis }}</div>@endif
      @if ($k->resep)<div class="mt"><b>Resep:</b> @foreach ($k->resep->obat as $o){{ $o->nama_obat }} ({{ $o->pivot->jumlah }}), @endforeach</div>@endif
      @if ($k->jenis === 'online')<a class="btn btn-primary mt" href="{{ route('pasien.antrian.create', ['konsultasi' => $k->kode]) }}"><x-ico n="ticket"/> Ambil antrean untuk konsultasi ini</a>@endif
    @endif
  </div>
</div>
@endsection

