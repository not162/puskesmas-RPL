@extends('layouts.app')
@section('title', $obat->exists ? 'Ubah Obat' : 'Tambah Obat')
@section('content')
<form class="card" style="max-width:560px" method="POST" action="{{ $obat->exists ? route('obat.update', $obat) : route('obat.store') }}">@csrf
  @if ($obat->exists) @method('PUT') @endif
  @include('partials.field', ['name' => 'nama_obat', 'label' => 'Nama obat', 'value' => $obat->nama_obat, 'attrs' => 'required'])
  <div class="grid g2">
    @include('partials.field', ['name' => 'satuan', 'label' => 'Satuan', 'value' => $obat->satuan ?? 'tablet', 'attrs' => 'required list="satuan"'])
    @include('partials.field', ['name' => 'stok', 'label' => 'Stok', 'type' => 'number', 'value' => $obat->stok ?? 0, 'attrs' => 'required min="0"'])
  </div>
  <datalist id="satuan"><option>tablet</option><option>kapsul</option><option>botol</option><option>sachet</option><option>tube</option><option>ampul</option></datalist>
  <div class="flex"><button class="btn btn-primary">Simpan</button><a href="{{ route('obat.index') }}" class="btn">Batal</a></div>
</form>
@endsection

