@extends('layouts.app')
@section('title', 'Kesehatan Sistem')
@section('content')
@php $ringkas = \App\Support\Pemantau::ringkas($hasil); @endphp
<div class="page-h"><div><h2>Kesehatan sistem</h2>
  <p>Diperiksa otomatis setiap jam{{ $pantauTerakhir ? ', terakhir '.\Carbon\Carbon::parse($pantauTerakhir)->locale('id')->diffForHumans() : '' }}. Super Admin mendapat notifikasi bila ada masalah.</p></div>
  <span class="badge {{ ['ok' => 'b-ok', 'warn' => 'b-warn', 'gagal' => 'b-bad'][$ringkas] }}" style="font-size:14px;padding:8px 14px">{{ ['ok' => 'Sehat', 'warn' => 'Perlu perhatian', 'gagal' => 'Ada masalah'][$ringkas] }}</span></div>
<div class="table-wrap"><table>
  <tr><th>Status</th><th>Pemeriksaan</th><th>Keadaan</th><th>Yang perlu dilakukan</th></tr>
  @foreach ($hasil as $x)
    <tr><td><span class="badge {{ ['ok' => 'b-ok', 'warn' => 'b-warn', 'gagal' => 'b-bad'][$x['status']] }}">{{ ['ok' => 'Baik', 'warn' => 'Perhatian', 'gagal' => 'Masalah'][$x['status']] }}</span></td>
      <td><b>{{ $x['item'] }}</b></td><td>{{ $x['ket'] }}</td><td class="muted">{{ $x['saran'] ?: '-' }}</td></tr>
  @endforeach
</table></div>
<div class="grid g2 mt">
  <div class="card"><h3>Versi</h3><dl class="kv">@foreach ($versi as $k => $v)<dt>{{ $k }}</dt><dd>{{ $v }}</dd>@endforeach</dl></div>
  <div class="card"><h3>Tugas pemeliharaan</h3>
    <ul class="list-plain">
      <li><span>Pantau kesehatan</span><span class="muted">setiap jam</span></li>
      <li><span>Cadangan basis data</span><span class="muted">setiap hari 02.00</span></li>
      <li><span>Periksa celah keamanan pustaka</span><span class="muted">setiap Senin 03.00</span></li>
      <li><span>Rotasi kunci tanda tangan</span><span class="muted">tiap 6 bulan (manual)</span></li>
    </ul>
    <p class="muted mb0" style="font-size:13px">Panduan lengkap: docs/PEMELIHARAAN.md</p></div>
</div>
@endsection
