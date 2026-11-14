@extends('layouts.app')
@section('title', 'Pengaduan')
@section('content')
<div class="grid g2">
  <form class="card" method="POST" action="{{ route('pasien.pengaduan.store') }}">@csrf
    <h3>Sampaikan pengaduan</h3>
    <p class="muted">Keluhan layanan, antrean, obat, atau fasilitas. Ditangani petugas loket / admin.</p>
    <div class="field"><label for="kategori">Kategori</label><select id="kategori" name="kategori" class="input" required>
      @foreach (\App\Models\Pengaduan::KATEGORI as $k => $l)<option value="{{ $k }}" @selected(old('kategori') === $k)>{{ $l }}</option>@endforeach</select></div>
    @include('partials.field', ['name' => 'judul', 'label' => 'Judul', 'attrs' => 'required maxlength="150"'])
    @include('partials.field', ['name' => 'isi', 'label' => 'Uraian', 'type' => 'textarea', 'attrs' => 'required rows="5" minlength="10"'])
    <button class="btn btn-primary btn-block">Kirim pengaduan</button>
  </form>
  <div class="card"><h3>Pengaduan saya</h3>
    <div id="live-pengaduan-saya" data-live="pengaduan">
    <ul class="list-plain">
      @forelse ($pengaduan as $p)
        <li><div><b>{{ $p->judul }}</b><br><small>{{ \App\Models\Pengaduan::KATEGORI[$p->kategori] ?? $p->kategori }} · {{ $p->created_at->format('d/m/Y H:i') }}</small>
          @if ($p->tanggapan)<div class="notif-preview"><b>Tanggapan:</b> {{ $p->tanggapan }}</div>@endif</div>@include('partials.status', ['s' => $p->status])</li>
      @empty <li class="muted">Belum ada pengaduan.</li>
      @endforelse
    </ul></div>
    {{ $pengaduan->links('partials.pager') }}
  </div>
</div>
@endsection

