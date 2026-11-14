@extends('layouts.app')
@section('title', $obat->exists ? 'Ubah Obat' : 'Tambah Obat')
@section('content')
<form class="card" style="max-width:900px" method="POST" action="{{ $obat->exists ? route('obat.update', $obat) : route('obat.store') }}">@csrf
  @if ($obat->exists) @method('PUT') @endif
  <div class="card-h"><h3>{{ $obat->exists ? 'Ubah '.$obat->nama_obat : 'Tambah obat ke formularium' }}</h3>
    <label class="check"><input type="checkbox" name="aktif" value="1" @checked(old('aktif', $obat->aktif ?? true))> Aktif (bisa dipilih dokter)</label></div>

  <h4 class="sub-judul">Identitas obat</h4>
  <div class="grid g3">
    @include('partials.field', ['name' => 'kode', 'label' => 'Kode', 'value' => $obat->kode, 'hint' => 'Format OBT-001', 'attrs' => 'required placeholder="OBT-021" pattern="OBT-\d{3,4}"'])
    <div style="grid-column:span 2">@include('partials.field', ['name' => 'nama_obat', 'label' => 'Nama obat & kekuatan', 'value' => $obat->nama_obat, 'attrs' => 'required placeholder="Contoh: Parasetamol 500 mg"'])</div>
  </div>
  <div class="grid g3">
    <div class="field"><label for="kategori">Kategori</label><select id="kategori" name="kategori" class="input" required>
      <option value="">Pilih kategori</option>@foreach (config('formularium.kategori') as $k)<option @selected(old('kategori', $obat->kategori) === $k)>{{ $k }}</option>@endforeach</select></div>
    <div class="field"><label for="bentuk">Bentuk sediaan</label><select id="bentuk" name="bentuk" class="input" required>
      @foreach (\App\Models\Obat::BENTUK as $b)<option @selected(old('bentuk', $obat->bentuk) === $b)>{{ $b }}</option>@endforeach</select></div>
    @include('partials.field', ['name' => 'zat_aktif', 'label' => 'Zat aktif', 'value' => $obat->zat_aktif, 'attrs' => 'placeholder="Contoh: Parasetamol"'])
  </div>
  @include('partials.field', ['name' => 'kegunaan', 'label' => 'Kegunaan singkat', 'value' => $obat->kegunaan, 'attrs' => 'maxlength="160" placeholder="Contoh: Pereda demam & nyeri ringan–sedang"'])
  @include('partials.field', ['name' => 'indikasi', 'label' => 'Kata kunci keluhan/diagnosis (untuk saran obat)', 'type' => 'textarea', 'value' => $obat->indikasi, 'hint' => 'Pisahkan dengan koma. Dipakai untuk mencocokkan keluhan pasien.', 'attrs' => 'rows="2" placeholder="demam, nyeri, sakit kepala, ispa"'])

  <h4 class="sub-judul">Keamanan & penggunaan</h4>
  <div class="grid g3">
    <div class="field"><label for="golongan">Golongan</label><select id="golongan" name="golongan" class="input">
      <option value="">Umum</option>@foreach (\App\Models\Obat::GOLONGAN as $k => $l)<option value="{{ $k }}" @selected(old('golongan', $obat->golongan) === $k)>{{ $l }}</option>@endforeach</select></div>
    <div class="field"><label for="poli">Poli</label><select id="poli" name="poli" class="input">
      @foreach (\App\Models\Obat::POLI as $k => $l)<option value="{{ $k }}" @selected(old('poli', $obat->poli) === $k)>{{ $l }}</option>@endforeach</select></div>
    <div class="field"><label for="keamanan_hamil">Keamanan ibu hamil</label><select id="keamanan_hamil" name="keamanan_hamil" class="input">
      @foreach (\App\Models\Obat::KEAMANAN_HAMIL as $k => $l)<option value="{{ $k }}" @selected(old('keamanan_hamil', $obat->keamanan_hamil) === $k)>{{ $l }}</option>@endforeach</select></div>
  </div>
  <div class="grid g3">
    @include('partials.field', ['name' => 'jumlah_bawaan', 'label' => 'Jumlah bawaan per resep', 'type' => 'number', 'value' => $obat->jumlah_bawaan, 'attrs' => 'required min="1" placeholder="10"'])
    <div style="grid-column:span 2">@include('partials.field', ['name' => 'aturan_bawaan', 'label' => 'Aturan pakai bawaan', 'value' => $obat->aturan_bawaan, 'attrs' => 'placeholder="Contoh: 3x1 sesudah makan jika demam"'])</div>
  </div>
  @include('partials.field', ['name' => 'catatan', 'label' => 'Catatan untuk pasien', 'value' => $obat->catatan, 'attrs' => 'maxlength="255" placeholder="Contoh: Antibiotik, wajib dihabiskan. / Jangan diminum bersama teh atau kopi."'])

  <h4 class="sub-judul">Stok</h4>
  <div class="grid g3">
    @include('partials.field', ['name' => 'satuan', 'label' => 'Satuan', 'value' => $obat->satuan ?? 'tablet', 'attrs' => 'required list="satuan" placeholder="tablet"'])
    @include('partials.field', ['name' => 'stok', 'label' => 'Stok saat ini', 'type' => 'number', 'value' => $obat->stok ?? 0, 'attrs' => 'required min="0" placeholder="0"'])
    @include('partials.field', ['name' => 'stok_minimum', 'label' => 'Peringatan bila stok ≤', 'type' => 'number', 'value' => $obat->stok_minimum ?? 20, 'attrs' => 'required min="0" placeholder="20"'])
  </div>
  <datalist id="satuan"><option>tablet</option><option>kapsul</option><option>sachet</option><option>botol</option><option>tube</option><option>ampul</option></datalist>
  <div class="flex"><button class="btn btn-primary">Simpan</button><a href="{{ route('obat.index') }}" class="btn">Batal</a></div>
</form>
@endsection

