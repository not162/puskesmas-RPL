@extends('layouts.app')
@section('title', 'Dashboard Super Admin')
@section('content')
<div id="live-dash" data-live="antrian,konsultasi,resep,pasien,akun,obat">
<div class="page-h"><div><h2>Ringkasan Puskesmas</h2><p>Pihak Puskesmas · akses penuh</p></div>
  <div class="flex"><a href="{{ route('laporan') }}" class="btn"><x-ico n="chart"/> Laporan bulanan</a><a href="{{ route('admin.akun.create') }}" class="btn btn-primary"><x-ico n="plus"/> Tambah staf</a></div></div>
<div class="grid g3">
  @foreach ($stat as $label => $nilai)
    <div class="card stat"><span class="ic {{ ['c-blue','c-green','c-amber','c-violet','c-teal','c-red'][$loop->index % 6] }}"><x-ico :n="['users','ticket','chat','pill','shield','box'][$loop->index % 6]"/></span><div><b>{{ $nilai }}</b><span>{{ $label }}</span></div></div>
  @endforeach
</div>
<div class="card mt"><div class="card-h"><h3>Kunjungan 7 hari terakhir</h3></div>
  @php $max = max(1, $tujuhHari->max()); @endphp
  <div class="bars">@foreach ($tujuhHari as $tgl => $n)<div class="bar"><b>{{ $n }}</b><i style="height:{{ max(2, $n / $max * 100) }}%"></i>{{ $tgl }}</div>@endforeach</div>
</div>
</div>
@endsection

