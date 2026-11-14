@extends('layouts.app')
@section('title', 'Riwayat Resep')
@section('content')
<div id="live-dokter-resep" data-live="resep" class="table-wrap"><table>
  <tr><th>Tanggal</th><th>Pasien</th><th>Obat</th><th>Status</th></tr>
  @forelse ($resep as $r)
    <tr><td>{{ $r->tgl_resep->format('d/m/Y H:i') }}</td><td>{{ $r->pasien->nama }}</td><td>{{ $r->obat->map(fn ($o) => $o->nama_obat.' ×'.$o->pivot->jumlah)->implode(', ') }}</td><td>@include('partials.status', ['s' => $r->status])</td></tr>
  @empty <tr><td colspan="4" class="empty">Belum ada resep.</td></tr>
  @endforelse
</table></div>
{{ $resep->links('partials.pager') }}
@endsection

