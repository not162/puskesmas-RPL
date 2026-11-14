@extends('layouts.app')
@section('title', 'Detail Resep')
@section('content')
<div class="grid g2">
  <div class="card"><div class="card-h"><h3>Resep {{ substr($r->kode, -8) }}</h3>@include('partials.status', ['s' => $r->status])</div>
    <dl class="kv"><dt>Pasien</dt><dd>{{ $r->pasien->nama }} ({{ $r->pasien->no_rm }})</dd><dt>Dokter</dt><dd>{{ $r->dokter->nama }} — {{ $r->dokter->spesialisasi->poli }}</dd>
      <dt>Tanggal</dt><dd>{{ $r->tgl_resep->format('d/m/Y H:i') }}</dd><dt>Diagnosis</dt><dd>{{ $r->konsultasi?->diagnosis ?? '-' }}</dd>
      <dt>Apoteker</dt><dd>{{ $r->apoteker?->nama ?? '-' }}</dd>@if($r->penyerahan)<dt>Diserahkan</dt><dd>{{ $r->penyerahan->waktu->format('d/m/Y H:i') }}</dd>@endif</dl>
    <div class="flex mt">
      @if ($r->status === 'baru')<form method="POST" action="{{ route('farmasi.resep.proses', $r) }}">@csrf<button class="btn btn-primary">Proses resep</button></form>@endif
      @if ($r->status === 'diproses')<form method="POST" action="{{ route('farmasi.resep.serahkan', $r) }}" data-confirm="Stok obat akan dikurangi otomatis." data-confirm-title="Serahkan obat?" data-confirm-tone="ok" data-confirm-ok="Serahkan">@csrf<button class="btn btn-ok">Serahkan obat</button></form>@endif
      <button class="btn no-print" onclick="print()"><x-ico n="printer"/> Cetak etiket</button>
    </div></div>
  <div class="card"><h3>Obat</h3>
    <div class="table-wrap"><table><tr><th>Obat</th><th>Jumlah</th><th>Aturan</th><th>Stok</th></tr>
      @foreach ($r->obat as $o)<tr><td>{{ $o->nama_obat }}</td><td>{{ $o->pivot->jumlah }} {{ $o->satuan }}</td><td>{{ $o->pivot->aturan_pakai }}</td><td>@if($o->stok < $o->pivot->jumlah && $r->status !== 'diserahkan')<span class="badge b-bad">{{ $o->stok }}</span>@else{{ $o->stok }}@endif</td></tr>@endforeach
    </table></div></div>
</div>
@endsection

