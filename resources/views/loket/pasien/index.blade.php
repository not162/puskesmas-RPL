@extends('layouts.app')
@section('title', 'Pendaftaran Pasien')
@section('content')
<div class="page-h"><div><h2>Data pasien</h2><p>Cari pasien lama atau daftarkan pasien baru yang datang langsung.</p></div><a href="{{ route('loket.pasien.create') }}" class="btn btn-primary"><x-ico n="plus"/> Pasien baru</a></div>
<form class="filters" method="GET"><input class="input" name="q" value="{{ request('q') }}" placeholder="Nama / NIK / No. RM"><button class="btn"><x-ico n="search"/> Cari</button></form>
<div id="live-loket-pasien-index" data-live="pasien,antrian" class="table-wrap mt"><table>
  <tr><th>No. RM</th><th>Nama</th><th>NIK</th><th>HP</th><th>Akun</th><th>Ambil antrean hari ini</th><th></th></tr>
  @forelse ($pasien as $p)
    <tr><td class="mono">{{ $p->no_rm }}</td><td>{{ $p->nama }}</td><td class="mono">{{ $p->nik }}</td><td>{{ $p->no_tlp }}</td><td>{!! $p->id_akun ? '<span class="badge b-ok">Ada</span>' : '<span class="badge">Belum</span>' !!}</td>
      <td><form method="POST" action="{{ route('loket.pasien.antrian', $p) }}" class="flex">@csrf
        <select name="id_spesialisasi" class="input" style="width:auto;padding:6px">@foreach ($spesialisasi as $s)<option value="{{ $s->id_spesialisasi }}">{{ $s->poli }}</option>@endforeach</select>
        <button class="btn btn-sm btn-primary">Ambil</button></form></td>
      <td class="actions"><a class="btn btn-sm" href="{{ route('loket.pasien.edit', $p) }}"><x-ico n="edit"/></a>@if($p->foto_ktp)<a class="btn btn-sm" target="_blank" href="{{ route('ktp', $p) }}" title="Lihat KTP (diaudit)">KTP</a>@endif</td></tr>
  @empty <tr><td colspan="7" class="empty">Tidak ditemukan.</td></tr>
  @endforelse
</table></div>
{{ $pasien->links('partials.pager') }}
@endsection

