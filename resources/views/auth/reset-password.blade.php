@extends('layouts.public')
@section('title', 'Atur Ulang Password')
@section('content')
<div class="auth-main" style="min-height:calc(100vh - 140px)">
  <form class="card auth-card" method="POST" action="{{ route('password.update') }}">@csrf
    <h1 style="font-size:24px;font-weight:800">Password baru</h1>
    <input type="hidden" name="token" value="{{ $token }}">
    @include('partials.field', ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'value' => $email, 'attrs' => 'required readonly'])
    @include('partials.password', ['name' => 'password', 'label' => 'Password baru', 'hint' => 'Minimal 8 karakter.'])
    @include('partials.password', ['name' => 'password_confirmation', 'label' => 'Ulangi password baru'])
    <button class="btn btn-primary btn-block">Simpan password</button>
  </form>
</div>
@endsection

