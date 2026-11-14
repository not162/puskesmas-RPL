@extends('layouts.app')
@section('title', 'Pemeriksaan')
@section('content')
<div class="page-h"><div><h2>Sedang diperiksa / dipanggil</h2><p>Lanjutkan pemeriksaan pasien yang sudah dipanggil.</p></div><a class="btn" href="{{ route('dokter.antrian') }}">Antrean poli</a></div>
<div id="live-periksa" data-live="antrian">
  <div class="grid g3">
    @forelse ($antrian as $a)
      <div class="card"><div class="card-h"><b style="font-size:22px">{{ $a->nomor }}</b>@include('partials.status', ['s' => $a->status])</div>
        <p class="mb0">{{ $a->pasien->nama }}</p><small>{{ $a->pasien->no_rm }} · {{ $a->pasien->tgl_lahir?->age ?? '-' }} th</small>
        <a class="btn btn-primary btn-block mt" href="{{ route('dokter.periksa', $a) }}"><x-ico n="steth"/> {{ $a->status === 'dilayani' ? 'Lanjutkan' : 'Mulai' }} pemeriksaan</a></div>
    @empty <div class="card empty" style="grid-column:1/-1">Belum ada pasien dipanggil. Panggil dari menu Antrean Poli.</div>
    @endforelse
  </div>
</div>
@endsection

