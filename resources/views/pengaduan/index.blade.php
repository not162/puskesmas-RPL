@extends('layouts.app')
@section('title', 'Pengaduan')
@section('content')
<div class="page-h"><div><h2>Pengaduan pasien</h2><p>Pengaduan baru dari pasien muncul otomatis di sini.</p></div>
  <div class="tabs" style="margin:0">@foreach (['aktif' => 'Aktif', 'baru' => 'Baru', 'diproses' => 'Diproses', 'selesai' => 'Selesai', 'semua' => 'Semua'] as $s => $l)<a class="{{ $status === $s ? 'on' : '' }}" href="?status={{ $s }}">{{ $l }}</a>@endforeach</div></div>
<div class="table-wrap" id="live-pengaduan" data-live="pengaduan"><table>
  <tr><th>Waktu</th><th>Pelapor</th><th>Kategori</th><th>Judul</th><th>Status</th><th></th></tr>
  @forelse ($pengaduan as $p)
    <tr><td class="nowrap">{{ $p->created_at->format('d/m H:i') }}</td><td>{{ $p->akun?->namaTampil() ?? '-' }}</td><td>{{ \App\Models\Pengaduan::KATEGORI[$p->kategori] ?? $p->kategori }}</td>
      <td>{{ $p->judul }}</td><td>@include('partials.status', ['s' => $p->status])</td><td><a class="btn btn-sm btn-primary" href="{{ route('pengaduan.show', $p) }}">Tangani</a></td></tr>
  @empty <tr><td colspan="6" class="empty">Tidak ada pengaduan.</td></tr>
  @endforelse
</table></div>
{{ $pengaduan->links('partials.pager') }}
@endsection

