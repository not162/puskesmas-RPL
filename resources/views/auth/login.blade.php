@extends('layouts.public')
@section('title', 'Masuk')
@section('content')
<div class="auth-wrap">
  <aside class="auth-side">
    <a href="{{ route('beranda') }}" class="brand" style="margin-bottom:22px"><span class="brand-mark"><x-ico n="cross"/></span> {{ $namaPkm }}</a>
    <h2>Portal Masuk</h2>
    <p class="muted">Satu pintu untuk pasien dan seluruh petugas. Sistem mengarahkan Anda ke dasbor sesuai peran akun.</p>
    <ul>
      <li><x-ico n="check" class="ico ok"/> Autentikasi aman untuk data kesehatan</li>
      <li><x-ico n="check" class="ico ok"/> Responsif: desktop, tablet, ponsel</li>
      <li><x-ico n="check" class="ico ok"/> Dirancang aksesibel</li>
    </ul>
  </aside>
  <div class="auth-main">
    <div class="card auth-card">
      <h1 style="font-size:26px;font-weight:800">Masuk</h1>
      <p class="muted">Masuk untuk melanjutkan ke dasbor Anda.</p>
      <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf
        <div class="field"><label for="login">Email atau username</label>
          <div class="input-group"><x-ico n="user"/><input id="login" name="login" class="input @error('login') err @enderror" value="{{ old('login') }}" required autofocus autocomplete="username" autocapitalize="none" spellcheck="false"></div>
          @error('login')<div class="err-msg" role="alert">{{ $message }}</div>@enderror</div>
        @include('partials.password', ['name' => 'password', 'label' => 'Password', 'auto' => 'current-password'])
        <div class="flex between field"><label class="check"><input type="checkbox" name="ingat" value="1"> Ingat saya</label><a href="{{ route('password.request') }}">Lupa password?</a></div>
        @include('partials.captcha')
        <button class="btn btn-primary btn-block btn-lg" type="submit">Masuk</button>
      </form>
      <p class="muted mt mb0" style="text-align:center">Belum punya akun? <a href="{{ route('register') }}">Daftar pasien</a></p>
    </div>
  </div>
</div>
@endsection

