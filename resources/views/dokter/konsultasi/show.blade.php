@extends('layouts.app')
@section('title', 'Tanggapi Konsultasi')
@section('content')
<div class="grid g2">
  <div class="card"><div class="card-h"><h3>{{ $k->pasien->nama }}</h3>@include('partials.status', ['s' => $k->status])</div>
    <dl class="kv"><dt>Usia / JK</dt><dd>{{ $k->pasien->tgl_lahir?->age ?? '-' }} th / {{ $k->pasien->jenis_kelamin }}</dd><dt>Dikirim</dt><dd>{{ $k->tanggal->format('d/m/Y H:i') }}</dd></dl>
    <p class="mt" style="white-space:pre-line">{{ $k->keluhan }}</p>
    <h3 class="mt">Riwayat</h3><ul class="list-plain">@forelse ($riwayat as $r)<li><span>{{ $r->tanggal->format('d/m/Y') }} · {{ ucfirst($r->jenis) }}</span><small>{{ \Illuminate\Support\Str::limit($r->diagnosis ?? $r->keluhan, 50) }}</small></li>@empty<li class="muted">-</li>@endforelse</ul>
  </div>
  <form class="card" method="POST" action="{{ route('dokter.konsultasi.tanggapi', $k) }}">@csrf
    <h3>Tanggapan</h3>
    @include('partials.field', ['name' => 'tanggapan', 'label' => 'Saran untuk pasien', 'type' => 'textarea', 'value' => $k->tanggapan, 'attrs' => 'required rows="6"', 'hint' => 'Contoh: perbanyak minum, kompres, segera ambil antrean Poli Umum besok pagi.'])
    @include('partials.field', ['name' => 'diagnosis', 'label' => 'Diagnosis sementara (opsional)', 'value' => $k->diagnosis])
    <button class="btn btn-primary btn-block">Kirim tanggapan & notifikasi pasien</button>
  </form>
</div>
@endsection

