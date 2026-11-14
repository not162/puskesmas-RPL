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
    @include('partials.field', ['name' => 'jam_layanan', 'label' => 'Jam layanan', 'value' => $nilai['jam_layanan']])
    @include('partials.field', ['name' => 'maps_url', 'label' => 'Tautan Google Maps', 'type' => 'url', 'value' => $nilai['maps_url']])
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
        <li><span>Verifikasi login</span><span class="badge b-ok">Soal hitung + rate limit</span></li>
      </ul></div>
  </div>
</div>
@endsection
