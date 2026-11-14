@extends('layouts.app')
@section('title', 'Dasbor Pasien')
@section('content')
<div id="live-dash" data-live="antrian,konsultasi,resep">
<div class="page-h"><div><h2>Halo, {{ $pasien->nama }}</h2><p>No. RM <b>{{ $pasien->no_rm }}</b> · NIK {{ $pasien->nikSamaran() }}</p></div>
  <div class="flex"><a href="{{ route('pasien.konsultasi.create') }}" class="btn"><x-ico n="chat"/> Konsultasi online</a><a href="{{ route('pasien.antrian.create') }}" class="btn btn-primary"><x-ico n="ticket"/> Ambil antrean</a></div></div>

<div class="card">
  <div class="card-h"><h3>Progres layanan Anda</h3><span class="badge b-info">Langkah {{ $langkah }} dari {{ count($alur) }}</span></div>
  <div class="steps-h">
    @foreach ($alur as $no => $l)
      <div class="step-h {{ $no < $langkah ? 'done' : ($no === $langkah ? 'now' : '') }}"><div class="n">@if($no < $langkah)<x-ico n="check"/>@else{{ $no }}@endif</div>{{ $l['judul'] }}</div>
    @endforeach
  </div>
  @php $next = $alur[min($langkah + 1, count($alur))] ?? null; @endphp
  @if ($next && $langkah < count($alur))
    <div class="notif-preview"><b>Langkah berikutnya: {{ $next['judul'] }}.</b> <span class="muted">{{ $next['syarat'] }}</span></div>
  @endif
</div>

<div class="grid g2 mt">
  <div class="card">
    <div class="card-h"><h3>Antrean aktif</h3><a href="{{ route('pasien.antrian.index') }}">Semua</a></div>
    @forelse ($antrianAktif as $a)
      <a href="{{ route('pasien.antrian.show', $a) }}" class="intent" style="margin-bottom:10px"><span class="ic c-blue"><x-ico n="ticket"/></span>
        <div style="flex:1"><b style="font-size:20px">{{ $a->nomor }}</b><div class="muted" style="font-weight:500">{{ $a->spesialisasi->poli }} · {{ $a->tanggal->locale('id')->translatedFormat('l, d M Y') }}</div></div>
        @include('partials.status', ['s' => $a->status])</a>
    @empty
      <div class="empty">Belum ada antrean aktif.<br><a href="{{ route('pasien.antrian.create') }}" class="btn btn-sm btn-primary mt">Ambil antrean</a></div>
    @endforelse
  </div>
  <div class="card">
    <div class="card-h"><h3>Konsultasi terbaru</h3><a href="{{ route('pasien.konsultasi.index') }}">Semua</a></div>
    <ul class="list-plain">
      @forelse ($konsultasi as $k)
        <li><a href="{{ route('pasien.konsultasi.show', $k) }}">{{ $k->spesialisasi->poli }} · {{ $k->tanggal->format('d/m H:i') }}<br><small>{{ \Illuminate\Support\Str::limit($k->keluhan, 60) }}</small></a>@include('partials.status', ['s' => $k->status])</li>
      @empty <li class="empty" style="display:block">Belum ada konsultasi.</li>
      @endforelse
    </ul>
  </div>
</div>
<div class="card mt">
  <div class="card-h"><h3>Resep terbaru</h3><a href="{{ route('pasien.resep') }}">Semua</a></div>
  <ul class="list-plain">
    @forelse ($resep as $r)<li><span class="mono">{{ substr($r->kode, -8) }}</span><span>{{ $r->tgl_resep->format('d/m/Y H:i') }}</span>@include('partials.status', ['s' => $r->status])</li>
    @empty <li class="empty" style="display:block">Belum ada resep.</li>@endforelse
  </ul>
</div>
</div>
@endsection

