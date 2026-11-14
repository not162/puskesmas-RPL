@extends('layouts.app')
@section('title', 'Audit')
@section('content')
<div class="page-h"><div><h2>Jejak audit</h2><p>Setiap buat/ubah/hapus data, login, check-in, dan akses KTP tercatat. <a href="{{ route('admin.log') }}">Log login →</a></p></div>
  <form class="filters" method="GET">
    <select name="objek" class="input"><option value="">Semua objek</option>@foreach ($objek as $o)<option @selected(request('objek') === $o)>{{ $o }}</option>@endforeach</select>
    <select name="aksi" class="input"><option value="">Semua aksi</option>@foreach (['buat','ubah','hapus','login','lihat_ktp','qr_tolak','checkin_kiosk'] as $a)<option @selected(request('aksi') === $a)>{{ $a }}</option>@endforeach</select>
    <button class="btn">Filter</button></form></div>
<div class="table-wrap"><table>
  <tr><th>Waktu</th><th>Pengguna</th><th>Aksi</th><th>Objek</th><th>Perubahan</th><th>IP</th></tr>
  @forelse ($log as $l)
    <tr><td class="nowrap">{{ $l->waktu->format('d/m/Y H:i:s') }}</td><td>{{ $l->akun ? '@'.$l->akun->username : 'sistem' }}<br><small>{{ $l->akun?->peran->label }}</small></td>
      <td><span class="badge {{ ['hapus'=>'b-bad','buat'=>'b-ok','qr_tolak'=>'b-bad'][$l->aksi] ?? 'b-info' }}">{{ $l->aksi }}</span></td><td class="mono">{{ $l->objek }}#{{ $l->objek_id }}</td>
      <td class="mono" style="max-width:380px;white-space:normal;word-break:break-word">{{ $l->perubahan ? \Illuminate\Support\Str::limit(json_encode($l->perubahan, JSON_UNESCAPED_UNICODE), 160) : '-' }}</td><td class="mono">{{ $l->ip }}</td></tr>
  @empty <tr><td colspan="6" class="empty">Belum ada catatan.</td></tr>
  @endforelse
</table></div>
{{ $log->links('partials.pager') }}
@endsection

