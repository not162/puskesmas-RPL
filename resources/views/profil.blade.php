@extends('layouts.app')
@section('title', 'Profil & Keamanan')
@section('content')
<div class="grid g2">
  <div class="card"><h3>Data akun</h3>
    <dl class="kv"><dt>Username</dt><dd>{{ '@'.$akun->username }}</dd><dt>Nama</dt><dd>{{ $akun->namaTampil() }}</dd><dt>Peran</dt><dd>{{ $akun->peran->label }}</dd><dt>Status</dt><dd>@include('partials.status', ['s' => $akun->status])</dd>
      @if ($akun->pasien)<dt>No. RM</dt><dd>{{ $akun->pasien->no_rm }}</dd><dt>NIK</dt><dd>{{ $akun->pasien->nik }}</dd><dt>Tanggal lahir</dt><dd>{{ $akun->pasien->tgl_lahir?->format('d/m/Y') }}</dd>@endif
    </dl></div>
  <form class="card" method="POST" action="{{ route('profil') }}">@csrf @method('PUT')
    <h3>Ubah profil</h3>
    @include('partials.field', ['name' => 'email', 'label' => 'Email'.($akun->pasien ? ' (opsional)' : ''), 'type' => 'email', 'value' => $akun->email])
    @if ($akun->pasien)
      @include('partials.field', ['name' => 'no_tlp', 'label' => 'Nomor HP', 'value' => $akun->pasien->no_tlp])
      @include('partials.field', ['name' => 'alamat', 'label' => 'Alamat', 'type' => 'textarea', 'value' => $akun->pasien->alamat, 'attrs' => 'rows="2"'])
    @endif
    <hr style="border:0;border-top:1px solid var(--line);margin:18px 0">
    <p class="muted">Kosongkan bila tidak ingin mengganti kata sandi.</p>
    @include('partials.password', ['name' => 'password_lama', 'label' => 'Password lama', 'auto' => 'current-password', 'attrs' => ''])
    @include('partials.password', ['name' => 'password', 'label' => 'Password baru', 'attrs' => ''])
    @include('partials.password', ['name' => 'password_confirmation', 'label' => 'Ulangi password baru', 'attrs' => ''])
    <button class="btn btn-primary">Simpan</button>
  </form>
</div>
@endsection

