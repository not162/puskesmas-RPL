@extends('layouts.app')
@section('title', 'Profil & Keamanan')
@section('content')
<div class="grid g2">
  <div class="card">
    <div style="display:flex;align-items:center;gap:16px;margin-bottom:18px;padding-bottom:16px;border-bottom:1px solid var(--line)">
      <img src="{{ $akun->avatarUrl() }}" alt="Avatar {{ $akun->namaTampil() }}" style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:2px solid var(--primary);box-shadow:0 2px 8px rgba(0,0,0,0.08);background:var(--panel-2)">
      <div>
        <div style="font-weight:700;font-size:17px">{{ $akun->namaTampil() }}</div>
        <div class="muted" style="font-size:13px">{{ $akun->peran->label }} · {{ '@'.$akun->username }}</div>
      </div>
    </div>
    <h3>Data akun</h3>
    <dl class="kv"><dt>Username</dt><dd>{{ '@'.$akun->username }}</dd><dt>Nama</dt><dd>{{ $akun->namaTampil() }}</dd><dt>Peran</dt><dd>{{ $akun->peran->label }}</dd><dt>Status</dt><dd>@include('partials.status', ['s' => $akun->status])</dd>
      @if ($akun->pasien)<dt>No. RM</dt><dd>{{ $akun->pasien->no_rm }}</dd><dt>NIK</dt><dd>{{ $akun->pasien->nik }}</dd><dt>Tanggal lahir</dt><dd>{{ $akun->pasien->tgl_lahir?->format('d/m/Y') }}</dd>@endif
    </dl>
    <div class="notif-preview mt"><b>Data pribadi Anda</b><br><span class="muted">Sesuai UU Pelindungan Data Pribadi, Anda berhak memperoleh salinan data Anda.</span><br>
      <a class="btn btn-sm mt" href="{{ route('profil.unduh') }}"><x-ico n="download"/> Unduh data saya</a> <a class="btn btn-sm btn-ghost mt" href="{{ route('privasi') }}">Kebijakan privasi</a></div>
  </div>
  <form class="card" method="POST" action="{{ route('profil') }}" enctype="multipart/form-data">@csrf @method('PUT')
    <h3>Ubah profil</h3>
    <div class="field" style="padding:14px;background:var(--panel-2);border-radius:12px;border:1px solid var(--line);margin-bottom:18px">
      <label style="font-weight:600;font-size:13.5px;display:block;margin-bottom:8px">Avatar / Foto Profil</label>
      <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
        <img id="avatar-preview" src="{{ $akun->avatarUrl() }}" alt="Preview Avatar" style="width:68px;height:68px;border-radius:50%;object-fit:cover;border:2px solid var(--primary);background:var(--panel);box-shadow:0 2px 8px rgba(0,0,0,0.06);flex:none">
        <div style="flex:1;min-width:210px">
          <input type="file" id="foto" name="foto" accept=".png,.jpg,.jpeg,.webp" class="input input-sm" style="margin-bottom:6px">
          <div class="hint" style="font-size:12px">Format standar: <b>.PNG</b>, <b>.JPG</b>, atau <b>.WEBP</b>. Ukuran maksimal: <b>2 MB</b>.</div>
          @if ($akun->foto)
            <label class="check" style="margin-top:6px;font-size:12.5px;color:var(--bad);display:inline-flex;align-items:center;gap:6px">
              <input type="checkbox" name="hapus_foto" value="1"> Hapus foto profil (kembali ke inisial)
            </label>
          @endif
          @error('foto')<div class="err-msg" style="margin-top:4px">{{ $message }}</div>@enderror
        </div>
      </div>
    </div>
    @include('partials.field', ['name' => 'email', 'label' => 'Email'.($akun->pasien ? ' (opsional)' : ''), 'type' => 'email', 'value' => $akun->email])
    @if ($akun->pasien)
      <div class="field">
        <label style="display:flex;justify-content:space-between;align-items:center">
          <span>Nomor HP</span>
          <span class="badge b-info" style="font-size:11px;font-weight:600"><x-ico n="lock"/> Terkunci (Sesuai Registrasi)</span>
        </label>
        <input type="text" value="{{ $akun->pasien->no_tlp }}" disabled class="input" style="background:var(--bg);cursor:not-allowed;opacity:0.85">
        <div class="hint">Nomor HP telah diverifikasi saat registrasi akun.</div>
      </div>
      <div class="field">
        <label style="display:flex;justify-content:space-between;align-items:center">
          <span>Alamat</span>
          <span class="badge b-info" style="font-size:11px;font-weight:600"><x-ico n="lock"/> Terkunci (Sesuai Registrasi)</span>
        </label>
        <textarea disabled class="input" rows="2" style="background:var(--bg);cursor:not-allowed;opacity:0.85">{{ $akun->pasien->alamat }}</textarea>
        <div class="hint">Alamat domisili resmi pasien. Untuk perubahan data, silakan konfirmasi ke petugas loket.</div>
      </div>
      <label class="check field"><input type="checkbox" name="wa_opt_in" value="1" @checked(old('wa_opt_in', $akun->pasien->wa_opt_in))> Terima notifikasi & OTP cadangan lewat WhatsApp</label>
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

@push('scripts')
<script>
(() => {
  const f = document.getElementById('foto');
  const p = document.getElementById('avatar-preview');
  if (f && p) {
    f.addEventListener('change', () => {
      const file = f.files[0];
      if (file) {
        const r = new FileReader();
        r.onload = e => { p.src = e.target.result; };
        r.readAsDataURL(file);
      }
    });
  }
})();
</script>
@endpush
