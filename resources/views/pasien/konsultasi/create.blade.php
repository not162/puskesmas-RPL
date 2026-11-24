@extends('layouts.app')
@section('title', 'Konsultasi Online')
@section('content')
<div class="grid g2">
  <form class="card" method="POST" action="{{ route('pasien.konsultasi.store') }}">@csrf
    <h3>Konsultasi sebelum datang</h3>
    <p class="muted">Tuliskan keluhan Anda. Dokter poli terkait akan menanggapi dan menyarankan apakah perlu datang.</p>
    <div class="field"><label for="id_spesialisasi">Poli</label>
      <select class="input @error('id_spesialisasi') err @enderror" id="id_spesialisasi" name="id_spesialisasi" required>
        <option value="">Pilih poli</option>
        @foreach ($spesialisasi as $s)<option value="{{ $s->id_spesialisasi }}" @selected(old('id_spesialisasi') == $s->id_spesialisasi)>{{ $s->poli }}</option>@endforeach
      </select>@error('id_spesialisasi')<div class="err-msg">{{ $message }}</div>@enderror</div>
    @include('partials.field', ['name' => 'keluhan', 'label' => 'Keluhan', 'type' => 'textarea', 'hint' => 'Minimal 10 karakter. Contoh: demam 3 hari, batuk berdahak, nafsu makan turun.', 'attrs' => 'rows="5" required minlength="10" maxlength="2000"'])
    @include('partials.field', ['name' => 'sejak', 'label' => 'Dirasakan sejak (opsional)', 'attrs' => 'placeholder="contoh: 3 hari lalu"'])
    <button class="btn btn-primary btn-block btn-lg"><x-ico n="chat"/> Kirim ke dokter</button>
  </form>
  <div class="card"><h3>Perlu diketahui</h3>
    <div class="alert alert-warn"><x-ico n="alert"/><div><b>Bukan untuk keadaan darurat.</b> Untuk sesak berat, perdarahan, nyeri dada, atau kejang segera ke IGD / hubungi 119.</div></div>
    <p class="muted">Tanggapan dokter bersifat saran awal dan tidak menggantikan pemeriksaan langsung. Setelah ditanggapi, Anda dapat langsung mengambil antrean yang tertaut dengan konsultasi ini.</p>
    <div class="notif-preview"><x-ico n="bell"/> Dokter akan menanggapi konsultasi Anda dan Anda akan menerima notifikasi pemberitahuan.</div>
  </div>
</div>
@endsection

