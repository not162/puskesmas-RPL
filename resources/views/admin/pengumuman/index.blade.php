@extends('layouts.app')
@section('title', 'Pengumuman')
@section('content')
<div class="page-h"><div><h2>Pengumuman & agenda</h2><p>Tampil di beranda publik.</p></div><a href="{{ route('admin.pengumuman.create') }}" class="btn btn-primary"><x-ico n="plus"/> Buat</a></div>
<div id="live-admin-pengumuman-index" data-live="pengumuman" class="table-wrap"><table>
  <tr><th>Tanggal</th><th>Judul</th><th>Kategori</th><th>Terbit</th><th></th></tr>
  @forelse ($pengumuman as $p)
    <tr><td>{{ $p->tanggal->format('d/m/Y') }}</td><td>{{ $p->judul }}</td><td>{{ ucfirst($p->kategori) }}</td><td>{!! $p->terbit ? '<span class="badge b-ok">Ya</span>' : '<span class="badge">Draf</span>' !!}</td>
      <td><div class="actions"><a class="btn btn-sm" href="{{ route('admin.pengumuman.edit', $p) }}"><x-ico n="edit"/></a>
        <form class="inline" method="POST" action="{{ route('admin.pengumuman.destroy', $p) }}" data-confirm="Hapus pengumuman ini?" data-confirm-title="Hapus">@csrf @method('DELETE')<button class="btn btn-sm btn-bad"><x-ico n="trash"/></button></form></div></td></tr>
  @empty <tr><td colspan="5" class="empty">Belum ada.</td></tr>
  @endforelse
</table></div>
{{ $pengumuman->links('partials.pager') }}
@endsection

