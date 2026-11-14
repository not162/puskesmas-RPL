@extends('layouts.app')
@section('title', 'Antrian Loket')
@section('content')
<div class="page-h"><div><h2>Antrean {{ \Carbon\Carbon::parse($tanggal)->locale('id')->translatedFormat('d F Y') }}</h2><p>Check-in pasien online, panggil, atau batalkan.</p></div>
  <a href="{{ route('loket.pasien.create') }}" class="btn btn-primary"><x-ico n="plus"/> Daftar pasien datang langsung</a></div>
<form class="filters card" method="GET" style="padding:12px">
  <input type="date" name="tanggal" value="{{ $tanggal }}" class="input">
  <select name="poli" class="input"><option value="">Semua poli</option>@foreach ($spesialisasi as $s)<option value="{{ $s->id_spesialisasi }}" @selected(request('poli') == $s->id_spesialisasi)>{{ $s->poli }}</option>@endforeach</select>
  <select name="status" class="input"><option value="">Semua status</option>@foreach (\App\Models\Antrian::STATUS as $s)<option @selected(request('status') === $s)>{{ $s }}</option>@endforeach</select>
  <input name="q" value="{{ request('q') }}" class="input" placeholder="Nomor / nama / No. RM / kode">
  <button class="btn"><x-ico n="search"/> Cari</button>
</form>
<div id="live-loket-antrian" data-live="antrian,pasien" class="table-wrap mt"><table>
  <tr><th>No.</th><th>Pasien</th><th>Poli</th><th>Sumber</th><th>Status</th><th>Aksi</th></tr>
  @forelse ($antrian as $a)
    <tr><td><b>{{ $a->nomor }}</b></td><td>{{ $a->pasien->nama }}<br><small>{{ $a->pasien->no_rm }}</small></td><td>{{ $a->spesialisasi->poli }}</td><td>{{ ucfirst($a->sumber) }}</td><td>@include('partials.status', ['s' => $a->status])</td>
      <td><div class="actions">
        @if ($a->status === 'menunggu')<form class="inline" method="POST" action="{{ route('loket.antrian.checkin', $a) }}">@csrf<button class="btn btn-sm btn-ok">Check-in</button></form>@endif
        @if (in_array($a->status, ['hadir','dipanggil']))<form class="inline" method="POST" action="{{ route('loket.antrian.panggil', $a) }}">@csrf<button class="btn btn-sm btn-primary">{{ $a->status === 'dipanggil' ? 'Panggil ulang' : 'Panggil' }}</button></form>@endif
        @if (in_array($a->status, ['menunggu','hadir','dipanggil']))<form class="inline" method="POST" action="{{ route('loket.antrian.batal', $a) }}" data-confirm="Batalkan antrean {{ $a->nomor }}? Pasien akan menerima notifikasi." data-confirm-title="Batalkan antrian">@csrf<button class="btn btn-sm btn-bad">Batal</button></form>@endif
      </div></td></tr>
  @empty <tr><td colspan="6" class="empty">Tidak ada antrian.</td></tr>
  @endforelse
</table></div>
{{ $antrian->links('partials.pager') }}
@endsection

