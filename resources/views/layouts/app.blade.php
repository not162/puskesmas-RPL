@extends('layouts.base')
@section('body')
@php
  $u = auth()->user()->loadMissing('peran');
  $r = $u->role();
  // [route, label, ikon, params?]  — sesuai tabel "Navigasi portal per peran"
  $nav = match (true) {
    $r === 'pasien' => [
      ['dashboard', 'Beranda', 'home'], ['pasien.antrian.create', 'Daftar Berobat', 'plus'], ['pasien.antrian.index', 'Antrean Saya', 'ticket'],
      ['pasien.konsultasi.index', 'Riwayat', 'log'], ['pasien.resep', 'Resep & Obat', 'pill'], ['notifikasi', 'Notifikasi', 'bell'],
      ['pasien.pengaduan', 'Pengaduan', 'megaphone'], ['profil', 'Profil', 'user'],
    ],
    $u->isDokter() => [
      ['dokter.antrian', 'Antrean Poli', 'ticket'], ['dokter.pemeriksaan', 'Pemeriksaan', 'steth'], ['dokter.resep', 'Resep', 'pill'],
      ['dokter.riwayat', 'Riwayat Pasien', 'users'], ['dokter.jadwal', 'Jadwal Saya', 'calendar'], ['notifikasi', 'Notifikasi', 'bell'],
      ['dokter.konsultasi', 'Konsultasi Online', 'chat'], ['dashboard', 'Dasbor', 'home'],
    ],
    $r === 'apoteker' => [
      ['farmasi.resep.index', 'Resep Masuk', 'pill', ['status' => 'baru']], ['farmasi.resep.index', 'Proses & Serahkan', 'check', ['status' => 'diproses']],
      ['obat.index', 'Obat & Stok', 'box'], ['farmasi.penyerahan', 'Riwayat Penyerahan', 'log'], ['notifikasi', 'Notifikasi', 'bell'], ['dashboard', 'Dasbor', 'home'],
    ],
    $r === 'loket' => [
      ['loket.pasien.index', 'Cari Pasien', 'search'], ['loket.pasien.create', 'Pasien Baru', 'plus'], ['loket.pendaftaran', 'Pendaftaran', 'list'],
      ['loket.antrian', 'Antrean Hari Ini', 'ticket'], ['loket.qr', 'Check-in QR', 'qr'], ['pengaduan.index', 'Pengaduan', 'megaphone'],
      ['notifikasi', 'Notifikasi', 'bell'], ['dashboard', 'Dasbor', 'home'],
    ],
    $r === 'super_admin' => [
      ['dashboard', 'Dasbor', 'home'], ['admin.akun.index', 'Akun Staf', 'shield'], ['admin.peran', 'Peran & Izin', 'key'],
      ['admin.jadwal', 'Jadwal Praktik', 'calendar'], ['admin.pengumuman.index', 'Pengumuman', 'megaphone'], ['admin.template', 'Template Notifikasi', 'bell'],
      ['laporan', 'Laporan', 'chart'], ['admin.audit', 'Audit', 'log'], ['admin.pengaturan', 'Pengaturan', 'cog'],
    ],
    default => [ // admin (operasional)
      ['dashboard', 'Dasbor', 'home'], ['admin.jadwal', 'Jadwal Praktik', 'calendar'], ['admin.pengumuman.index', 'Pengumuman', 'megaphone'],
      ['pengaduan.index', 'Pengaduan', 'chat'], ['admin.template', 'Kirim Alert', 'bell'], ['laporan', 'Laporan', 'chart'], ['notifikasi', 'Notifikasi', 'bell'],
    ],
  };
  $lain = [['layar', 'Layar Antrean Publik', 'monitor'], ['alur', 'Alur Layanan', 'help']];
  if ($r === 'super_admin') { array_unshift($lain, ['admin.pasien.index', 'Data Pasien', 'users'], ['pengaduan.index', 'Pengaduan', 'chat'], ['admin.log', 'Log Login', 'lock']); $lain[] = ['swagger', 'API Docs (Swagger)', 'code']; }
  if ($r !== 'pasien') $lain[] = ['profil', 'Profil & Keamanan', 'user'];
  $url = fn ($it) => route($it[0], $it[3] ?? []);
  $aktif = function ($it) {
    if (isset($it[3])) return request()->routeIs($it[0]) && collect($it[3])->every(fn ($v, $k) => request($k) === $v);
    return request()->routeIs($it[0]) || (substr_count($it[0], '.') >= 1 && ! str_ends_with($it[0], 'create') && request()->routeIs(\Illuminate\Support\Str::beforeLast($it[0], '.').'.*') && ! request()->routeIs('*.create'));
  };
@endphp
<div class="shell">
  <aside class="sidebar" aria-label="Menu samping">
    <a href="{{ route('beranda') }}" class="brand"><span class="brand-mark"><x-ico n="cross"/></span> {{ $namaPkm }}</a>
    <div class="role-chip"><b>{{ $u->namaTampil() }}</b><span class="muted">{{ $u->peran->label }} · {{ '@'.$u->username }}</span></div>
    <div class="side-label">Menu {{ $u->peran->label }}</div>
    @foreach ($nav as $it)
      <a href="{{ $url($it) }}" class="side-link {{ $aktif($it) ? 'active' : '' }}"><x-ico :n="$it[2]"/> {{ $it[1] }}
        @if ($it[0] === 'notifikasi')<span class="cnt" data-notif-count style="display:none">0</span>@endif</a>
    @endforeach
    <div class="side-label">Lainnya</div>
    @foreach ($lain as $it)
      <a href="{{ $url($it) }}" class="side-link {{ $aktif($it) ? 'active' : '' }}" @if(in_array($it[0], ['layar','swagger'])) target="_blank" @endif><x-ico :n="$it[2]"/> {{ $it[1] }}</a>
    @endforeach
    <form method="POST" action="{{ route('logout') }}" style="margin-top:auto;padding-top:14px">@csrf
      <button class="side-link btn-block" style="border:0;background:none;cursor:pointer;font:inherit"><x-ico n="logout"/> Keluar</button>
    </form>
  </aside>
  <div class="scrim"></div>
  <div class="main">
    <header class="appbar">
      <button class="btn btn-icon btn-ghost mobile-only" data-side aria-label="Buka menu"><x-ico n="menu"/></button>
      <h1>@yield('title')</h1>
      <div class="nav-actions">
        <span class="live live-chip" title="Sinkron real-time antar peran"><span class="dot"></span><span class="hide-sm">Live</span></span>
        <button class="btn btn-icon btn-ghost" data-alert-toggle aria-pressed="false" title="Aktifkan alert suara & pop-up perangkat" aria-label="Aktifkan alert"><span data-alert-icon><x-ico n="bell-off"/></span></button>
        <button class="btn btn-icon btn-ghost" data-toggle-theme aria-label="Ganti tema"><span data-theme-icon></span></button>
        <div class="dropdown">
          <button class="btn btn-icon btn-ghost" data-dropdown aria-label="Notifikasi"><x-ico n="bell"/><span class="bell-count" id="bell-count" style="display:none">0</span></button>
          <div class="menu">
            <div class="menu-h">Notifikasi
              <form method="POST" action="{{ route('notifikasi.bacaSemua') }}">@csrf<button class="btn btn-sm btn-ghost">Tandai dibaca</button></form>
            </div>
            <div id="notif-list"><div class="empty">Memuat…</div></div>
            <a href="{{ route('notifikasi') }}" class="notif-item" style="justify-content:center;font-weight:700">Lihat semua</a>
          </div>
        </div>
      </div>
    </header>
    <div class="content">@yield('content')</div>
    <footer class="app-footer">🇮🇩 Dibuat di Indonesia · © 2026 Seluruh hak cipta dilindungi · Grup NussCorp Technologies Indonesia</footer>
  </div>
</div>
<nav class="bottomnav" aria-label="Navigasi bawah">
  @foreach (array_slice($nav, 0, 4) as $it)
    <a href="{{ $url($it) }}" class="{{ $aktif($it) ? 'on' : '' }}"><x-ico :n="$it[2]"/><span>{{ $it[1] }}</span></a>
  @endforeach
  <button type="button" data-side><x-ico n="menu"/><span>Menu</span></button>
</nav>
<script>window.__REALTIME = @json(route('realtime'));</script>
@endsection
