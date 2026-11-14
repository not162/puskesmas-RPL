@extends('layouts.app')
@section('title', 'Pendaftaran')
@section('content')
<div class="grid g2">
  <div class="card"><h3>Cari pasien lama</h3>
    <form class="filters" method="GET"><input class="input" name="q" value="{{ $q }}" placeholder="NIK / No. RM / No. BPJS / nama" autofocus style="flex:1"><button class="btn btn-primary"><x-ico n="search"/></button></form>
    @if ($q !== '')
      <ul class="list-plain mt">
        @forelse ($hasil as $p)
          <li><div><b>{{ $p->nama }}</b><br><small>{{ $p->no_rm }} · NIK {{ $p->nik }}{{ $p->no_bpjs ? ' · BPJS '.$p->no_bpjs : '' }}</small></div>
            <form method="POST" action="{{ route('loket.pasien.antrian', $p) }}" class="flex">@csrf
              <select name="id_spesialisasi" class="input" style="width:auto;padding:6px">@foreach ($spesialisasi as $s)<option value="{{ $s->id_spesialisasi }}">{{ $s->poli }}</option>@endforeach</select>
              <button class="btn btn-sm btn-primary">Daftarkan</button></form></li>
        @empty <li class="muted">Tidak ditemukan. <a href="{{ route('loket.pasien.create') }}">Daftarkan pasien baru →</a></li>
        @endforelse
      </ul>
    @endif
  </div>
  <div class="card"><h3>Didaftarkan di loket hari ini</h3>
    <div id="live-pendaftaran" data-live="antrian"><ul class="list-plain">
      @forelse ($hariIni as $a)<li><span><b>{{ $a->nomor }}</b> {{ $a->pasien->nama }}</span>@include('partials.status', ['s' => $a->status])</li>
      @empty <li class="muted">Belum ada.</li>@endforelse</ul></div>
  </div>
</div>
@endsection

