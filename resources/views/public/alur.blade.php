@extends('layouts.public')
@section('title', 'Alur Layanan')
@section('content')
<section class="section container" style="max-width:860px">
  <span class="eyebrow">Alur Layanan</span>
  <h1 class="section-title">Persyaratan & notifikasi langkah demi langkah</h1>
  <p class="section-sub">Setiap langkah memiliki persyaratan. Begitu langkah selesai, Anda menerima notifikasi di aplikasi.</p>
  <ol class="steps-v">
    @foreach ($langkah as $no => $l)
      <li><span class="n">{{ $no }}</span><div><h3 style="font-size:17px">{{ $l['judul'] }}</h3>
        <p class="mb0"><b>Persyaratan:</b> <span class="muted">{{ $l['syarat'] }}</span></p>
        <div class="notif-preview"><x-ico n="bell"/> <b>Notifikasi:</b> {{ preg_replace('/:(\w+)/', '[$1]', $l['notif']) }}</div></div></li>
    @endforeach
  </ol>
  <p style="text-align:center" class="mt"><a href="{{ route('register') }}" class="btn btn-primary btn-lg">Mulai daftar sekarang</a></p>
</section>
@endsection

