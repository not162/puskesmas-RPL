@extends('layouts.app')
@section('title', 'Log Login')
@section('content')
<div class="page-h"><div><h2>Log login</h2><p>Setiap percobaan login (berhasil/gagal) tercatat.</p></div>
  <div class="tabs" style="margin:0">@foreach (['' => 'Semua', 'berhasil' => 'Berhasil', 'gagal' => 'Gagal'] as $s => $l)<a class="{{ request('hasil', '') === $s ? 'on' : '' }}" href="?hasil={{ $s }}">{{ $l }}</a>@endforeach</div></div>
<div class="table-wrap"><table>
  <tr><th>Waktu</th><th>Email</th><th>Peran</th><th>IP</th><th>Hasil</th></tr>
  @forelse ($log as $l)
    <tr><td class="nowrap">{{ $l->waktu->format('d/m/Y H:i:s') }}</td><td>{{ $l->akun?->email ?? $l->email_dicoba }}</td><td>{{ $l->akun?->peran->label ?? '-' }}</td><td class="mono">{{ $l->ip }}</td><td>@include('partials.status', ['s' => $l->hasil])</td></tr>
  @empty <tr><td colspan="5" class="empty">Belum ada log.</td></tr>
  @endforelse
</table></div>
{{ $log->links('partials.pager') }}
@endsection

