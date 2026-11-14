@extends('layouts.public')
@section('title', 'Informasi')
@section('content')
<section class="section container">
  <span class="eyebrow">Informasi</span>
  <h1 class="section-title">Pengumuman, Agenda & Edukasi</h1>
  <div class="grid g3 mt">
    @forelse ($pengumuman as $p)
      <article class="card"><span class="badge">{{ ucfirst($p->kategori) }}</span><h3 class="mt" style="font-size:17px">{{ $p->judul }}</h3>
        <p class="muted">{{ $p->isi }}</p><small>{{ $p->tanggal->format('d/m/Y') }}</small></article>
    @empty <div class="card empty">Belum ada informasi.</div>
    @endforelse
  </div>
  {{ $pengumuman->links('partials.pager') }}
</section>
@endsection

