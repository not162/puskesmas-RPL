@extends('layouts.app')
@section('title', 'Ambil Antrean Online')
@section('content')
<div class="grid g2">
  <form class="card" method="POST" action="{{ route('pasien.antrian.store') }}">@csrf
    <h3>Ambil antrean online</h3>
    <p class="muted">Pilih poli dan tanggal kunjungan. Maksimal {{ $pengaturan['maks_hari_antrean'] }} hari ke depan, 1 antrean aktif per poli per hari.</p>
    @if ($konsultasi)<input type="hidden" name="konsultasi" value="{{ $konsultasi->kode }}">
      <div class="alert alert-ok"><x-ico n="chat"/><div>Antrean ini ditautkan dengan konsultasi online Anda tanggal {{ $konsultasi->tanggal->format('d/m/Y') }}.</div></div>@endif
    <div class="field"><label for="id_spesialisasi">Poli tujuan</label>
      <select class="input @error('id_spesialisasi') err @enderror" name="id_spesialisasi" id="id_spesialisasi" required>
        <option value="">Pilih poli</option>
        @foreach ($spesialisasi as $s)<option value="{{ $s->id_spesialisasi }}" @selected(old('id_spesialisasi', $konsultasi?->id_spesialisasi) == $s->id_spesialisasi)>{{ $s->poli }} ({{ $s->nama === 'Kandungan' ? 'Dokter Kandungan' : 'Dokter Umum' }})</option>@endforeach
      </select>@error('id_spesialisasi')<div class="err-msg">{{ $message }}</div>@enderror</div>
    @include('partials.field', ['name' => 'tanggal', 'label' => 'Tanggal kunjungan', 'type' => 'date', 'value' => today()->toDateString(), 'attrs' => 'required min="'.today()->toDateString().'" max="'.today()->addDays((int) $pengaturan['maks_hari_antrean'])->toDateString().'"'])
    @include('partials.field', ['name' => 'keluhan_awal', 'label' => 'Keluhan singkat (opsional)', 'type' => 'textarea', 'value' => $konsultasi?->keluhan, 'attrs' => 'rows="3" maxlength="500"'])
    <button class="btn btn-primary btn-block btn-lg"><x-ico n="ticket"/> Ambil nomor antrean</button>
  </form>
  <div class="card"><h3>Petunjuk saat datang</h3>
    <ul class="list-plain">
      <li style="display:flex;gap:12px;align-items:flex-start">
        <span class="ic c-blue" style="margin-top:2px"><x-ico n="qr"/></span>
        <div><b>Check-in di loket</b><p class="muted mb0">Tunjukkan QR tiket antrean atau sebutkan NIK kepada petugas saat tiba di Puskesmas.</p></div>
      </li>
      <li style="display:flex;gap:12px;align-items:flex-start;margin-top:14px">
        <span class="ic c-green" style="margin-top:2px"><x-ico n="bell"/></span>
        <div><b>Tunggu panggilan poli</b><p class="muted mb0">Pantau layar antrean dan panggilan periksa. Notifikasi juga dikirim langsung ke perangkat Anda.</p></div>
      </li>
    </ul>
    <div class="notif-preview mt"><x-ico n="bell"/> Notifikasi akan dikirim saat nomor diambil, check-in, dipanggil, hingga obat diserahkan.</div>
  </div>
</div>
@endsection

