@extends('layouts.app')
@section('title', 'Antrian '.$dokter->spesialisasi->poli)
@section('content')
<div class="page-h"><div><h2>Antrean {{ $dokter->spesialisasi->poli }} hari ini</h2><p>Pasien yang sudah check-in dapat dipanggil dan diperiksa.</p></div>
  <span class="live"><span class="dot"></span> Diperbarui otomatis bersama loket</span></div>
<div id="live-dokter-antrian" data-live="antrian" class="table-wrap"><table>
  <tr><th>No.</th><th>Pasien</th><th>Usia / JK</th><th>Keluhan awal</th><th>Status</th><th>Aksi</th></tr>
  @forelse ($antrian as $a)
    <tr><td><b>{{ $a->nomor }}</b></td><td>{{ $a->pasien->nama }}<br><small>{{ $a->pasien->no_rm }}</small></td>
      <td>{{ $a->pasien->tgl_lahir ? $a->pasien->tgl_lahir->age.' th' : '-' }} / {{ $a->pasien->jenis_kelamin ?? '-' }}</td>
      <td>{{ \Illuminate\Support\Str::limit($a->keluhan_awal, 50) ?: '-' }}</td><td>@include('partials.status', ['s' => $a->status])</td>
      <td><div class="actions">
        @if (in_array($a->status, ['hadir','dipanggil']))<form class="inline" method="POST" action="{{ route('dokter.panggil', $a) }}">@csrf<button class="btn btn-sm">Panggil</button></form>@endif
        @if (in_array($a->status, ['hadir','dipanggil','dilayani']))<a class="btn btn-sm btn-primary" href="{{ route('dokter.periksa', $a) }}"><x-ico n="steth"/> Periksa</a>@endif
        @if ($a->status === 'menunggu')<small class="muted">Belum check-in</small>@endif
      </div></td></tr>
  @empty <tr><td colspan="6" class="empty">Belum ada pasien.</td></tr>
  @endforelse
</table></div>
<div class="card mt"><h3>Selesai diperiksa</h3>
  <ul class="list-plain">@forelse ($selesai as $a)<li><span><b>{{ $a->nomor }}</b> {{ $a->pasien->nama }}</span><small>{{ $a->updated_at->format('H:i') }}</small></li>@empty<li class="muted">Belum ada.</li>@endforelse</ul></div>
@endsection


