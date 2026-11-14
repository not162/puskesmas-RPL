@extends('layouts.app')
@section('title', 'Resep & Obat')
@section('content')
<div class="page-h"><div><h2>Resep saya</h2><p>Langkah 8–10 · Pantau resep dari dokter hingga obat diserahkan.</p></div></div>
@forelse ($resep as $r)
  <div class="card">
    <div class="card-h"><div><b>{{ $r->dokter->nama }}</b> <span class="muted">· {{ $r->dokter->spesialisasi->poli }} · {{ $r->tgl_resep->format('d/m/Y H:i') }}</span></div>@include('partials.status', ['s' => $r->status])</div>
    <div class="table-wrap"><table><tr><th>Obat</th><th>Jumlah</th><th>Aturan pakai</th></tr>
      @foreach ($r->obat as $o)<tr><td>{{ $o->nama_obat }}</td><td>{{ $o->pivot->jumlah }} {{ $o->satuan }}</td><td>{{ $o->pivot->aturan_pakai }}</td></tr>@endforeach</table></div>
    <p class="muted mt mb0">Kode resep: <span class="mono">{{ $r->kode }}</span>@if($r->penyerahan) · diserahkan {{ $r->penyerahan->waktu->format('d/m/Y H:i') }}@endif</p>
  </div>
@empty <div class="card empty">Belum ada resep.</div>
@endforelse
{{ $resep->links('partials.pager') }}
@endsection

