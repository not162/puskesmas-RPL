@extends('layouts.app')
@section('title', $p->exists ? 'Ubah Pengumuman' : 'Buat Pengumuman')
@section('content')
<form class="card" style="max-width:720px" method="POST" action="{{ $p->exists ? route('admin.pengumuman.update', $p) : route('admin.pengumuman.store') }}">@csrf
  @if ($p->exists) @method('PUT') @endif
  @include('partials.field', ['name' => 'judul', 'label' => 'Judul', 'value' => $p->judul, 'attrs' => 'required'])
  @include('partials.field', ['name' => 'isi', 'label' => 'Isi', 'type' => 'textarea', 'value' => $p->isi, 'attrs' => 'required rows="5"'])
  <div class="grid g2">
    <div class="field"><label>Kategori</label><select name="kategori" class="input">@foreach (['pengumuman','agenda','edukasi'] as $k)<option @selected(old('kategori', $p->kategori) === $k)>{{ $k }}</option>@endforeach</select></div>
    @include('partials.field', ['name' => 'tanggal', 'label' => 'Tanggal', 'type' => 'date', 'value' => $p->tanggal?->toDateString(), 'attrs' => 'required'])
  </div>
  <label class="check field"><input type="checkbox" name="terbit" value="1" @checked(old('terbit', $p->terbit))> Terbitkan di halaman publik</label>
  <div class="flex"><button class="btn btn-primary">Simpan</button><a class="btn" href="{{ route('admin.pengumuman.index') }}">Batal</a></div>
</form>
@endsection

