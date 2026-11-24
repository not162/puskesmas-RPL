@extends('layouts.app')
@section('title', 'Notifikasi')
@section('content')
<div class="page-h"><div><h2>Semua notifikasi</h2><p>Riwayat pemberitahuan aktivitas dan layanan kesehatan Anda.</p></div>
  <form method="POST" action="{{ route('notifikasi.bacaSemua') }}">@csrf<button class="btn"><x-ico n="check"/> Tandai semua dibaca</button></form></div>
<div class="card" style="padding:0;overflow:hidden">
  @forelse ($notifikasi as $n)
    <a class="notif-item {{ $n->read_at ? '' : 'unread' }}" href="{{ route('notifikasi.baca', $n->id) }}">
      <span class="notif-dot {{ $n->data['tipe'] ?? '' }}"></span>
      <div style="flex:1"><div class="t">{{ $n->data['judul'] ?? 'Info' }}</div><div>{{ $n->data['pesan'] ?? '' }}</div><div class="w">{{ $n->created_at->diffForHumans() }} · {{ $n->created_at->format('d/m/Y H:i') }}</div></div>
      @if (!$n->read_at)<span class="badge b-info">Baru</span>@endif
    </a>
  @empty <div class="empty">Belum ada notifikasi.</div>
  @endforelse
</div>
{{ $notifikasi->links('partials.pager') }}
@endsection

