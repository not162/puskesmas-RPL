@extends('layouts.app')
@section('title', 'Pemeriksaan '.$a->nomor)
@section('content')
<div class="grid" style="grid-template-columns:minmax(0,2fr) minmax(0,1fr)">
  <form class="card" method="POST" action="{{ route('dokter.periksa.simpan', $a) }}">@csrf
    <div class="card-h"><h3>{{ $a->pasien->nama }} · {{ $a->nomor }}</h3>@include('partials.status', ['s' => $a->status])</div>
    @include('partials.field', ['name' => 'keluhan', 'label' => 'Anamnesis / keluhan', 'type' => 'textarea', 'value' => $a->keluhan_awal ?? $a->konsultasi?->keluhan, 'attrs' => 'required rows="3"'])
    @include('partials.field', ['name' => 'diagnosis', 'label' => 'Diagnosis', 'type' => 'textarea', 'value' => $a->konsultasi?->diagnosis, 'attrs' => 'required rows="3"'])
    <h3 class="mt">Resep obat</h3>
    <p class="muted">Kosongkan bila tidak perlu obat. Resep otomatis diteruskan ke farmasi.</p>
    <div id="rx-rows">
      @foreach (old('obat', [[]]) as $i => $row)
        <div class="rx-row">
          <select name="obat[{{ $i }}][id_obat]" class="input"><option value="">— Pilih obat —</option>@foreach ($obat as $o)<option value="{{ $o->id_obat }}" @selected(($row['id_obat'] ?? null) == $o->id_obat)>{{ $o->nama_obat }} (stok {{ $o->stok }})</option>@endforeach</select>
          <input name="obat[{{ $i }}][jumlah]" type="number" min="1" class="input" placeholder="Jml" value="{{ $row['jumlah'] ?? '' }}">
          <input name="obat[{{ $i }}][aturan_pakai]" class="input" placeholder="3 x sehari sesudah makan" value="{{ $row['aturan_pakai'] ?? '' }}">
          <button type="button" class="btn btn-icon btn-bad" data-rx-del aria-label="Hapus"><x-ico n="x"/></button>
        </div>
      @endforeach
    </div>
    <template id="rx-template"><div class="rx-row">
      <select name="obat[__i__][id_obat]" class="input"><option value="">— Pilih obat —</option>@foreach ($obat as $o)<option value="{{ $o->id_obat }}">{{ $o->nama_obat }} (stok {{ $o->stok }})</option>@endforeach</select>
      <input name="obat[__i__][jumlah]" type="number" min="1" class="input" placeholder="Jml"><input name="obat[__i__][aturan_pakai]" class="input" placeholder="Aturan pakai">
      <button type="button" class="btn btn-icon btn-bad" data-rx-del aria-label="Hapus"><x-ico n="x"/></button></div></template>
    <button type="button" class="btn btn-sm" id="rx-add"><x-ico n="plus"/> Tambah obat</button>
    <div class="flex mt"><button class="btn btn-primary btn-lg"><x-ico n="check"/> Simpan & selesaikan</button><a href="{{ route('dokter.antrian') }}" class="btn">Kembali</a></div>
  </form>
  <div>
    <div class="card"><h3>Pasien</h3>
      <dl class="kv"><dt>No. RM</dt><dd>{{ $a->pasien->no_rm }}</dd><dt>Usia</dt><dd>{{ $a->pasien->tgl_lahir?->age ?? '-' }} th</dd><dt>JK</dt><dd>{{ $a->pasien->jenis_kelamin }}</dd></dl>
      @if ($a->konsultasi && $a->konsultasi->jenis === 'online')<div class="notif-preview"><b>Dari konsultasi online:</b> {{ $a->konsultasi->tanggapan }}</div>@endif
    </div>
    <div class="card"><h3>Riwayat diagnosis</h3>
      <ul class="list-plain">@forelse ($riwayat as $k)<li><span>{{ $k->tanggal->format('d/m/Y') }}<br><small>{{ $k->diagnosis }}</small></span><small>{{ $k->dokter?->nama }}</small></li>@empty<li class="muted">Belum ada riwayat.</li>@endforelse</ul></div>
  </div>
</div>
@endsection

