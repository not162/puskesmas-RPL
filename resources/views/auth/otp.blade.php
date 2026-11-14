@extends('layouts.public')
@section('title', 'Verifikasi Kode OTP')
@section('content')
<div class="auth-main" style="min-height:calc(100vh - 160px)">
  <div class="card auth-card">
    <div class="hero-icon" style="margin:0 0 14px;width:52px;height:52px"><x-ico n="mail"/></div>
    <h1 style="font-size:24px;font-weight:800">{{ $judul }}</h1>
    <p class="muted">Kode OTP 6 digit telah dikirim ke <b>{{ $email }}</b>. Periksa kotak masuk atau folder spam. Kode berlaku {{ \App\Support\Otp::BERLAKU_MENIT }} menit.</p>
    <form method="POST" action="{{ $aksi }}" novalidate>@csrf
      <div class="field"><label for="kode">Kode OTP</label>
        <input id="kode" name="kode" class="input otp-input @error('kode') err @enderror" inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="\d{6}" required autofocus placeholder="••••••">
        @error('kode')<div class="err-msg" role="alert">{{ $message }}</div>@enderror</div>
      @if (!empty($resetPassword))
        @include('partials.password', ['name' => 'password', 'label' => 'Password baru', 'hint' => 'Minimal 8 karakter.'])
        @include('partials.password', ['name' => 'password_confirmation', 'label' => 'Ulangi password baru'])
      @endif
      @if (!empty($ingatPerangkat))
        <label class="check field"><input type="checkbox" name="percaya" value="1" checked> Percayai perangkat ini selama 30 hari</label>
      @endif
      <button class="btn btn-primary btn-block btn-lg" type="submit">Verifikasi</button>
    </form>
    <form method="POST" action="{{ $aksiUlang }}" class="mt" style="text-align:center">@csrf
      <button class="btn btn-ghost btn-sm" id="ulang" type="submit" disabled>Kirim ulang kode (<span id="hitung">60</span>)</button>
    </form>
    <p class="mt mb0" style="text-align:center"><a href="{{ route('login') }}">← Kembali ke halaman masuk</a></p>
  </div>
</div>
@endsection
@push('scripts')
<script>
(() => { let s = {{ max(0, (int) ($tunggu ?? 60)) }}; const b = document.getElementById('ulang'), h = document.getElementById('hitung');
  const t = () => { if (s <= 0) { b.disabled = false; b.textContent = 'Kirim ulang kode'; return; } h.textContent = s--; setTimeout(t, 1000); }; t();
  document.getElementById('kode').addEventListener('input', e => { e.target.value = e.target.value.replace(/\D/g, '').slice(0, 6); if (e.target.value.length === 6 && !document.getElementById('password')) e.target.form.requestSubmit(); });
})();
</script>
@endpush
