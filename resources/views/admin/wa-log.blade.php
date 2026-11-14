@extends('layouts.app')
@section('title', 'Log WhatsApp')
@section('content')
<div class="page-h"><div><h2>Notifikasi WhatsApp</h2>
  <p>Driver: <b>{{ config('whatsapp.driver') }}</b>{{ config('whatsapp.driver') === 'log' ? ' — pesan hanya dicatat, belum terkirim' : '' }} · hari ini:
    @forelse ($ringkas as $s => $n)<span class="badge {{ ['terkirim'=>'b-ok','log'=>'b-info','gagal'=>'b-bad','dibatasi'=>'b-warn'][$s] ?? '' }}">{{ $s }} {{ $n }}</span> @empty 0 pesan @endforelse</p></div>
  <form class="filters" method="POST" action="{{ route('admin.wa.uji') }}">@csrf
    <input class="input" name="nomor" placeholder="08xxxxxxxxxx" required style="min-width:180px"><button class="btn btn-primary">Kirim uji</button></form></div>
<div class="tabs" style="margin-bottom:12px;max-width:520px">@foreach (['' => 'Semua', 'terkirim' => 'Terkirim', 'log' => 'Log', 'gagal' => 'Gagal', 'dibatasi' => 'Dibatasi'] as $s => $l)<a class="{{ request('status', '') === $s ? 'on' : '' }}" href="?status={{ $s }}">{{ $l }}</a>@endforeach</div>
<div class="table-wrap" id="live-wa" data-live="akun"><table>
  <tr><th>Waktu</th><th>Nomor</th><th>Akun</th><th>Jenis</th><th>Status</th><th>Pesan</th></tr>
  @forelse ($log as $l)
    <tr><td class="nowrap">{{ $l->created_at->format('d/m H:i:s') }}</td><td class="mono">{{ $l->nomorSamaran() }}</td><td>{{ $l->akun ? '@'.$l->akun->username : '-' }}</td>
      <td>{{ $l->jenis }}</td><td>@include('partials.status', ['s' => $l->status])</td><td style="max-width:360px">{{ \Illuminate\Support\Str::limit($l->ringkas, 90) }}</td></tr>
  @empty <tr><td colspan="6" class="empty">Belum ada pesan.</td></tr>
  @endforelse
</table></div>
{{ $log->links('partials.pager') }}
@endsection
