@extends('layouts.app')
@section('title', 'Dasbor Loket')
@section('content')
<div id="live-dash" data-live="antrian,pasien">
<div class="page-h"><div><h2>Loket Pendaftaran</h2><p>{{ now()->locale('id')->translatedFormat('l, d F Y') }}</p></div>
  <div class="flex"><a href="{{ route('loket.pasien.create') }}" class="btn"><x-ico n="plus"/> Pasien baru</a><a href="{{ route('loket.antrian') }}" class="btn btn-primary"><x-ico n="ticket"/> Kelola antrean</a></div></div>
<div class="grid g4">
  @foreach (['menunggu' => ['Belum check-in','c-amber'], 'hadir' => ['Sudah hadir','c-blue'], 'dipanggil' => ['Dipanggil','c-violet'], 'selesai' => ['Selesai','c-green']] as $s => [$l, $c])
    <a class="card stat" href="{{ route('loket.antrian', ['status' => $s]) }}"><span class="ic {{ $c }}"><x-ico n="ticket"/></span><div><b>{{ $perStatus[$s] ?? 0 }}</b><span>{{ $l }}</span></div></a>
  @endforeach
</div>
<div class="card mt"><div class="card-h"><h3>Antrean terbaru</h3></div>
  <div class="table-wrap"><table><tr><th>No.</th><th>Pasien</th><th>Poli</th><th>Sumber</th><th>Status</th></tr>
    @forelse ($terbaru as $a)<tr><td><b>{{ $a->nomor }}</b></td><td>{{ $a->pasien->nama }}</td><td>{{ $a->spesialisasi->poli }}</td><td>{{ ucfirst($a->sumber) }}</td><td>@include('partials.status', ['s' => $a->status])</td></tr>
    @empty <tr><td colspan="5" class="empty">Belum ada antrean hari ini.</td></tr>@endforelse
  </table></div></div>
</div>
@endsection

