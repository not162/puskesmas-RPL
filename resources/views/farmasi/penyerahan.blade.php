@extends('layouts.app')
@section('title', 'Riwayat Penyerahan')
@section('content')
<form class="filters" method="GET"><input type="date" name="tanggal" value="{{ request('tanggal') }}" class="input"><button class="btn">Filter</button></form>
<div class="table-wrap mt" id="live-penyerahan" data-live="resep"><table>
  <tr><th>Waktu</th><th>Pasien</th><th>Dokter</th><th>Obat</th><th>Apoteker</th></tr>
  @forelse ($penyerahan as $s)
    <tr><td class="nowrap">{{ $s->waktu->format('d/m/Y H:i') }}</td><td>{{ $s->pasien->nama }}<br><small>{{ $s->pasien->no_rm }}</small></td><td>{{ $s->resep->dokter->nama }}</td>
      <td>{{ $s->resep->obat->map(fn ($o) => $o->nama_obat.' ×'.$o->pivot->jumlah)->implode(', ') }}</td><td>{{ $s->apoteker->nama }}</td></tr>
  @empty <tr><td colspan="5" class="empty">Belum ada penyerahan.</td></tr>
  @endforelse
</table></div>
{{ $penyerahan->links('partials.pager') }}
@endsection

