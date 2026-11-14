@extends('layouts.public')
@section('title', 'Lupa Password')
@section('content')
<div class="auth-main" style="min-height:calc(100vh - 140px)">
  <div class="card auth-card">
    <h1 style="font-size:24px;font-weight:800">Lupa password</h1>
    <p class="muted">Masukkan username atau email. Kami kirim <b>kode OTP 6 digit</b> ke email akun (berlaku 10 menit) untuk membuat password baru.</p>
    @if (session('terkirim'))
      <div class="alert alert-ok"><x-ico n="mail"/><div>Periksa kotak masuk email Anda. Akun tanpa email: datang ke loket membawa KTP, atau petugas hubungi Super Admin.</div></div>
    @endif
    <form method="POST" action="{{ route('password.email') }}">@csrf
      @include('partials.field', ['name' => 'login', 'label' => 'Username atau email', 'attrs' => 'required autofocus autocapitalize="none"'])
      @include('partials.captcha')
      <button class="btn btn-primary btn-block">Kirim kode OTP</button>
    </form>
    <p class="mt mb0" style="text-align:center"><a href="{{ route('login') }}">← Kembali ke halaman masuk</a></p>
  </div>
</div>
@endsection

