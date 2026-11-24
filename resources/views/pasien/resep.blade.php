@extends('layouts.app')
@section('title', 'Resep & Obat')
@section('content')
<div class="page-h"><div><h2>Resep saya</h2><p>Pantau resep dari dokter hingga penyerahan obat.</p></div></div>
@forelse ($resep as $r)
  <div class="card">
    <div class="card-h"><div><b>{{ $r->dokter->nama }}</b> <span class="muted">· {{ $r->dokter->spesialisasi->poli }} · {{ $r->tgl_resep->format('d/m/Y H:i') }}</span></div>@include('partials.status', ['s' => $r->status])</div>
    <div class="table-wrap"><table><tr><th>Obat</th><th>Jumlah</th><th>Aturan pakai</th></tr>
      @foreach ($r->obat as $o)<tr><td>{{ $o->nama_obat }}</td><td>{{ $o->pivot->jumlah }} {{ $o->satuan }}</td><td>{{ $o->pivot->aturan_pakai }}@if($o->catatan)<br><small class="muted">{{ $o->catatan }}</small>@endif</td></tr>@endforeach</table></div>
    <p class="muted mt mb0">Kode resep: <span class="mono">{{ $r->kode }}</span>@if($r->penyerahan) · diserahkan {{ $r->penyerahan->waktu->format('d/m/Y H:i') }}@endif</p>
  </div>
@empty <div class="card empty">Belum ada resep.</div>
@endforelse
{{ $resep->links('partials.pager') }}
<div class="alert alert-info mt"><x-ico n="info"/><div><b>Bijak minum obat:</b> habiskan antibiotik sesuai anjuran, sampaikan riwayat alergi obat, dan jangan membagikan obat ke orang lain. <a href="{{ route('info') }}">Baca tips hidup sehat →</a></div></div>
@endsection

