@extends('layouts.app')
@section('title', 'Pengaturan')
@section('content')
<div class="grid g2">
  <form class="card" method="POST" action="{{ route('admin.pengaturan') }}">@csrf @method('PUT')
    <h3>Identitas & kontak Puskesmas</h3>
    @include('partials.field', ['name' => 'nama_puskesmas', 'label' => 'Nama Puskesmas', 'value' => $nilai['nama_puskesmas'], 'attrs' => 'required'])
    @include('partials.field', ['name' => 'alamat', 'label' => 'Alamat', 'value' => $nilai['alamat'], 'attrs' => 'required'])
    <div class="grid g2">
      @include('partials.field', ['name' => 'telepon', 'label' => 'Telepon', 'value' => $nilai['telepon']])
      @include('partials.field', ['name' => 'whatsapp', 'label' => 'WhatsApp (62…)', 'value' => $nilai['whatsapp']])
      @include('partials.field', ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'value' => $nilai['email']])
      @include('partials.field', ['name' => 'maks_hari_antrean', 'label' => 'Antrean online maks. (hari ke depan)', 'type' => 'number', 'value' => $nilai['maks_hari_antrean']])
    </div>
    <div class="grid g2">
      @include('partials.field', ['name' => 'kota', 'label' => 'Kota/Kabupaten (kop & tanda tangan)', 'value' => $nilai['kota'], 'attrs' => 'required'])
      @include('partials.field', ['name' => 'kepala_puskesmas', 'label' => 'Nama Kepala Puskesmas', 'value' => $nilai['kepala_puskesmas']])
      @include('partials.field', ['name' => 'nip_kepala', 'label' => 'NIP Kepala Puskesmas', 'value' => $nilai['nip_kepala']])
    </div>
    @include('partials.field', ['name' => 'jam_layanan', 'label' => 'Jam layanan', 'value' => $nilai['jam_layanan']])
    @include('partials.field', ['name' => 'maps_url', 'label' => 'Tautan Google Maps', 'type' => 'url', 'value' => $nilai['maps_url']])
    <h3 class="mt">Keamanan login</h3>
    <div class="grid g2">
      @include('partials.field', ['name' => 'maks_gagal_login', 'label' => 'Kunci akun setelah gagal (kali)', 'type' => 'number', 'value' => $nilai['maks_gagal_login'], 'attrs' => 'min="3" max="20" required'])
      @include('partials.field', ['name' => 'menit_kunci', 'label' => 'Lama terkunci (menit)', 'type' => 'number', 'value' => $nilai['menit_kunci'], 'attrs' => 'min="1" max="1440" required'])
    </div>
    <div class="field"><label>OTP email saat login dari perangkat baru — petugas</label>
      <div class="hint" style="margin-bottom:6px">Pasien selalu wajib OTP. Centang peran petugas yang juga wajib (akun harus punya email asli).</div>
      @php $otpPetugas = explode(',', $nilai['otp_petugas']); @endphp
      <div class="flex">@foreach (['super_admin' => 'Super Admin', 'admin' => 'Admin', 'dokter_umum' => 'Dokter Umum', 'dokter_kandungan' => 'Dokter Kandungan', 'apoteker' => 'Apoteker', 'loket' => 'Loket'] as $k => $l)<label class="check"><input type="checkbox" name="otp_petugas[]" value="{{ $k }}" @checked(in_array($k, $otpPetugas))> {{ $l }}</label>@endforeach</div></div>
    <h3 class="mt">Pencegahan pendaftaran ganda</h3>
    <p class="muted">Username, email, dan NIK selalu ditolak bila sudah terdaftar.</p>
    <label class="check field"><input type="checkbox" name="tolak_nama_ganda" value="1" @checked($nilai['tolak_nama_ganda'] !== '0')> Tolak nama lengkap yang sama</label>
    <label class="check field"><input type="checkbox" name="tolak_kk_ganda" value="1" @checked($nilai['tolak_kk_ganda'] !== '0')> Tolak Nomor KK yang sama (matikan bila satu keluarga mendaftar sendiri-sendiri)</label>
    <button class="btn btn-primary">Simpan pengaturan</button>
  </form>
  <div>
    <div class="card"><h3>Poli & spesialisasi</h3><p class="muted">Dokter hanya Dokter Umum dan Dokter Kandungan. Nama poli dapat diubah.</p>
      @foreach ($spesialisasi as $s)
        <form method="POST" action="{{ route('admin.spesialisasi.update', $s) }}" class="flex" style="margin-bottom:10px">@csrf @method('PUT')
          <span class="badge" style="min-width:96px">{{ $s->nama }} · {{ $s->prefiks() }}-</span>
          <input name="poli" class="input" style="flex:1" value="{{ $s->poli }}"><button class="btn btn-sm">Simpan</button></form>
      @endforeach</div>
    <div class="card"><h3>Keamanan</h3>
      <ul class="list-plain">
        <li><span>Tanda tangan QR tiket</span><span class="badge b-ok">HMAC-SHA256</span></li>
        <li><span>API kiosk (X-Signature, anti-replay {{ config('hmac.jendela') }} dtk)</span><span class="badge {{ config('hmac.kiosk') ? 'b-ok' : 'b-bad' }}">{{ config('hmac.kiosk') ? count(config('hmac.kiosk')).' kiosk terdaftar' : 'belum diatur' }}</span></li>
        <li><span>Verifikasi anti-bot</span><span class="badge b-ok">{{ config('services.turnstile.site_key') ? 'Cloudflare Turnstile (cadangan: soal hitung)' : 'Soal hitung (Turnstile belum diatur)' }}</span></li>
        <li><span>OTP email (daftar, lupa password, perangkat baru)</span><span class="badge {{ config('mail.default') === 'smtp' ? 'b-ok' : 'b-warn' }}">{{ config('mail.default') === 'smtp' ? 'SMTP '.config('mail.mailers.smtp.host') : 'Mode log (SMTP belum diatur)' }}</span></li>
      </ul></div>
  </div>
</div>
@endsection
