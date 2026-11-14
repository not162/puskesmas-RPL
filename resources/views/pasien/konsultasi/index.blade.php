@extends('layouts.app')
@section('title', 'Riwayat Konsultasi')
@section('content')
<div class="page-h"><div><h2>Konsultasi saya</h2><p>Online & pemeriksaan langsung.</p></div><a href="{{ route('pasien.konsultasi.create') }}" class="btn btn-primary"><x-ico n="plus"/> Konsultasi baru</a></div>
<div id="live-pasien-konsultasi-index" data-live="konsultasi" class="table-wrap"><table>
  <tr><th>Tanggal</th><th>Poli</th><th>Jenis</th><th>Dokter</th><th>Status</th><th></th></tr>
  @forelse ($konsultasi as $k)
    <tr><td>{{ $k->tanggal->format('d/m/Y H:i') }}</td><td>{{ $k->spesialisasi->poli }}</td><td>{{ ucfirst($k->jenis) }}</td><td>{{ $k->dokter?->nama ?? '-' }}</td><td>@include('partials.status', ['s' => $k->status])</td>
      <td><a class="btn btn-sm" href="{{ route('pasien.konsultasi.show', $k) }}">Buka</a></td></tr>
  @empty <tr><td colspan="6" class="empty">Belum ada konsultasi.</td></tr>
  @endforelse
</table></div>
{{ $konsultasi->links('partials.pager') }}
@endsection

