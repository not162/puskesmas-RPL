@extends('layouts.app')
@section('title', 'Antrian Saya')
@section('content')
<div class="page-h"><div><h2>Antrean saya</h2><p>Riwayat antrean online dan loket.</p></div><a href="{{ route('pasien.antrian.create') }}" class="btn btn-primary"><x-ico n="plus"/> Ambil antrean</a></div>
<div id="live-pasien-antrian-index" data-live="antrian" class="table-wrap"><table>
  <tr><th>Nomor</th><th>Poli</th><th>Tanggal</th><th>Sumber</th><th>Status</th><th></th></tr>
  @forelse ($antrian as $a)
    <tr><td><b>{{ $a->nomor }}</b></td><td>{{ $a->spesialisasi->poli }}</td><td>{{ $a->tanggal->format('d/m/Y') }}</td><td>{{ ucfirst($a->sumber) }}</td><td>@include('partials.status', ['s' => $a->status])</td>
      <td class="actions"><a class="btn btn-sm" href="{{ route('pasien.antrian.show', $a) }}">Detail</a> <a class="btn btn-sm" href="{{ route('pasien.antrian.tiket', $a) }}"><x-ico n="download"/> Tiket</a></td></tr>
  @empty <tr><td colspan="6" class="empty">Belum ada antrian.</td></tr>
  @endforelse
</table></div>
{{ $antrian->links('partials.pager') }}
@endsection

