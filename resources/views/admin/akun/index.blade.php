@extends('layouts.app')
@section('title', 'Akun')
@section('content')
<div class="page-h"><div><h2>Akun pengguna</h2><p>7 peran: Super Admin, Admin, Dokter Umum, Dokter Kandungan, Apoteker, Loket, Pasien. Username & password tiap akun berbeda. <a href="{{ route('admin.pasien.index') }}">Data pasien →</a></p></div><a href="{{ route('admin.akun.create') }}" class="btn btn-primary"><x-ico n="plus"/> Tambah akun staf</a></div>
<form class="filters" method="GET">
  <select name="peran" class="input"><option value="">Semua peran</option>@foreach ($peran as $p)<option value="{{ $p->nama_peran }}" @selected(request('peran') === $p->nama_peran)>{{ $p->label }}</option>@endforeach</select>
  <input class="input" name="q" value="{{ request('q') }}" placeholder="Cari email"><button class="btn"><x-ico n="search"/></button></form>
<div id="live-admin-akun-index" data-live="akun" class="table-wrap mt"><table>
  <tr><th>Nama</th><th>Username / Email</th><th>Peran</th><th>Keterangan</th><th>Status</th><th>Aksi</th></tr>
  @foreach ($akun as $a)
    <tr><td>{{ $a->namaTampil() }}</td><td><b>{{ '@'.$a->username }}</b><br><small>{{ $a->email ?? '-' }}</small></td><td><span class="badge">{{ $a->peran->label }}</span></td>
      <td><small>{{ $a->dokter ? 'STR '.$a->dokter->no_str : ($a->apoteker ? 'SIPA '.$a->apoteker->no_sipa : ($a->pasien ? $a->pasien->no_rm : '')) }}</small></td>
      <td>@include('partials.status', ['s' => $a->status])</td>
      <td><div class="actions">
        @unless ($a->hasRole('pasien'))<a class="btn btn-sm" href="{{ route('admin.akun.edit', $a) }}"><x-ico n="edit"/></a>@endunless
        <form class="inline" method="POST" action="{{ route('admin.akun.status', $a) }}">@csrf<button class="btn btn-sm">{{ $a->aktif() ? 'Nonaktifkan' : 'Aktifkan' }}</button></form>
        <form class="inline" method="POST" action="{{ route('admin.akun.destroy', $a) }}" data-confirm="Hapus akun {{ $a->email }}? Tindakan ini tidak dapat dibatalkan." data-confirm-title="Hapus akun">@csrf @method('DELETE')<button class="btn btn-sm btn-bad"><x-ico n="trash"/></button></form>
      </div></td></tr>
  @endforeach
</table></div>
{{ $akun->links('partials.pager') }}
@endsection

