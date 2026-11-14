@extends('layouts.app')
@section('title', 'Ambil Antrean Online')
@section('content')
<div class="grid g2">
  <form class="card" method="POST" action="{{ route('pasien.antrian.store') }}">@csrf
    <h3>Ambil antrean online</h3>
    <p class="muted">Langkah 5 · Pilih poli dan tanggal kunjungan. Maksimal {{ $pengaturan['maks_hari_antrean'] }} hari ke depan, 1 antrean aktif per poli per hari.</p>
    @if ($konsultasi)<input type="hidden" name="konsultasi" value="{{ $konsultasi->kode }}">
      <div class="alert alert-ok"><x-ico n="chat"/><div>Antrean ini ditautkan dengan konsultasi online Anda tanggal {{ $konsultasi->tanggal->format('d/m/Y') }}.</div></div>@endif
    <div class="field"><label for="id_spesialisasi">Poli tujuan</label>
      <select class="input @error('id_spesialisasi') err @enderror" name="id_spesialisasi" id="id_spesialisasi" required>
        <option value="">— Pilih poli —</option>
        @foreach ($spesialisasi as $s)<option value="{{ $s->id_spesialisasi }}" @selected(old('id_spesialisasi', $konsultasi?->id_spesialisasi) == $s->id_spesialisasi)>{{ $s->poli }} ({{ $s->nama === 'Kandungan' ? 'Dokter Kandungan' : 'Dokter Umum' }})</option>@endforeach
      </select>@error('id_spesialisasi')<div class="err-msg">{{ $message }}</div>@enderror</div>
    @include('partials.field', ['name' => 'tanggal', 'label' => 'Tanggal kunjungan', 'type' => 'date', 'value' => today()->toDateString(), 'attrs' => 'required min="'.today()->toDateString().'" max="'.today()->addDays((int) $pengaturan['maks_hari_antrean'])->toDateString().'"'])
    @include('partials.field', ['name' => 'keluhan_awal', 'label' => 'Keluhan singkat (opsional)', 'type' => 'textarea', 'value' => $konsultasi?->keluhan, 'attrs' => 'rows="3" maxlength="500"'])
    <button class="btn btn-primary btn-block btn-lg"><x-ico n="ticket"/> Ambil nomor antrean</button>
  </form>
  <div class="card"><h3>Persyaratan saat datang</h3>
    <ol class="steps-v">
      <li><span class="n">6</span><div><b>Check-in di loket</b><p class="muted mb0">{{ config('alur.langkah.6.syarat') }}</p></div></li>
      <li><span class="n">7</span><div><b>Tunggu panggilan</b><p class="muted mb0">{{ config('alur.langkah.7.syarat') }}</p></div></li>
    </ol>
    <div class="notif-preview mt"><x-ico n="bell"/> Notifikasi dikirim saat nomor diambil, check-in, dipanggil, hingga obat diserahkan.</div>
  </div>
</div>
@endsection

