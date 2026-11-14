@extends('layouts.base')
@section('body-class', 'public')
@section('body')
@php
  $menuPublik = [['beranda', 'Beranda', null], ['beranda', 'Layanan', '#layanan'], ['layar', 'Antrean', null], ['publik.dokter', 'Jadwal', null], ['beranda', 'Bantuan', '#bantuan']];
@endphp
<header class="topnav">
  <div class="container">
    <a href="{{ route('beranda') }}" class="brand"><span class="brand-mark"><x-ico n="cross"/></span> {{ $namaPkm }}</a>
    <nav class="pills" aria-label="Navigasi utama">
      @foreach ($menuPublik as [$rt, $label, $hash])
        <a href="{{ route($rt).($hash ?? '') }}" class="{{ !$hash && request()->routeIs($rt) ? 'active' : '' }}">{{ $label }}</a>
      @endforeach
    </nav>
    <div class="nav-actions">
      @auth
        <a href="{{ route('dashboard') }}" class="btn btn-primary btn-sm"><x-ico n="user"/> Dasbor</a>
      @else
        <a href="{{ route('login') }}" class="btn btn-primary btn-sm"><x-ico n="user"/> Masuk</a>
      @endauth
      <button class="btn btn-icon btn-ghost" data-toggle-theme aria-label="Ganti tema gelap/terang"><span data-theme-icon></span></button>
      <button class="btn btn-icon btn-ghost burger" data-burger aria-label="Menu"><x-ico n="menu"/></button>
    </div>
  </div>
</header>
<main>@yield('content')</main>
<footer class="footer">
  <div class="container">
    <nav class="footnav" aria-label="Navigasi bawah">
      @foreach ($menuPublik as [$rt, $label, $hash])<a href="{{ route($rt).($hash ?? '') }}">{{ $label }}</a>@endforeach
    </nav>
    <p class="mb0">© {{ date('Y') }} {{ $namaPkm }} · {{ $pengaturan['alamat'] }} · <a href="{{ route('privasi') }}">Kebijakan Privasi</a> · <a href="{{ route('swagger') }}">API</a></p>
  </div>
</footer>
@endsection
