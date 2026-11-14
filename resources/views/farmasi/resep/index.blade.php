@extends('layouts.app')
@section('title', 'Resep Masuk')
@section('content')
<div class="page-h"><div><h2>Resep</h2><p>Diteruskan dokter ke farmasi.</p></div>
  <div class="tabs" style="margin:0">@foreach (['aktif' => 'Aktif', 'baru' => 'Baru', 'diproses' => 'Diproses', 'diserahkan' => 'Diserahkan', 'semua' => 'Semua'] as $s => $l)<a class="{{ $status === $s ? 'on' : '' }}" href="?status={{ $s }}">{{ $l }}</a>@endforeach</div></div>
<div id="live-farmasi-resep-index" data-live="resep,obat" class="table-wrap"><table>
  <tr><th>Waktu</th><th>Pasien</th><th>Dokter</th><th>Obat</th><th>Status</th><th>Aksi</th></tr>
  @forelse ($resep as $r)
    <tr><td class="nowrap">{{ $r->tgl_resep->format('d/m H:i') }}</td><td>{{ $r->pasien->nama }}<br><small>{{ $r->pasien->no_rm }}</small></td><td>{{ $r->dokter->nama }}</td>
      <td>{{ $r->obat->count() }} item</td><td>@include('partials.status', ['s' => $r->status])</td>
      <td><div class="actions"><a class="btn btn-sm" href="{{ route('farmasi.resep.show', $r) }}">Detail</a>
        @if ($r->status === 'baru')<form class="inline" method="POST" action="{{ route('farmasi.resep.proses', $r) }}">@csrf<button class="btn btn-sm btn-primary">Proses</button></form>@endif
        @if ($r->status === 'diproses')<form class="inline" method="POST" action="{{ route('farmasi.resep.serahkan', $r) }}" data-confirm="Obat diserahkan ke {{ $r->pasien->nama }}. Stok akan dikurangi otomatis." data-confirm-title="Serahkan obat?" data-confirm-tone="ok" data-confirm-ok="Serahkan">@csrf<button class="btn btn-sm btn-ok">Serahkan</button></form>@endif
      </div></td></tr>
  @empty <tr><td colspan="6" class="empty">Tidak ada resep.</td></tr>
  @endforelse
</table></div>
{{ $resep->links('partials.pager') }}
@endsection

