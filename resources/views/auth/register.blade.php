@extends('layouts.public')
@section('title', 'Daftar Pasien')
@section('content')
<div class="auth-main">
  <div class="card auth-card wide">
    <h1 style="font-size:24px;font-weight:800">Daftar Pasien</h1>
    <p class="muted">Isi data sesuai KTP. NIK dan username hanya dapat terdaftar satu kali. Akun petugas dibuat oleh pihak Puskesmas.</p>
    <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data" novalidate>
      @csrf
      @include('partials.field', ['name' => 'nik', 'label' => 'NIK (16 digit)', 'attrs' => 'inputmode="numeric" maxlength="16" pattern="\d{16}" required'])
      @include('partials.field', ['name' => 'nama', 'label' => 'Nama lengkap (sesuai KTP)', 'attrs' => 'required autocomplete="name"'])
      <div class="grid g2">
        @include('partials.field', ['name' => 'tgl_lahir', 'label' => 'Tanggal lahir', 'type' => 'date', 'attrs' => 'required max="'.now()->subDay()->toDateString().'"'])
        <fieldset class="field" style="border:0;padding:0;margin:0 0 14px"><legend style="font-weight:600;font-size:13.5px;margin-bottom:10px">Jenis kelamin</legend>
          <label class="check" style="display:inline-flex;margin-right:18px"><input type="radio" name="jenis_kelamin" value="L" @checked(old('jenis_kelamin', 'L') === 'L')> Laki-laki</label>
          <label class="check" style="display:inline-flex"><input type="radio" name="jenis_kelamin" value="P" @checked(old('jenis_kelamin') === 'P')> Perempuan</label>
          @error('jenis_kelamin')<div class="err-msg">{{ $message }}</div>@enderror</fieldset>
      </div>
      @include('partials.field', ['name' => 'alamat', 'label' => 'Alamat', 'type' => 'textarea', 'attrs' => 'required rows="2"'])
      @include('partials.field', ['name' => 'username', 'label' => 'Username', 'hint' => '4–30 karakter: huruf kecil, angka, titik (.) dan garis bawah (_)', 'attrs' => 'required pattern="[a-z0-9._]{4,30}" autocomplete="username" autocapitalize="none" spellcheck="false"'])
      <div class="grid g2">
        @include('partials.field', ['name' => 'no_tlp', 'label' => 'No. HP / WhatsApp', 'type' => 'tel', 'attrs' => 'required placeholder="08xxxxxxxxxx"'])
        @include('partials.field', ['name' => 'email', 'label' => 'Email (opsional)', 'type' => 'email', 'hint' => 'Diperlukan untuk fitur lupa password lewat email', 'attrs' => 'autocomplete="email"'])
      </div>
      @include('partials.field', ['name' => 'no_bpjs', 'label' => 'No. BPJS (opsional)', 'attrs' => 'inputmode="numeric" maxlength="13" placeholder="13 digit"'])
      <div class="grid g2">
        @include('partials.password', ['name' => 'password', 'label' => 'Password', 'hint' => 'Minimal 8 karakter.'])
        @include('partials.password', ['name' => 'password_confirmation', 'label' => 'Ulangi password'])
      </div>
      <div class="field"><label for="foto_ktp">Foto/scan KTP (opsional)</label>
        <input id="foto_ktp" type="file" name="foto_ktp" accept=".jpg,.jpeg,.png,.pdf" class="input @error('foto_ktp') err @enderror">
        <div class="hint">JPG/PNG/PDF, maks 5 MB. Disimpan privat, tidak tampil di halaman publik.</div>
        @error('foto_ktp')<div class="err-msg">{{ $message }}</div>@enderror</div>
      <div class="field"><label class="check"><input type="checkbox" name="setuju" value="1" @checked(old('setuju'))> <span>Saya setuju data saya diproses sesuai <a href="{{ route('privasi') }}" target="_blank">Kebijakan Privasi</a> (wajib)</span></label>
        @error('setuju')<div class="err-msg">{{ $message }}</div>@enderror</div>
      <button class="btn btn-primary btn-block btn-lg" type="submit">Daftar sekarang</button>
    </form>
    <p class="muted mt mb0" style="text-align:center">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a> · <a href="{{ route('password.request') }}">Lupa password?</a></p>
  </div>
</div>
@endsection

