@extends('layouts.public')
@section('title', 'Daftar Pasien')
@section('content')
<div class="auth-main">
  <div class="card auth-card wide">
    <h1 style="font-size:24px;font-weight:800">Daftar Pasien</h1>
    <p class="muted">Isi data sesuai KTP. Satu NIK, Nomor KK, nama, email, dan username hanya bisa didaftarkan sekali. Jika data Anda sudah terdaftar, Anda akan diarahkan ke halaman masuk. Setelah formulir dikirim, kode OTP dikirim ke email Anda.</p>
    <div class="alert alert-warn" id="ganda-box" hidden><x-ico n="alert"/><div><b><span id="ganda-label"></span> sudah terdaftar.</b> Silakan <a href="{{ route('login') }}">masuk</a> atau <a href="{{ route('password.request') }}">gunakan Lupa password</a>.</div></div>
    <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data" novalidate>
      @csrf
      @include('partials.field', ['name' => 'nik', 'label' => 'NIK (16 digit)', 'attrs' => 'inputmode="numeric" maxlength="16" pattern="\d{16}" required data-cek="nik"'])
      @include('partials.field', ['name' => 'no_kk', 'label' => 'Nomor Kartu Keluarga (16 digit)', 'attrs' => 'inputmode="numeric" maxlength="16" pattern="\d{16}" required data-cek="no_kk"'])
      @include('partials.field', ['name' => 'nama', 'label' => 'Nama lengkap (sesuai KTP)', 'attrs' => 'required autocomplete="name" data-cek="nama"'])
      <div class="grid g2">
        @include('partials.field', ['name' => 'tgl_lahir', 'label' => 'Tanggal lahir', 'type' => 'date', 'attrs' => 'required max="'.now()->subDay()->toDateString().'"'])
        <fieldset class="field" style="border:0;padding:0;margin:0 0 14px"><legend style="font-weight:600;font-size:13.5px;margin-bottom:10px">Jenis kelamin</legend>
          <label class="check" style="display:inline-flex;margin-right:18px"><input type="radio" name="jenis_kelamin" value="L" @checked(old('jenis_kelamin', 'L') === 'L')> Laki-laki</label>
          <label class="check" style="display:inline-flex"><input type="radio" name="jenis_kelamin" value="P" @checked(old('jenis_kelamin') === 'P')> Perempuan</label>
          @error('jenis_kelamin')<div class="err-msg">{{ $message }}</div>@enderror</fieldset>
      </div>
      @include('partials.field', ['name' => 'alamat', 'label' => 'Alamat', 'type' => 'textarea', 'attrs' => 'required rows="2"'])
      @include('partials.field', ['name' => 'username', 'label' => 'Username', 'hint' => '4–30 karakter: huruf kecil, angka, titik (.) dan garis bawah (_)', 'attrs' => 'required pattern="[a-z0-9._]{4,30}" autocomplete="username" autocapitalize="none" spellcheck="false" data-cek="username"'])
      <div class="grid g2">
        @include('partials.field', ['name' => 'no_tlp', 'label' => 'No. HP / WhatsApp', 'type' => 'tel', 'attrs' => 'required placeholder="08xxxxxxxxxx"'])
        @include('partials.field', ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'hint' => 'Kode OTP dikirim ke email ini (contoh: nama@gmail.com)', 'attrs' => 'required autocomplete="email" data-cek="email"'])
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
      <div class="field"><label class="check"><input type="checkbox" name="wa_opt_in" value="1" @checked(old('wa_opt_in'))> <span>Saya bersedia menerima pemberitahuan antrean, panggilan, resep, dan kode OTP cadangan lewat <b>WhatsApp</b> (opsional, maks. {{ $pengaturan['wa_maks_harian'] }} pesan/hari)</span></label></div>
      <div class="field"><label class="check"><input type="checkbox" name="setuju" value="1" @checked(old('setuju'))> <span>Saya setuju data saya diproses sesuai <a href="{{ route('privasi') }}" target="_blank">Kebijakan Privasi</a> (wajib)</span></label>
        @error('setuju')<div class="err-msg">{{ $message }}</div>@enderror</div>
      @include('partials.captcha')
      <button class="btn btn-primary btn-block btn-lg" type="submit">Daftar & kirim kode OTP</button>
    </form>
    <p class="muted mt mb0" style="text-align:center">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a> · <a href="{{ route('password.request') }}">Lupa password?</a></p>
  </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
  const ganda = new Set(), box = document.getElementById('ganda-box'), lbl = document.getElementById('ganda-label');
  const render = () => { box.hidden = !ganda.size; lbl.textContent = [...ganda].join(', '); if (ganda.size) box.scrollIntoView({ block: 'nearest', behavior: 'smooth' }); };
  document.querySelectorAll('[data-cek]').forEach(el => el.addEventListener('change', async () => {
    const k = el.dataset.cek, v = el.value.trim();
    const d = v ? await fetch(@json(route('register.cek')) + '?kolom=' + k + '&nilai=' + encodeURIComponent(v), { headers: { Accept: 'application/json' } }).then(r => r.ok ? r.json() : null).catch(() => null) : null;
    if (d && d.terdaftar) { ganda.add(d.label); el.classList.add('err'); } else { ganda.delete(d ? d.label : ''); [...ganda].forEach(g => { if (g && el.closest('.field').querySelector('label').textContent.startsWith(g)) ganda.delete(g); }); el.classList.remove('err'); }
    render();
  }));
})();
</script>
@endpush
