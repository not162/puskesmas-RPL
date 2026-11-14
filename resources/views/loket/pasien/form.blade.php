@extends('layouts.app')
@section('title', $pasien->exists ? 'Ubah Data Pasien' : 'Pasien Datang Langsung')
@section('content')
<form class="card" style="max-width:760px" method="POST" action="{{ $pasien->exists ? route('loket.pasien.update', $pasien) : route('loket.pasien.store') }}">@csrf
  @if ($pasien->exists) @method('PUT') @endif
  <h3>{{ $pasien->exists ? 'Ubah data '.$pasien->nama : 'Daftarkan pasien' }}</h3>
  <div class="grid g2">
    @include('partials.field', ['name' => 'nik', 'label' => 'NIK', 'value' => $pasien->nik, 'attrs' => 'required maxlength="16" inputmode="numeric"'])
    @include('partials.field', ['name' => 'nama', 'label' => 'Nama lengkap', 'value' => $pasien->nama, 'attrs' => 'required'])
    @include('partials.field', ['name' => 'tgl_lahir', 'label' => 'Tanggal lahir', 'type' => 'date', 'value' => $pasien->tgl_lahir?->toDateString()])
    <div class="field"><label>Jenis kelamin</label><select name="jenis_kelamin" class="input"><option value="">-</option><option value="L" @selected(old('jenis_kelamin', $pasien->jenis_kelamin) === 'L')>Laki-laki</option><option value="P" @selected(old('jenis_kelamin', $pasien->jenis_kelamin) === 'P')>Perempuan</option></select></div>
    @include('partials.field', ['name' => 'no_tlp', 'label' => 'Nomor HP', 'value' => $pasien->no_tlp])
    @include('partials.field', ['name' => 'no_bpjs', 'label' => 'No. BPJS (opsional)', 'value' => $pasien->no_bpjs, 'attrs' => 'maxlength="13" inputmode="numeric"'])
  </div>
  @include('partials.field', ['name' => 'alamat', 'label' => 'Alamat', 'type' => 'textarea', 'value' => $pasien->alamat, 'attrs' => 'rows="2"'])
  <label class="check field"><input type="checkbox" name="wa_opt_in" value="1" @checked(old('wa_opt_in', $pasien->wa_opt_in))> Pasien setuju menerima notifikasi WhatsApp (tanyakan langsung)</label>
  @unless ($pasien->exists)
    <div class="grid g2">
      <div class="field"><label>Langsung ambil antrean hari ini</label><select name="id_spesialisasi" class="input"><option value="">Tidak</option>@foreach ($spesialisasi as $s)<option value="{{ $s->id_spesialisasi }}">{{ $s->poli }}</option>@endforeach</select><div class="hint">Antrean loket otomatis berstatus "hadir".</div></div>
      @include('partials.field', ['name' => 'keluhan_awal', 'label' => 'Keluhan singkat'])
    </div>
  @endunless
  <div class="flex"><button class="btn btn-primary">Simpan</button><a class="btn" href="{{ route('loket.pasien.index') }}">Kembali</a></div>
</form>
@endsection

