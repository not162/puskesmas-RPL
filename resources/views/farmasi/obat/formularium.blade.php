@extends('layouts.app')
@section('title', 'Formularium Obat')
@section('content')
<div class="page-h"><div><h2>Formularium Puskesmas</h2><p>Daftar obat yang dapat diresepkan. Dikelola apoteker; diperbarui otomatis.</p></div>
  <form class="filters" method="GET"><input class="input" name="q" value="{{ request('q') }}" placeholder="Cari obat atau keluhan, mis. batuk">
    <select name="poli" class="input"><option value="">Semua poli</option><option value="umum" @selected(request('poli') === 'umum')>Poli Umum</option><option value="kandungan" @selected(request('poli') === 'kandungan')>Poli KIA & Kandungan</option></select>
    <button class="btn"><x-ico n="search"/></button></form></div>
<div id="live-formularium" data-live="obat">
  @forelse ($kelompok as $kat => $daftar)
    <div class="card"><h3>{{ $kat ?: 'Lainnya' }} <span class="muted" style="font-weight:500;font-size:14px">· {{ $daftar->count() }} obat</span></h3>
      <div class="grid g2 mt">
        @foreach ($daftar as $o)
          <div class="obat-kartu">
            <div class="flex between"><b>{{ $o->nama_obat }}</b><span class="mono muted">{{ $o->kode }}</span></div>
            <div class="muted" style="font-size:13px">{{ $o->bentuk }} · {{ $o->zat_aktif }}</div>
            <p class="mb0" style="margin-top:6px">{{ $o->kegunaan }}</p>
            @if ($o->aturan_bawaan)<div style="font-size:13px;margin-top:4px"><b>Dosis lazim:</b> {{ $o->jumlah_bawaan }} {{ $o->satuan }} · {{ $o->aturan_bawaan }}</div>@endif
            @if ($o->catatan)<div class="muted" style="font-size:13px">{{ $o->catatan }}</div>@endif
            <div class="flex" style="gap:4px;margin-top:6px">
              <span class="badge">{{ \App\Models\Obat::POLI[$o->poli] ?? $o->poli }}</span>
              @if ($o->golongan === 'antibiotik')<span class="badge b-warn">Antibiotik · wajib resep & habiskan</span>@endif
              <span class="badge {{ ['aman' => 'b-ok', 'hindari' => 'b-bad', 'hati_hati' => 'b-warn'][$o->keamanan_hamil] ?? '' }}">{{ \App\Models\Obat::KEAMANAN_HAMIL[$o->keamanan_hamil] ?? '' }}</span>
              <span class="badge {{ $o->stokMenipis() ? 'b-bad' : 'b-ok' }}">Stok {{ $o->stok }}</span>
            </div>
          </div>
        @endforeach
      </div></div>
  @empty <div class="card empty">Tidak ada obat yang cocok.</div>
  @endforelse
</div>
@endsection
