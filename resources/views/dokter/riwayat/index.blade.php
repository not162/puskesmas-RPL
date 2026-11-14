@extends('layouts.app')
@section('title', 'Riwayat Pasien')
@section('content')
<form class="filters" method="GET"><input class="input" name="q" value="{{ request('q') }}" placeholder="Nama / No. RM"><button class="btn"><x-ico n="search"/> Cari</button></form>
<div class="table-wrap mt" id="live-riwayat" data-live="konsultasi"><table>
  <tr><th>No. RM</th><th>Nama</th><th>Usia / JK</th><th>Kunjungan di poli</th><th></th></tr>
  @forelse ($pasien as $p)
    <tr><td class="mono">{{ $p->no_rm }}</td><td>{{ $p->nama }}</td><td>{{ $p->tgl_lahir?->age ?? '-' }} th / {{ $p->jenis_kelamin }}</td><td>{{ $p->konsultasi_count }}</td>
      <td><a class="btn btn-sm" href="{{ route('dokter.riwayat.show', $p) }}">Lihat riwayat</a></td></tr>
  @empty <tr><td colspan="5" class="empty">Belum ada pasien di poli Anda.</td></tr>
  @endforelse
</table></div>
{{ $pasien->links('partials.pager') }}
@endsection

