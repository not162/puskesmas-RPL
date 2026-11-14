@extends('layouts.app')
@section('title', 'Tangani Pengaduan')
@section('content')
<div class="grid g2">
  <div class="card"><div class="card-h"><h3>{{ $p->judul }}</h3>@include('partials.status', ['s' => $p->status])</div>
    <dl class="kv"><dt>Pelapor</dt><dd>{{ $p->akun?->namaTampil() ?? '-' }} @if($p->akun?->pasien)({{ $p->akun->pasien->no_rm }})@endif</dd>
      <dt>Kategori</dt><dd>{{ \App\Models\Pengaduan::KATEGORI[$p->kategori] ?? $p->kategori }}</dd><dt>Dikirim</dt><dd>{{ $p->created_at->format('d/m/Y H:i') }}</dd>
      @if($p->penanggap)<dt>Ditangani</dt><dd>{{ $p->penanggap->namaTampil() }}</dd>@endif</dl>
    <p class="mt" style="white-space:pre-line">{{ $p->isi }}</p>
    <form method="POST" action="{{ route('pengaduan.destroy', $p) }}" data-confirm="Hapus pengaduan ini secara permanen?" data-confirm-title="Hapus pengaduan">@csrf @method('DELETE')<button class="btn btn-sm btn-bad">Hapus</button></form>
  </div>
  <form class="card" method="POST" action="{{ route('pengaduan.update', $p) }}">@csrf @method('PUT')
    <h3>Tindak lanjut</h3>
    <div class="field"><label>Status</label><select name="status" class="input">@foreach (['baru','diproses','selesai'] as $s)<option @selected(old('status', $p->status) === $s)>{{ $s }}</option>@endforeach</select></div>
    @include('partials.field', ['name' => 'tanggapan', 'label' => 'Tanggapan untuk pelapor', 'type' => 'textarea', 'value' => $p->tanggapan, 'attrs' => 'rows="5"', 'hint' => 'Wajib bila status selesai. Pelapor menerima notifikasi.'])
    <button class="btn btn-primary btn-block">Simpan & kirim notifikasi</button>
  </form>
</div>
@endsection

