@extends('layouts.app')
@section('title', 'Riwayat '.$p->nama)
@section('content')
<div class="card"><dl class="kv"><dt>No. RM</dt><dd>{{ $p->no_rm }}</dd><dt>Usia / JK</dt><dd>{{ $p->tgl_lahir?->age ?? '-' }} th / {{ $p->jenis_kelamin }}</dd><dt>BPJS</dt><dd>{{ $p->no_bpjs ?: '-' }}</dd></dl></div>
@foreach ($konsultasi as $k)
  <div class="card"><div class="card-h"><div><b>{{ $k->tanggal->format('d/m/Y H:i') }}</b> <span class="muted">· {{ ucfirst($k->jenis) }} · {{ $k->dokter?->nama }}</span></div>@include('partials.status', ['s' => $k->status])</div>
    <p class="mb0"><b>Keluhan:</b> {{ $k->keluhan }}</p>
    @if ($k->diagnosis)<p class="mb0"><b>Diagnosis:</b> {{ $k->diagnosis }}</p>@endif
    @if ($k->tanggapan)<p class="mb0"><b>Tanggapan:</b> {{ $k->tanggapan }}</p>@endif
    @if ($k->resep)<p class="mb0"><b>Resep:</b> {{ $k->resep->obat->map(fn ($o) => $o->nama_obat.' ×'.$o->pivot->jumlah.' ('.$o->pivot->aturan_pakai.')')->implode(', ') }}</p>@endif
  </div>
@endforeach
@endsection

