@extends('layouts.app')
@section('title', 'Jadwal Saya')
@section('content')
<div class="card" id="live-jadwal" data-live="jadwal">
  <div class="card-h"><h3>{{ $dokter->nama }} · {{ $dokter->spesialisasi->poli }}</h3><span class="muted">Diatur oleh Admin / Super Admin</span></div>
  <div class="table-wrap"><table><tr><th>Hari</th><th>Jam</th><th>Kuota</th><th>Status</th></tr>
    @forelse ($dokter->jadwalPraktik as $j)
      <tr class="{{ $j->hari == now()->dayOfWeekIso ? 'live-flash' : '' }}"><td><b>{{ \App\Models\JadwalPraktik::HARI[$j->hari] }}</b>@if($j->hari == now()->dayOfWeekIso) <span class="badge b-info">Hari ini</span>@endif</td>
        <td>{{ substr($j->jam_mulai, 0, 5) }}–{{ substr($j->jam_selesai, 0, 5) }}</td><td>{{ $j->kuota }} pasien</td><td>{!! $j->aktif ? '<span class="badge b-ok">Aktif</span>' : '<span class="badge">Libur</span>' !!}</td></tr>
    @empty <tr><td colspan="4" class="empty">Belum ada jadwal.</td></tr>
    @endforelse
  </table></div>
</div>
@endsection

