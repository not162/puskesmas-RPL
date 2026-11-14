@extends('layouts.app')
@section('title', $akun->exists ? 'Ubah Akun' : 'Tambah Akun Staf')
@section('content')
<form class="card" style="max-width:760px" method="POST" action="{{ $akun->exists ? route('admin.akun.update', $akun) : route('admin.akun.store') }}">@csrf
  @if ($akun->exists) @method('PUT') @endif
  <div class="field"><label for="id_peran">Peran</label>
    <select id="id_peran" name="id_peran" class="input @error('id_peran') err @enderror" {{ $akun->exists ? 'disabled' : 'required' }}>
      <option value="">— Pilih peran —</option>
      @foreach ($peran as $p)<option value="{{ $p->id_peran }}" data-role="{{ $p->nama_peran }}" @selected(old('id_peran', $akun->id_peran) == $p->id_peran)>{{ $p->label }}</option>@endforeach
    </select>
    <div class="hint">Dokter hanya tersedia sebagai <b>Dokter Umum</b> atau <b>Dokter Kandungan</b>; spesialisasi diatur otomatis.</div>
    @error('id_peran')<div class="err-msg">{{ $message }}</div>@enderror</div>
  <div class="grid g2">
    @include('partials.field', ['name' => 'nama', 'label' => 'Nama lengkap', 'value' => $akun->exists ? $akun->namaTampil() : '', 'attrs' => 'required'])
    @include('partials.field', ['name' => 'username', 'label' => 'Username', 'value' => $akun->username, 'hint' => '4–30: huruf kecil, angka, . _', 'attrs' => 'required autocapitalize="none"'])
    @include('partials.field', ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'value' => $akun->email, 'attrs' => 'required'])
    @include('partials.password', ['name' => 'password', 'label' => $akun->exists ? 'Password baru (opsional)' : 'Password', 'hint' => 'Minimal 8 karakter, berbeda untuk tiap akun.', 'attrs' => $akun->exists ? '' : 'required'])
    <div class="field"><label>Status</label><select name="status" class="input"><option value="aktif" @selected(old('status', $akun->status ?? 'aktif') === 'aktif')>Aktif</option><option value="nonaktif" @selected(old('status', $akun->status) === 'nonaktif')>Nonaktif</option></select></div>
  </div>
  <div class="grid g2" data-for="dokter">
    @include('partials.field', ['name' => 'no_str', 'label' => 'No. STR', 'value' => $akun->dokter?->no_str])
    @include('partials.field', ['name' => 'jadwal', 'label' => 'Jadwal praktik', 'value' => $akun->dokter?->jadwal, 'attrs' => 'placeholder="Senin–Jumat 08.00–14.00"'])
  </div>
  <div data-for="apoteker">@include('partials.field', ['name' => 'no_sipa', 'label' => 'No. SIPA', 'value' => $akun->apoteker?->no_sipa])</div>
  <div class="flex"><button class="btn btn-primary">Simpan</button><a class="btn" href="{{ route('admin.akun.index') }}">Batal</a></div>
</form>
@endsection
@push('scripts')
<script>
(function () {
  const sel = document.getElementById('id_peran');
  const sync = () => { const r = sel.selectedOptions[0]?.dataset.role || '';
    document.querySelector('[data-for=dokter]').style.display = r.startsWith('dokter') ? '' : 'none';
    document.querySelector('[data-for=apoteker]').style.display = r === 'apoteker' ? '' : 'none'; };
  sel.addEventListener('change', sync); sync();
})();
</script>
@endpush

