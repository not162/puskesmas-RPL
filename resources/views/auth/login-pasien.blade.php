@extends('layouts.public')
@section('title', 'Masuk Pasien')
@section('content')
<div class="auth-main" style="min-height:calc(100vh - 140px)">
  <div class="card auth-card" style="max-width:400px">
    <div class="brand" style="margin-bottom:6px"><span class="brand-mark"><x-ico n="cross"/></span> Masuk ke {{ $namaPkm }}</div>
    <p class="muted">Gunakan username atau email akun Anda.</p>
    <form method="POST" action="{{ route('login') }}" novalidate>@csrf
      <div class="field"><label for="login">Username atau email</label>
        <input id="login" name="login" class="input @error('login') err @enderror" value="{{ old('login') }}" required autofocus autocomplete="username" autocapitalize="none">
        @error('login')<div class="err-msg" role="alert">{{ $message }}</div>@enderror</div>
      @include('partials.password', ['name' => 'password', 'label' => 'Password', 'auto' => 'current-password'])
      <div class="flex between field"><label class="check"><input type="checkbox" name="ingat" value="1"> Ingat saya</label><a href="{{ route('password.request') }}">Lupa password?</a></div>
      @include('partials.captcha')
      <button class="btn btn-primary btn-block" type="submit">Masuk</button>
    </form>
    <p class="mt mb0">Belum punya akun? <a href="{{ route('register') }}">Daftar pasien</a></p>
    <p class="mb0">Butuh bantuan? <a href="https://wa.me/{{ $pengaturan['whatsapp'] }}" target="_blank" rel="noopener">Chat WhatsApp</a></p>
  </div>
</div>
@endsection

