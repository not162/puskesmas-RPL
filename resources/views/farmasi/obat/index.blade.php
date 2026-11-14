@extends('layouts.app')
@section('title', 'Obat & Stok')
@section('content')
<div class="page-h"><div><h2>Formularium & stok obat</h2><p>Perubahan langsung terlihat di pemilih obat dokter. Stok berkurang otomatis saat obat diserahkan.</p></div>
  <div class="flex"><a href="{{ route('formularium') }}" class="btn"><x-ico n="list"/> Lihat per kategori</a><a href="{{ route('obat.create') }}" class="btn btn-primary"><x-ico n="plus"/> Tambah obat</a></div></div>
@if ($menipis)<div class="alert alert-warn"><x-ico n="alert"/><div><b>{{ $menipis }} obat stoknya menipis.</b> <a href="?stok=menipis">Tampilkan</a></div></div>@endif
<form class="filters card" method="GET" style="padding:12px">
  <input class="input" name="q" value="{{ request('q') }}" placeholder="Cari kode, nama, zat aktif, atau keluhan (mis. demam)" style="flex:1 1 260px">
  <select name="kategori" class="input"><option value="">Semua kategori</option>@foreach ($kategori as $k)<option @selected(request('kategori') === $k)>{{ $k }}</option>@endforeach</select>
  <select name="stok" class="input"><option value="">Semua stok</option><option value="menipis" @selected(request('stok') === 'menipis')>Stok menipis</option></select>
  <button class="btn"><x-ico n="search"/> Cari</button>
</form>
<div class="table-wrap mt" id="live-obat" data-live="obat"><table>
  <tr><th>Kode</th><th>Obat</th><th>Kategori</th><th>Poli</th><th class="tr">Stok</th><th></th></tr>
  @forelse ($obat as $o)
    <tr class="{{ $o->aktif ? '' : 'nonaktif' }}"><td class="mono">{{ $o->kode ?? '-' }}</td>
      <td><b>{{ $o->nama_obat }}</b> <span class="muted">· {{ $o->bentuk }}</span><br><small class="muted">{{ $o->kegunaan }}</small>
        <div class="flex" style="gap:4px;margin-top:4px">
          @if ($o->golongan === 'antibiotik')<span class="badge b-warn">Antibiotik</span>@endif
          @if ($o->keamanan_hamil === 'hindari')<span class="badge b-bad">Hindari saat hamil</span>@elseif ($o->keamanan_hamil === 'aman')<span class="badge b-ok">Aman ibu hamil</span>@endif
          @unless ($o->aktif)<span class="badge">Nonaktif</span>@endunless</div></td>
      <td>{{ $o->kategori }}</td><td>{{ \App\Models\Obat::POLI[$o->poli] ?? $o->poli }}</td>
      <td class="tr"><span class="badge {{ $o->stok <= $o->stok_minimum ? 'b-bad' : ($o->stok <= $o->stok_minimum * 2.5 ? 'b-warn' : 'b-ok') }}">{{ $o->stok }} {{ $o->satuan }}</span></td>
      <td><div class="actions"><a class="btn btn-sm" href="{{ route('obat.edit', $o) }}"><x-ico n="edit"/> Ubah</a>
        <form class="inline" method="POST" action="{{ route('obat.destroy', $o) }}" data-confirm="Hapus {{ $o->nama_obat }}? Obat yang sudah pernah diresepkan tidak bisa dihapus (nonaktifkan saja)." data-confirm-title="Hapus obat">@csrf @method('DELETE')<button class="btn btn-sm btn-bad" aria-label="Hapus"><x-ico n="trash"/></button></form></div></td></tr>
  @empty <tr><td colspan="6" class="empty">Tidak ada obat yang cocok.</td></tr>
  @endforelse
</table></div>
{{ $obat->links('partials.pager') }}
@endsection

