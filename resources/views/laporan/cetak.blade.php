@extends('layouts.base')
@section('title', 'Laporan '.$lap['label'])
@section('body')
<div class="container" style="padding:24px 16px">
  <div class="flex between"><div><h1>{{ $namaPkm }}</h1><p class="muted">Laporan Bulanan · {{ $lap['label'] }} · dicetak {{ now()->format('d/m/Y H:i') }}</p></div>
    <button class="btn btn-primary no-print" onclick="print()"><x-ico n="printer"/> Cetak / Simpan PDF</button></div>
  @include('laporan._isi')
  <p class="muted mt" style="text-align:center;font-size:12px">Dibuat di Indonesia · © 2026 Seluruh hak cipta dilindungi · Grup NussCorp Technologies Indonesia</p>
</div>
<script>setTimeout(() => print(), 600);</script>
@endsection
