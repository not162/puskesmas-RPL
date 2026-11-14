@extends('layouts.app')
@section('title', 'Konsultasi Online')
@section('content')
<div class="page-h"><div><h2>Konsultasi online {{ $dokter->spesialisasi->poli }}</h2><p>Tanggapi keluhan pasien sebelum mereka datang.</p></div>
  <div class="tabs" style="margin:0">@foreach (['menunggu' => 'Menunggu', 'ditanggapi' => 'Ditanggapi', 'semua' => 'Semua'] as $s => $l)<a class="{{ request('status', 'menunggu') === $s ? 'on' : '' }}" href="?status={{ $s }}">{{ $l }}</a>@endforeach</div></div>
<div id="live-dokter-konsultasi-index" data-live="konsultasi" class="table-wrap"><table>
  <tr><th>Waktu</th><th>Pasien</th><th>Keluhan</th><th>Status</th><th></th></tr>
  @forelse ($konsultasi as $k)
    <tr><td class="nowrap">{{ $k->tanggal->format('d/m H:i') }}</td><td>{{ $k->pasien->nama }}</td><td>{{ \Illuminate\Support\Str::limit($k->keluhan, 70) }}</td><td>@include('partials.status', ['s' => $k->status])</td>
      <td><a class="btn btn-sm btn-primary" href="{{ route('dokter.konsultasi.show', $k) }}">{{ $k->status === 'menunggu' ? 'Tanggapi' : 'Lihat' }}</a></td></tr>
  @empty <tr><td colspan="5" class="empty">Tidak ada konsultasi.</td></tr>
  @endforelse
</table></div>
{{ $konsultasi->links('partials.pager') }}
@endsection

