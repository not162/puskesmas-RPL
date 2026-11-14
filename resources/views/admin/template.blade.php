@extends('layouts.app')
@section('title', 'Template Notifikasi & Alert')
@section('content')
<form class="card" method="POST" action="{{ route('admin.broadcast') }}">@csrf
  <div class="card-h"><h3><x-ico n="bell-ring"/> Kirim alert ke peran</h3><span class="muted">Diterima real-time (lonceng, toast, pop-up perangkat bila alert aktif)</span></div>
  <div class="field"><label>Penerima</label><div class="flex">
    @foreach ($peran as $p)<label class="check"><input type="checkbox" name="peran[]" value="{{ $p->nama_peran }}" @checked(in_array($p->nama_peran, old('peran', [])))> {{ $p->label }}</label>@endforeach</div>
    @error('peran')<div class="err-msg">{{ $message }}</div>@enderror</div>
  <div class="grid g2">
    @include('partials.field', ['name' => 'judul', 'label' => 'Judul alert', 'attrs' => 'required maxlength="100"'])
    <div class="field"><label>Tingkat</label><select name="tipe" class="input"><option value="info">Info</option><option value="success">Sukses</option><option value="warning">Peringatan</option><option value="danger">Darurat</option></select></div>
  </div>
  @include('partials.field', ['name' => 'pesan', 'label' => 'Pesan', 'type' => 'textarea', 'attrs' => 'required rows="2" maxlength="500"'])
  <button class="btn btn-warn"><x-ico n="megaphone"/> Kirim alert sekarang</button>
</form>
<div class="card"><h3>Template notifikasi langkah layanan</h3>
  <p class="muted">Placeholder: <code>:no_rm</code> <code>:poli</code> <code>:nomor</code> <code>:tanggal</code> <code>:dokter</code>. Matikan untuk menonaktifkan notifikasi langkah tersebut.</p>
  @foreach ($template as $t)
    <form method="POST" action="{{ route('admin.template.update', $t) }}" class="grid" style="grid-template-columns:40px 1fr 2fr auto auto;align-items:center;gap:8px;margin-bottom:8px">@csrf @method('PUT')
      <span class="badge b-info">{{ $t->langkah }}</span>
      <input name="judul" class="input" value="{{ $t->judul }}" @disabled(!$bolehUbah)>
      <input name="pesan" class="input" value="{{ $t->pesan }}" @disabled(!$bolehUbah)>
      <label class="check"><input type="checkbox" name="aktif" value="1" @checked($t->aktif) @disabled(!$bolehUbah)> Aktif</label>
      @if ($bolehUbah)<button class="btn btn-sm">Simpan</button>@endif
    </form>
  @endforeach
</div>
@endsection

