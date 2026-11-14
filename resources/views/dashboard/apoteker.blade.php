@extends('layouts.app')
@section('title', 'Dasbor Farmasi')
@section('content')
<div id="live-dash" data-live="resep,obat">
<div class="grid g3">
  <a class="card stat" href="{{ route('farmasi.resep.index', ['status' => 'baru']) }}"><span class="ic c-amber"><x-ico n="pill"/></span><div><b>{{ $baru }}</b><span>Resep baru</span></div></a>
  <a class="card stat" href="{{ route('farmasi.resep.index', ['status' => 'diproses']) }}"><span class="ic c-blue"><x-ico n="clock"/></span><div><b>{{ $diproses }}</b><span>Sedang diproses</span></div></a>
  <div class="card stat"><span class="ic c-green"><x-ico n="check"/></span><div><b>{{ $diserahkanHariIni }}</b><span>Diserahkan hari ini</span></div></div>
</div>
<div class="card mt">
  <div class="card-h"><h3>Stok menipis (&lt; 20)</h3><a href="{{ route('obat.index') }}">Kelola stok</a></div>
  @if ($stokMenipis->isEmpty())<div class="empty">Semua stok aman.</div>@else
  <div class="table-wrap"><table><tr><th>Obat</th><th>Satuan</th><th class="tr">Stok</th></tr>
    @foreach ($stokMenipis as $o)<tr><td>{{ $o->nama_obat }}</td><td>{{ $o->satuan }}</td><td class="tr"><span class="badge b-bad">{{ $o->stok }}</span></td></tr>@endforeach
  </table></div>@endif
</div>
</div>
@endsection

