@extends('layouts.app')
@section('title', 'Data Pasien')
@section('content')
<form class="filters" method="GET"><input class="input" name="q" value="{{ request('q') }}" placeholder="Nama / NIK / No. RM"><button class="btn"><x-ico n="search"/> Cari</button></form>
<div id="live-admin-pasien" data-live="pasien" class="table-wrap mt"><table>
  <tr><th>No. RM</th><th>Nama</th><th>NIK</th><th>Email</th><th>Kunjungan</th><th>Resep</th><th></th></tr>
  @forelse ($pasien as $p)
    <tr><td class="mono">{{ $p->no_rm }}</td><td>{{ $p->nama }}</td><td class="mono">{{ $p->nik }}</td><td>{{ $p->akun?->email ?? '-' }}</td><td>{{ $p->antrian_count }}</td><td>{{ $p->resep_count }}</td>
      <td><form method="POST" action="{{ route('admin.pasien.destroy', $p) }}" data-confirm="Hapus {{ $p->nama }} beserta seluruh riwayat antrean, konsultasi, dan resep? Tindakan permanen." data-confirm-title="Hapus data pasien">@csrf @method('DELETE')<button class="btn btn-sm btn-bad"><x-ico n="trash"/></button></form></td></tr>
  @empty <tr><td colspan="7" class="empty">Tidak ada data.</td></tr>
  @endforelse
</table></div>
{{ $pasien->links('partials.pager') }}
@endsection

