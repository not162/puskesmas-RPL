@extends('layouts.app')
@section('title', 'Jadwal Praktik')
@section('content')
<form class="card" method="POST" action="{{ route('admin.jadwal.store') }}">@csrf
  <h3>Tambah jadwal</h3>
  <div class="filters">
    <select name="id_dokter" class="input" required>@foreach ($dokter as $d)<option value="{{ $d->id_dokter }}">{{ $d->nama }} ({{ $d->spesialisasi->nama }})</option>@endforeach</select>
    <select name="hari" class="input">@foreach (\App\Models\JadwalPraktik::HARI as $n => $h)<option value="{{ $n }}">{{ $h }}</option>@endforeach</select>
    <input type="time" name="jam_mulai" class="input" value="08:00" required style="min-width:120px"><input type="time" name="jam_selesai" class="input" value="12:00" required style="min-width:120px">
    <input type="number" name="kuota" class="input" value="30" min="1" style="min-width:90px;width:90px" title="Kuota">
    <button class="btn btn-primary"><x-ico n="plus"/> Tambah</button>
  </div>
</form>
<div id="live-jadwal-admin" data-live="jadwal">
@foreach ($dokter as $d)
  <div class="card"><div class="card-h"><h3>{{ $d->nama }}</h3><span class="badge {{ $d->spesialisasi->nama === 'Kandungan' ? 'b-warn' : 'b-info' }}">{{ $d->spesialisasi->poli }}</span></div>
    <div class="table-wrap"><table><tr><th>Hari</th><th>Mulai</th><th>Selesai</th><th>Kuota</th><th>Aktif</th><th></th></tr>
      @forelse ($d->jadwalPraktik as $j)
        <tr><td colspan="6" style="padding:0"><div class="flex" style="padding:8px 14px">
          <form method="POST" action="{{ route('admin.jadwal.update', $j) }}" class="flex" style="flex:1">@csrf @method('PUT')
            <select name="hari" class="input" style="width:auto">@foreach (\App\Models\JadwalPraktik::HARI as $n => $h)<option value="{{ $n }}" @selected($j->hari == $n)>{{ $h }}</option>@endforeach</select>
            <input type="time" name="jam_mulai" class="input" style="width:auto" value="{{ substr($j->jam_mulai, 0, 5) }}"><input type="time" name="jam_selesai" class="input" style="width:auto" value="{{ substr($j->jam_selesai, 0, 5) }}">
            <input type="number" name="kuota" class="input" style="width:80px" value="{{ $j->kuota }}">
            <label class="check"><input type="checkbox" name="aktif" value="1" @checked($j->aktif)> Aktif</label>
            <button class="btn btn-sm">Simpan</button></form>
          <form method="POST" action="{{ route('admin.jadwal.destroy', $j) }}" data-confirm="Hapus jadwal {{ $j->label() }}?" data-confirm-title="Hapus jadwal">@csrf @method('DELETE')<button class="btn btn-sm btn-bad"><x-ico n="trash"/></button></form>
        </div></td></tr>
      @empty <tr><td colspan="6" class="empty">Belum ada jadwal.</td></tr>
      @endforelse
    </table></div></div>
@endforeach
</div>
@endsection

