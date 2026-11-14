@extends('layouts.app')
@section('title', 'Stok Obat')
@section('content')
<div class="page-h"><div><h2>Data & stok obat</h2><p>Stok berkurang otomatis saat obat diserahkan.</p></div><a href="{{ route('obat.create') }}" class="btn btn-primary"><x-ico n="plus"/> Tambah obat</a></div>
<form class="filters" method="GET"><input class="input" name="q" value="{{ request('q') }}" placeholder="Cari obat"><button class="btn"><x-ico n="search"/></button></form>
<div id="live-farmasi-obat-index" data-live="obat" class="table-wrap mt"><table>
  <tr><th>Nama obat</th><th>Satuan</th><th class="tr">Stok</th><th></th></tr>
  @forelse ($obat as $o)
    <tr><td>{{ $o->nama_obat }}</td><td>{{ $o->satuan }}</td><td class="tr"><span class="badge {{ $o->stok < 20 ? 'b-bad' : ($o->stok < 50 ? 'b-warn' : 'b-ok') }}">{{ $o->stok }}</span></td>
      <td><div class="actions"><a class="btn btn-sm" href="{{ route('obat.edit', $o) }}"><x-ico n="edit"/> Ubah</a>
        <form class="inline" method="POST" action="{{ route('obat.destroy', $o) }}" data-confirm="Hapus {{ $o->nama_obat }}? Tindakan ini tidak dapat dibatalkan." data-confirm-title="Hapus obat">@csrf @method('DELETE')<button class="btn btn-sm btn-bad"><x-ico n="trash"/></button></form></div></td></tr>
  @empty <tr><td colspan="4" class="empty">Tidak ada data.</td></tr>
  @endforelse
</table></div>
{{ $obat->links('partials.pager') }}
@endsection

