@extends('layouts.public')
@section('title', 'Lupa Password')
@section('content')
<div class="auth-main" style="min-height:calc(100vh - 140px)">
  <div class="card auth-card">
    <h1 style="font-size:24px;font-weight:800">Lupa password</h1>
    <p class="muted">Masukkan username atau email. Kami kirim tautan reset ke email akun (berlaku 60 menit).</p>
    @if (session('terkirim'))
      <div class="alert alert-ok"><x-ico n="mail"/><div>Periksa kotak masuk email Anda. Akun tanpa email: datang ke loket membawa KTP, atau petugas hubungi Super Admin.</div></div>
    @endif
    <form method="POST" action="{{ route('password.email') }}">@csrf
      @include('partials.field', ['name' => 'login', 'label' => 'Username atau email', 'attrs' => 'required autofocus autocapitalize="none"'])
      <button class="btn btn-primary btn-block">Kirim tautan reset</button>
    </form>
    <p class="mt mb0" style="text-align:center"><a href="{{ route('login') }}">← Kembali ke halaman masuk</a></p>
  </div>
</div>
@endsection

